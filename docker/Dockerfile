FROM php:8.3-fpm-alpine

# Устанавливаем системные зависимости + linux-headers для компиляции sockets
RUN apk update && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxml2-dev \
    linux-headers \
    zip \
    unzip \
    shadow

# Устанавливаем расширения PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    sockets \
    opcache

# Остальная часть без изменений
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN groupmod -g 1000 www-data && \
    usermod -u 1000 -g www-data www-data && \
    chown -R www-data:www-data /var/www
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
WORKDIR /var/www
ENV COMPOSER_MEMORY_LIMIT=-1 \
    COMPOSER_NO_INTERACTION=1