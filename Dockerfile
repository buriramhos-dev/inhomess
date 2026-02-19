FROM php:8.1-cli

RUN apt-get update \
    && apt-get install -y libzip-dev unzip libonig-dev libicu-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip

WORKDIR /app
COPY . /app

EXPOSE 8080

CMD php -S 0.0.0.0:8080
