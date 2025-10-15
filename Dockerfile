FROM php:8.3-fpm-alpine

LABEL maintainer="tomas@pobo.cz"
LABEL description="PHP 8.3 FPM with Composer for webhook processing"

RUN apk add --no-cache \
    git \
    unzip \
    bash \
    curl \
    && docker-php-ext-install \
    opcache

RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN { \
    echo 'error_reporting = E_ALL'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'memory_limit = 256M'; \
    echo 'post_max_size = 20M'; \
    echo 'upload_max_filesize = 20M'; \
    echo 'max_execution_time = 30'; \
    echo 'date.timezone = Europe/Prague'; \
} > /usr/local/etc/php/conf.d/custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --prefer-dist

COPY src/ ./

RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/logs

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php-fpm -t || exit 1

USER www-data

EXPOSE 9000

CMD ["php-fpm"]