# syntax=docker/dockerfile:1
#
# Multi-stage build. Build context is the REPO ROOT (so we can copy frontend/):
#   docker build -f backend-php/Dockerfile --target prod -t student-api-php .
#
#   vendor  -> installs production Composer dependencies (thrown away after)
#   base    -> PHP + Apache configured for this app
#   dev     -> base + Composer, used by docker-compose for local development
#   prod    -> base + code + vendor, the image CI builds, tests and deploys

########## Stage 1: production dependencies ##########
FROM composer:2 AS vendor
WORKDIR /app
COPY backend-php/composer.json backend-php/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-autoloader --no-scripts
COPY backend-php/src ./src
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

########## Stage 2: shared runtime ##########
FROM php:8.3-apache AS base

# Render (and most PaaS) tell the container which port to listen on via $PORT.
ENV PORT=8080 \
    APP_ENV=production

RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf \
 && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername \
 && a2enmod headers \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'expose_php=Off\ndisplay_errors=Off\nlog_errors=On\n' > "$PHP_INI_DIR/conf.d/app.ini"

COPY backend-php/docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/app
EXPOSE 8080

########## Stage 3: local development ##########
FROM base AS dev
ENV APP_ENV=local
RUN apt-get update \
 && apt-get install -y --no-install-recommends unzip git \
 && rm -rf /var/lib/apt/lists/* \
 && mv "$PHP_INI_DIR/php.ini" "$PHP_INI_DIR/php.ini-production" \
 && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
# Source code is bind-mounted by docker-compose, so nothing else is copied here.

########## Stage 4: production ##########
FROM base AS prod

# Injected by the pipeline (git commit SHA). Surfaced at GET /api/health so
# we can verify exactly which commit is running in production.
ARG APP_VERSION=unknown
ENV APP_VERSION=${APP_VERSION}

COPY --from=vendor /app/vendor ./vendor
COPY backend-php/src ./src
COPY backend-php/public ./public
COPY frontend /var/www/frontend

# Never run as root in production.
USER www-data

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD curl -fsS "http://localhost:${PORT}/api/health" || exit 1
