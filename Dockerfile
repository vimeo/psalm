FROM composer as composer
WORKDIR /app
COPY ./ /app
RUN composer install --no-dev --no-progress --optimize-autoloader --no-interaction

FROM php:8.1-cli-alpine
WORKDIR /app
COPY --from=composer /app/ /psalm/
COPY ./entrypoint /usr/bin/entrypoint
ENTRYPOINT ["/usr/bin/entrypoint"]