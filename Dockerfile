# ─── Stage 1: Build frontend assets ─────────────────────────────────────────
FROM node:20 AS build-assets
WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .

# Accept VITE_ vars at build time so they get baked into the JS bundle
ARG VITE_APP_NAME=TheZoo
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=https

ENV VITE_APP_NAME=$VITE_APP_NAME
ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY
ENV VITE_REVERB_HOST=$VITE_REVERB_HOST
ENV VITE_REVERB_PORT=$VITE_REVERB_PORT
ENV VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

RUN npm run build

# ─── Stage 2: Production Application ─────────────────────────────────────────
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    zip unzip libpq-dev libzip-dev libicu-dev \
    supervisor nginx \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd intl zip

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy application source
COPY . /var/www

# Overlay Vite build output (generated in stage 1)
COPY --from=build-assets /app/public/build /var/www/public/build

# Install PHP dependencies (production, no dev)
RUN composer install --optimize-autoloader --no-dev

# Fix storage permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Nginx configuration
COPY docker/nginx/conf.d/app.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/

# Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
