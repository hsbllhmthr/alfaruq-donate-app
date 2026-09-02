FROM php:8.2-apache

# Install pdo_mysql dan pdo_sqlite extension
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html \
    && chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
