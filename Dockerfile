FROM php:8.2-apache

# Install PostgreSQL dependencies
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache rewrite (optional)
RUN a2enmod rewrite

# Copy project files (optional if using volumes)
# COPY ./src /var/www/html
COPY .  /var/www/html/

EXPOSE 80
