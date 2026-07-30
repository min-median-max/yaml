FROM php:8.3-cli-alpine AS base

RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        linux-headers \
        autoconf \
        g++ \
        make \
    && apk add --no-cache \
        git \
        unzip \
        curl \
        zip \
        libzip-dev \
    && docker-php-ext-install -j$(nproc) \
        zip \
        opcache \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/*

RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

COPY --from=composer:2.6 /usr/bin/composer /usr/local/bin/composer

RUN addgroup -g 1001 -S appgroup && adduser -u 1001 -S appuser -G appgroup

FROM base AS development

WORKDIR /app
RUN chown appuser:appgroup /app

USER appuser

COPY --chown=appuser:appgroup composer.json composer.lock* ./

RUN composer install

COPY --chown=appuser:appgroup . .

FROM base AS production

WORKDIR /app
RUN chown appuser:appgroup /app

USER appuser

COPY --chown=appuser:appgroup composer.json composer.lock* ./

RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

COPY --chown=appuser:appgroup src/ ./src/

RUN composer dump-autoload --optimize --no-dev

CMD ["php", "-a"]
