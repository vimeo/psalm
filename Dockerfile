FROM composer as composer
WORKDIR /app
COPY ./ /app
RUN composer install --no-dev --no-progress --optimize-autoloader --quiet --no-interaction

#FROM php:7.4-cli as phpare-builder
#COPY --from=git /usr/bin/git /usr/bin/git
#RUN apt-get update \
#  && apt-get install -y \
#    git \
#    libzip-dev \
#    zip
#RUN docker-php-source extract \
#    && docker-php-ext-install zip \
#    && docker-php-source delete
#COPY --from=composer /usr/bin/composer /usr/bin/composer
#COPY --from=composer /app /app
#WORKDIR /app
#RUN composer install
#RUN bash /app/bin/build-phar.sh

FROM php:8.1-cli-alpine
WORKDIR /app
COPY --from=composer /app/ /psalm/
COPY ./entrypoint /usr/bin/entrypoint
ENTRYPOINT ["/usr/bin/entrypoint"]