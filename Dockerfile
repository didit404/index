FROM php:8.2-apache

# Install ekstensi mysqli untuk koneksi database
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Aktifkan mod_rewrite Apache
RUN a2enmod rewrite

# Copy seluruh file project ke direktori web Apache
COPY . /var/www/html/

# Berikan hak akses folder uploads
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
CMD ["apache2-foreground"]
