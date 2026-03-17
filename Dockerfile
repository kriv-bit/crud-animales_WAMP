FROM php:8.2-apache

# Activar mod_rewrite (muchos proyectos lo usan)
RUN a2enmod rewrite

# Instalar soporte para MySQL (aunque aún no lo uses)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copiar tu proyecto
COPY . /var/www/html/