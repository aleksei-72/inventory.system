FROM php:fpm-alpine

RUN apk upgrade --update
  && apk add \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev
  && docker-php-ext-configure gd 
  && docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    gd \
    intl \
    zip

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  && php composer-setup.php
  && php -r "unlink('composer-setup.php');"
  && mv composer.phar /usr/local/bin/composer

COPY . /var/www/html

RUN composer install

CMD ["php-fpm"]
