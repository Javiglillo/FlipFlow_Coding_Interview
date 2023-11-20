FROM php:8.2-cli

USER root

WORKDIR /var/www

COPY . /var/www/

RUN apt-get update \
    && apt-get install -y \
        libzip-dev \
        zip \
        unzip \
        sqlite3 \
        libsqlite3-dev \
    && docker-php-ext-install zip pdo_sqlite

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-scripts --no-interaction --optimize-autoloader \
    && rm -rf /usr/local/bin/composer

CMD ["php", "artisan"]
