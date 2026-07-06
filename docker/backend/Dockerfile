FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pdo \
        pdo_mysql \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache

COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8001

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8001"]
