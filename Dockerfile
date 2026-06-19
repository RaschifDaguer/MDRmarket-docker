FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    dos2unix \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

# Fix Windows line endings on all PHP/config files
RUN find /var/www -type f \( -name "*.php" -o -name "*.env*" -o -name "*.json" -o -name "*.xml" -o -name "*.yaml" -o -name "*.yml" -o -name "*.conf" -o -name "*.sh" \) -exec dos2unix {} \; 2>/dev/null || true

RUN composer dump-autoload --optimize

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

RUN mkdir -p /var/log/supervisor

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN dos2unix /usr/local/bin/start.sh /etc/nginx/http.d/default.conf /etc/supervisor/conf.d/supervisord.conf \
    && chmod +x /usr/local/bin/start.sh

EXPOSE ${PORT:-80}

CMD ["/usr/local/bin/start.sh"]
