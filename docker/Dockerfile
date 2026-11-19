# Production image for CyberLearn AI: PHP 8.4 + Apache serving public/.
# Build: docker compose -f docker-compose.prod.yml build

# ---- PHP dependencies -------------------------------------------------------
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts

# ---- Front-end assets (public/build is not committed) ------------------------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
# Tailwind scans the pagination views (see resources/css/app.css @source).
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ---- Runtime -----------------------------------------------------------------
FROM php:8.4-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libicu-dev libzip-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl zip opcache \
 && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite headers deflate \
 && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/apache.conf /etc/apache2/conf-enabled/zz-app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build
RUN chmod +x /usr/local/bin/entrypoint \
 && rm -f public/hot \
 && su www-data -s /bin/sh -c "php artisan package:discover --ansi"

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
