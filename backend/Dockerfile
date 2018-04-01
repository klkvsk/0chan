FROM ubuntu:16.04

RUN apt-get update && apt-get install -y wget git php-fpm php-memcache php-pgsql php-redis php-gd php-mbstring php-dom php-imagick php-curl php-apcu

RUN mkdir -p /var/www/logs

WORKDIR /src

ADD ./composer.json composer.json
ADD ./config/get-composer.sh config/get-composer.sh
RUN sh config/get-composer.sh
RUN php composer.phar install --no-autoloader
COPY . ./
RUN php composer.phar install --optimize-autoloader --apcu-autoloader

RUN mkdir /images
RUN chmod 777 /images

CMD [ "php-fpm7.0", "-O", "-F", "--fpm-config", "/src/config/php-fpm.conf" ]

