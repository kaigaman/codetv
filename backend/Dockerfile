FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev zip unzip git curl \
    libxml2-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl gd bcmath xml

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-dev \
    && php artisan key:generate \
    && php artisan storage:link \
    && mkdir -p storage/app/public/thumbnails \
    && chown -R www-data:www-data storage bootstrap/cache public/m3u public/storage

EXPOSE 80

CMD php artisan serve --host=0.0.0.0 --port=80
