#!/bin/bash
set -e

echo "Starting Symfony application..."

# Install dependencies if not already installed
if [ ! -d "vendor" ]; then
    echo "Installing PHP dependencies..."
    composer install --no-scripts
fi

# Wait for database to be ready (if DATABASE_URL is set)
if [ ! -z "$DATABASE_URL" ]; then
    echo "Waiting for database to be ready..."
    until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
        echo "Database is unavailable - sleeping"
        sleep 2
    done
    echo "Database is ready!"
fi

# Clear and warm up cache
echo "Clearing cache..."
php bin/console cache:clear --no-debug

echo "Warming up cache..."
php bin/console cache:warmup --no-debug

# Run database migrations
if [ ! -z "$DATABASE_URL" ]; then
    echo "Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

    # Check if fixtures should be loaded (only in dev environment)
    if [ "$APP_ENV" = "dev" ]; then
        echo "Checking if fixtures need to be loaded..."
        RECIPE_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM recipe" --env=dev 2>/dev/null | tail -n 1 || echo "0")
        
        if [ "$RECIPE_COUNT" -eq "0" ]; then
            echo "📊 No data found, loading demo fixtures..."
            php bin/console doctrine:fixtures:load --group=demo --no-interaction --env=dev
            echo "✅ Demo fixtures loaded successfully!"
        else
            echo "📊 Found $RECIPE_COUNT recipes, skipping fixtures..."
        fi
    fi
fi

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/var /var/www/html/public/uploads
chmod -R 755 /var/www/html/var
chmod -R 775 /var/www/html/var/cache /var/www/html/var/log
chmod -R 755 /var/www/html/public/uploads

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm