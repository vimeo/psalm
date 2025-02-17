FROM php:8.4-cli

# This line invalidates cache when master branch changes
ADD https://github.com/vimeo/psalm/commits/master.atom /dev/null

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN sed 's/-O2/-O3/g' -i /usr/local/bin/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions pcntl mbstring xml dom igbinary opcache && \
    rm /usr/local/bin/install-php-extensions

RUN apt-get update && apt-get -y --no-install-recommends install git unzip && apt-get clean && rm -rf /var/lib/apt/lists/*

ADD bin/docker/php.ini /usr/local/lib/php.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG PSALM_REV=dev-master
RUN COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME="/composer" \
    composer global require vimeo/psalm:${PSALM_REV} --prefer-dist --no-progress --dev && \
    rm /usr/bin/composer /usr/local/bin/phpdbg /usr/local/bin/php-cgi /usr/local/lib/libphp.so

ENV PATH /composer/vendor/bin:${PATH}

# Add entrypoint script

COPY ./bin/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Squash into single layer
FROM scratch
COPY --from=0 / /

WORKDIR "/app"
#ENTRYPOINT ["/entrypoint.sh"]