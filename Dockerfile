FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
        oniguruma-dev \
        libxml2-dev \
        libzip-dev \
        curl \
        autoconf \
        gcc \
        g++ \
        make \
        musl-dev \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --ignore-platform-reqs \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
