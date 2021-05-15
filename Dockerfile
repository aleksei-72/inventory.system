FROM php:fpm-alpine

RUN apk upgrade --update && apk add postgresql-dev && docker-php-ext-install pdo_pgsql pgsql

CMD ["php-fpm"]
