FROM composer:2 AS composer-bin
FROM php:8.5-cli-alpine3.23

RUN set -eux; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev sqlite-dev \
        libpng-dev libjpeg-turbo-dev libwebp-dev libavif-dev imagemagick-dev; \
    docker-php-ext-configure gd --with-jpeg --with-webp --with-avif; \
    docker-php-ext-install -j"$(nproc)" intl zip pdo_sqlite gd exif; \
    pecl install imagick; \
    docker-php-ext-enable imagick; \
    apk add --no-cache icu-libs icu-data-full libzip sqlite-libs libpng libjpeg-turbo libwebp libavif \
        imagemagick imagemagick-jpeg imagemagick-webp imagemagick-heic libgomp git unzip; \
    apk del --no-network .build-deps

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /app
EXPOSE 8000

# The built-in server is enough for an example. No router script: it serves the files of public/ itself (with their
# types - a router would hand the built CSS out as text/html) and everything else to public/index.php.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
