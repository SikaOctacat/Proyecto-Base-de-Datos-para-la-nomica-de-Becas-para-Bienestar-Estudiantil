# 1. Usamos una imagen oficial de PHP con Apache
FROM php:8.2-apache

# 2. Instalamos las extensiones necesarias para MySQL/TiDB
# Esto es vital para que PDO funcione en el servidor
RUN docker-php-ext-install pdo pdo_mysql

# 3. Copiamos todos los archivos de tu carpeta actual al servidor de Apache
COPY . /var/www/html/

# 4. Le damos permisos a Apache para leer tus archivos
RUN chown -R www-data:www-data /var/www/html/

# 5. Exponemos el puerto 80 (el estándar web)
EXPOSE 80