FROM php:8.3.9-apache

# Composer version
ENV COMPOSER_VERSION=2.7.7

# Cài PHP extensions cần thiết + utils
RUN apt-get update && apt-get install -y \
    curl zip unzip git libpng-dev libzip-dev libexif-dev libonig-dev gnupg tzdata \
    nodejs npm \
    && docker-php-ext-install bcmath gd opcache sockets zip exif pdo_mysql mysqli mbstring \
    && docker-php-ext-enable pdo_mysql mysqli

# Cài xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Set document root cho Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Cài Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=$COMPOSER_VERSION

# Bật mod_rewrite
RUN a2enmod rewrite

# Tạo user app
RUN useradd -ms /bin/bash -u 1000 app

# Cài Node.js + pnpm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g pnpm@latest

WORKDIR /var/www/html
