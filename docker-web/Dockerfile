FROM php:7.3-apache

RUN a2enmod rewrite

ADD https://raw.githubusercontent.com/mlocati/docker-php-extension-installer/master/install-php-extensions /usr/local/bin/

RUN chmod uga+x /usr/local/bin/install-php-extensions && sync
RUN install-php-extensions memcached
RUN install-php-extensions redis
RUN install-php-extensions opcache
RUN install-php-extensions mysqli

RUN apt-get update
RUN apt-get install -y git
RUN apt install zip unzip

RUN apt-get install -y memcached
# telnet is surprisingly useful for poking around inside memcached
RUN apt-get install -y telnet

COPY memcached.conf /etc/memcached.conf
COPY apache2.conf /etc/apache2/apache2.conf
COPY php-extra.ini /usr/local/etc/php/conf.d
COPY composer.json /var/www/

COPY PRHLCAcert.crt /usr/local/share/ca-certificates
RUN update-ca-certificates

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN cd /var/www/ && composer update --no-dev

CMD service memcached start && /bin/bash

EXPOSE 80
