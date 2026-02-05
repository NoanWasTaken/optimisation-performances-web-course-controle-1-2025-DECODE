FROM php:8.4-apache

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP
RUN apt-get update -y && apt-get upgrade -y
RUN apt-get install -y zlib1g-dev libwebp-dev libpng-dev libjpeg-dev libfreetype6-dev && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype && docker-php-ext-install gd
RUN apt-get install libzip-dev -y && docker-php-ext-install zip
RUN apt-get install libpq-dev -y
RUN docker-php-ext-install pdo pdo_pgsql

RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Composer
#COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -fsSL https://deb.nodesource.com/setup_22.x -o nodesource_setup.sh
RUN bash nodesource_setup.sh
RUN apt-get install -y nodejs

# Apache
RUN a2enmod rewrite
RUN service apache2 restart

EXPOSE 80