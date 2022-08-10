FROM php:7.4.25-apache
COPY --from=composer:2.1 /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libxml2-dev \
    && apt-get clean -y
RUN docker-php-ext-install mysqli zip gd intl xmlrpc soap
RUN mkdir -p /var/www/html/moodle 
COPY ./ /var/www/html/moodle
WORKDIR /var/www/html/moodle
RUN chown www-data:www-data -R /var/www/html/moodle
RUN mkdir -p /var/moodledata && chown www-data:www-data -R /var/moodledata && chmod -R 777 /var/www
RUN composer install --no-scripts
USER www-data
