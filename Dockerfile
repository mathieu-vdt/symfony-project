# Dockerfile simple pour Symfony
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install minimal dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy and make startup script executable
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port
EXPOSE 9000

# Use startup script instead of direct php-fpm
CMD ["/usr/local/bin/start.sh"]