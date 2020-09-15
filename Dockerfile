FROM composer:1.9.2

RUN apk --no-cache add libzip-dev zlib-dev icu-dev \
 && docker-php-ext-install zip \
 && docker-php-ext-install intl
