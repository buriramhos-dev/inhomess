FROM php:8.1-apache

# Install extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip libonig-dev libicu-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && a2enmod rewrite \
    && a2dismod mpm_event \
    && a2dismod mpm_worker \
    && a2enmod mpm_prefork

# Copy application
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
