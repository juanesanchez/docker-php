FROM php:7.0-apache

# let Upstart know it's in a container
ENV container docker

RUN docker-php-ext-install mysql; \
	docker-php-ext-configure mysql; \
	docker-php-ext-install mysqli; \
	docker-php-ext-configure mysqli;


RUN apt-get update && apt-get install -y libmemcached-dev zlib1g-dev \
	&& pecl install memcached-3.0.4 \
	&& docker-php-ext-enable memcached



COPY . /var/www/html/

EXPOSE 3000

RUN echo 'Listo'

