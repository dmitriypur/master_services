FROM php:8.3-fpm
RUN apt-get update && apt-get install -y git unzip libpq-dev libicu-dev libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev libonig-dev pkg-config && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp && docker-php-ext-install -j$(nproc) pdo_pgsql pgsql mbstring intl pcntl gd && pecl install redis && docker-php-ext-enable redis && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www