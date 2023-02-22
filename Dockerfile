FROM php:8.2-alpine AS app
RUN apk add bash acl yq make git supervisor
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash && \
    apk add symfony-cli
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql openswoole intl
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV PATH $PATH:/root/.composer/vendor/bin
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
WORKDIR app
CMD ["supervisord", "-n"]

## Nginx with Swoole config
FROM nginx as nginx-swoole
COPY docker/nginx/conf.d/nginx-swoole.conf /etc/nginx/conf.d/default.conf
WORKDIR /app/public
CMD ["nginx", "-g", "daemon off;"]

## K6 benchmarking
FROM loadimpact/k6 AS k6
WORKDIR /app
