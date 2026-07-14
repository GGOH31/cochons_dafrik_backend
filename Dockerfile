FROM php:8.4-fpm

# Installer les dépendances et extensions nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql pdo_pgsql pgsql zip

WORKDIR /var/www/html

# Configurer git safe directory pour éviter les erreurs de dubious ownership
RUN git config --global --add safe.directory /var/www/html

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le projet
COPY . .

RUN apt-get update && apt-get install -y \
    libicu-dev \
    && docker-php-ext-install intl


# Installer les dépendances Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
