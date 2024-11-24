FROM php:8.2-apache

RUN apt update
RUN apt install -y default-mysql-client --no-install-recommends
RUN apt install -y libicu-dev less sudo nano msmtp-mta less sudo nano msmtp-mta 

RUN docker-php-ext-install pdo pdo_mysql mysqli 
RUN docker-php-ext-configure intl \
    && docker-php-ext-install intl

RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# install additonal apt packages
RUN apt install -y nodejs npm

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp

RUN <<EOF cat >> /start.sh
/usr/local/bin/apache2-foreground &
sleep 5
/wp-install.sh
tail -f /dev/null
EOF

RUN chmod 755 /start.sh
RUN chown -R www-data /var/www

USER www-data
RUN wp core download && \
    wp config create --skip-check --dbhost=db --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --locale=de_DE

# make sure copy the wp-install script at the end
USER root
COPY ./wp-install.sh /wp-install.sh
RUN chmod 755 /wp-install.sh

USER www-data
CMD ["/start.sh"]