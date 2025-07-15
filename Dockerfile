# Usa una imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instala extensiones necesarias para Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia el código de la app al contenedor
COPY . /var/www/html

# Da permisos a las carpetas necesarias
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Habilita mod_rewrite de Apache (necesario para Laravel)
RUN a2enmod rewrite

# Expone el puerto 80
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]
