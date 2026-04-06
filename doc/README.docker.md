# Docker Deployment Guide

This project includes a Docker configuration for production deployment using Docker Compose.

## Prerequisites

- Docker and Docker Compose installed on your server.
- A `.env` file with production configuration (copy from `.env.example`).

## Setup Instructions

1.  **Configure Environment Variables**

    Copy `.env.example` to `.env` and update the values:

    ```bash
    cp .env.example .env
    ```

    Make sure to set `APP_ENV=production`, `APP_DEBUG=false`, and `APP_KEY`.
    Also set database credentials (these will be used by Docker Compose):

    ```ini
    DB_CONNECTION=pgsql
    DB_HOST=db
    DB_PORT=5432
    DB_DATABASE=wwm
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

    REDIS_HOST=redis
    REDIS_PASSWORD=null
    REDIS_PORT=6379
    ```

2.  **Build and Run**

    Run the following command to build and start the containers:

    ```bash
    docker-compose -f docker-compose.prod.yml up -d --build
    ```

3.  **Run Migrations**

    Once the containers are up, run the database migrations:

    ```bash
    docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
    ```

4.  **Create Storage Link**

    ```bash
    docker-compose -f docker-compose.prod.yml exec app php artisan storage:link
    ```

5.  **Optimization (Optional)**

    For better performance in production:

    ```bash
    docker-compose -f docker-compose.prod.yml exec app php artisan config:cache
    docker-compose -f docker-compose.prod.yml exec app php artisan route:cache
    docker-compose -f docker-compose.prod.yml exec app php artisan view:cache
    ```

## Services

-   **app**: The main application container running Nginx and PHP-FPM (managed by Supervisor).
-   **queue**: Runs the Laravel queue worker.
-   **scheduler**: Runs the Laravel task scheduler.
-   **db**: PostgreSQL database.
-   **redis**: Redis for caching and queues.

## Troubleshooting

-   **Logs**: Check logs with `docker-compose -f docker-compose.prod.yml logs -f`.
-   **Permissions**: If you encounter permission issues, ensure storage directories are writable by `www-data` (handled in Dockerfile).
