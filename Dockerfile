FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

RUN mkdir -p /var/www/html/admin/uploaded-files/cars \
    /var/www/html/admin/uploaded-files/brands \
    /var/www/html/admin/uploaded-files/customer-doc \
    && chown -R www-data:www-data /var/www/html/admin/uploaded-files

EXPOSE 80
