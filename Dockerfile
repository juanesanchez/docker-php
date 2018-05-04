FROM php:7.0-apache

# let Upstart know it's in a container
ENV container docker

RUN docker-php-ext-install mysql; \
	docker-php-ext-configure mysql; \
	docker-php-ext-install mysqli; \
	docker-php-ext-configure mysqli;


COPY . /var/www/html/
COPY startup.sh /var/www/html/startup.sh

EXPOSE 3000

RUN echo 'Listo'

