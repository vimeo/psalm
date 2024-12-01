ARG VERSION
FROM php:${VERSION}

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pcntl uv-beta ffi pgsql intl gmp mbstring pdo_mysql xml dom iconv zip igbinary gd && \
    rm /usr/local/bin/install-php-extensions

ADD php.ini /usr/local/etc/php/php.ini