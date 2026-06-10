FROM php:8.2-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libonig-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP configuration
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Entrypoint
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
