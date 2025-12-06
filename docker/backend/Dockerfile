FROM php:8.3.9-apache

ENV TZ=Asia/Ho_Chi_Minh \
    LANG=vi_VN.UTF-8 \
    LC_ALL=vi_VN.UTF-8 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_VERSION=2.7.7

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# =========================================================
# 1. CÀI MỌI THỨ + FIX LỖI 77 VĨNH VIỄN
# =========================================================
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl zip unzip git gnupg tzdata ca-certificates locales wget \
    libpng-dev libzip-dev libonig-dev libfreetype6-dev libjpeg62-turbo-dev libexif-dev \
    libreoffice libreoffice-writer libreoffice-l10n-vi \
    libcairo2 libcups2 libxinerama1 libxrandr2 libsm6 fontconfig \
    fonts-noto-core fonts-noto-cjk fonts-noto-color-emoji \
    fonts-liberation2 fonts-dejavu-core fonts-crosextra-carlito fonts-crosextra-caladea \
    fonts-roboto \
    && echo "deb http://deb.debian.org/debian bookworm contrib non-free" >> /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y ttf-mscorefonts-installer \
    && fc-cache -f -v \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g pnpm@latest \
    && rm -rf /var/lib/apt/lists/*

# =========================================================
# 2. PHP Extensions
# =========================================================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) bcmath gd opcache zip exif pdo_mysql mbstring

RUN pecl install xdebug && docker-php-ext-enable xdebug

# =========================================================
# 3. Apache + Composer
# =========================================================
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && a2enmod rewrite headers

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=$COMPOSER_VERSION

# =========================================================
# 4. FIX CHẾT MẸ LỖI 77 – 3 DÒNG CỨU MẠNG
# =========================================================
# Tạo home + cache riêng cho www-data để LibreOffice không đái ra quần
RUN mkdir -p /var/www/.cache /var/www/.config \
    && chown -R www-data:www-data /var/www/.cache /var/www/.config \
    && chmod -R 755 /var/www/.cache /var/www/.config

# Profile tạm cho LibreOffice (không cần --env nữa cũng được nhưng giữ cho chắc)
RUN mkdir -p /tmp/libreoffice && chown -R www-data:www-data /tmp/libreoffice

# Storage permission
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

WORKDIR /var/www/html
USER www-data

EXPOSE 80
CMD ["apache2-foreground"]