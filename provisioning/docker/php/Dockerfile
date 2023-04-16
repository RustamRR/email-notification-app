FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
        libzip-dev \
        unzip \
        librabbitmq-dev \
        supervisor \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install mysqli zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY ./docker/php/app.conf /etc/supervisor/conf.d/
