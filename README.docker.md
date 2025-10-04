# Cookbook Campus - Docker Deployment

This Symfony application is configured for deployment on Render.com with Docker.

## Local Development

### Prerequisites
- Docker and Docker Compose
- Git

### Setup

1. Clone the repository:
```bash
git clone <your-repo-url>
cd cookbook-campus
```

2. Copy environment file:
```bash
cp .env.example .env
```

3. Start the development environment:
```bash
docker-compose up -d
```

4. Install dependencies and run migrations:
```bash
docker-compose exec app composer install
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

5. Load fixtures (optional):
```bash
docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

### Access the application

- **Application**: http://localhost:8080
- **Adminer (Database UI)**: http://localhost:8081
- **MailHog (Email testing)**: http://localhost:8025

## Production Deployment on Render.com

### Prerequisites
- Render.com account
- GitHub repository

### Deployment Steps

1. **Push your code to GitHub** (make sure .env is not committed):
```bash
git add .
git commit -m "Add Docker configuration for Render.com"
git push origin main
```

2. **Connect to Render.com**:
   - Go to [Render.com](https://render.com)
   - Connect your GitHub repository
   - Render will automatically detect the `render.yaml` file

3. **Environment Variables** (set in Render dashboard):
   - `APP_SECRET`: Generate a 32+ character random string
   - `MAILER_DSN`: Configure your email service (e.g., SendGrid, Mailgun)
   - Other variables are automatically configured via `render.yaml`

4. **Deploy**:
   - Render will automatically build and deploy your application
   - The database will be created automatically
   - Health checks will ensure the application is running properly

### Database

The application uses PostgreSQL in production. The database connection is automatically configured through Render's environment variables.

### Assets

Static assets are built during the Docker build process and served efficiently by Nginx.

## Development Commands

```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f app

# Execute commands in the container
docker-compose exec app php bin/console cache:clear
docker-compose exec app php bin/console doctrine:migrations:migrate

# Stop services
docker-compose down

# Rebuild and start
docker-compose up -d --build
```

## Troubleshooting

### Database Connection Issues
- Ensure DATABASE_URL is correctly set
- Check if the database service is running: `docker-compose ps`

### Permission Issues
- Reset permissions: `docker-compose exec app chown -R www-data:www-data /var/www/html/var`

### Cache Issues
- Clear cache: `docker-compose exec app php bin/console cache:clear`

### Asset Issues
- Rebuild assets: `docker-compose exec app npm run build`

## Security

- Environment variables are properly excluded from version control
- Production uses optimized Docker images
- Nginx is configured with security headers
- Database credentials are managed by Render.com

## Performance

- Multi-stage Docker builds for optimized image size
- PHP OPcache enabled in production
- Nginx serves static assets efficiently
- Database connection pooling via Doctrine