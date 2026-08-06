FROM composer:2 AS composer

FROM php:8.2-cli AS vendor
WORKDIR /app
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libonig-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install gd mbstring pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/bin/composer
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
RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    libpng-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install gd mbstring pdo_mysql zip \
    && a2dismod -f mpm_event mpm_worker \
    && a2enmod mpm_prefork rewrite \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*
COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
RUN chmod +x scripts/render-web.sh scripts/render-worker.sh \
    && chown -R www-data:www-data storage bootstrap/cache
CMD ["scripts/render-web.sh"]
