FROM php:7.3-apache

ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli.so
RUN a2enmod rewrite

RUN apt-get update && \
    apt-get install -y \
        zlib1g-dev

RUN apt-get update
RUN apt-get install -y git

RUN install-php-extensions opcache

COPY apache2.conf /etc/apache2/apache2.conf
COPY php-extra.ini /usr/local/etc/php/conf.d
COPY composer.json /var/www

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN cd /var/www && composer update --no-dev

EXPOSE 81
