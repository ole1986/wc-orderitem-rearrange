FROM wordpress:6.7-apache

RUN ln -sf /bin/bash /bin/sh

RUN apt update
RUN apt install -y nano msmtp-mta msmtp-mta nodejs npm

# install xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp

COPY wp-install.sh /wp-install.sh
RUN <<EOF cat >> /start.sh
/usr/local/bin/apache2-foreground &
sleep 5
/wp-install.sh
tail -f /dev/null
EOF

RUN chmod 755 /start.sh && chmod 755 /wp-install.sh
RUN chown -R www-data /var/www

USER www-data

CMD ["/start.sh"]