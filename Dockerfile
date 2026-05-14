FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    postgresql-dev icu-dev libzip-dev git unzip \
    && docker-php-ext-install pdo pdo_pgsql intl zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public/"]
