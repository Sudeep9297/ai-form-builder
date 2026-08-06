FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader
COPY . .
RUN composer dump-autoload --optimize

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM php:8.2-apache
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y libzip-dev libpng-dev libxml2-dev unzip \
    && docker-php-ext-install pdo_mysql zip gd \
    && a2enmod rewrite \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
RUN chmod +x scripts/render-web.sh scripts/render-worker.sh \
    && chown -R www-data:www-data storage bootstrap/cache
CMD ["scripts/render-web.sh"]
