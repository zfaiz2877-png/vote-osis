# Gunakan PHP 8.1 dengan Apache
FROM php:8.1-apache

# WAJIB: Install ekstensi untuk koneksi database (PDO & MySQLi)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Aktifkan mod_rewrite untuk URL yang rapi
RUN a2enmod rewrite

# Copy semua file project ke folder default Apache
COPY . /var/www/html/

# Berikan izin pada folder
RUN chown -R www-data:www-data /var/www/html