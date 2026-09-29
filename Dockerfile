# syntax=docker/dockerfile:1
# Shared Reading Lists (bake-off candidate 2): Laravel 13 on PHP 8.5, Inertia + React built with Node 24.
#   docker build --target check .   -> lint, type-check and tests (run with DATABASE_URL pointing at an empty PostgreSQL DB)
#   docker build .                   -> production-mode preview image (last stage)

FROM node:24-trixie-slim AS node

# ---------------------------------------------------------------------------------------------------------------------------
FROM php:8.5-cli-trixie AS base
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip bcmath \
    && rm -rf /var/lib/apt/lists/* \
    && useradd --uid 1000 --create-home app \
    && mkdir /app && chown app:app /app
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
ENV COMPOSER_HOME=/tmp/composer \
    APP_NAME="Shared Reading Lists" \
    LOG_CHANNEL=stderr
WORKDIR /app
USER app

# ---------------------------------------------------------------------------------------------------------------------------
# Full development toolchain: PHP dev dependencies + Node (the Vite build runs `php artisan wayfinder:generate`).
FROM base AS dev
USER root
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules/npm /usr/local/lib/node_modules/npm
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
USER app
COPY --chown=app:app composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader
COPY --chown=app:app package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
COPY --chown=app:app . .
ENV VITE_APP_NAME="Shared Reading Lists"
RUN composer dump-autoload --optimize --no-interaction && npm run build

# ---------------------------------------------------------------------------------------------------------------------------
# Candidate checks (gate: `check` stage). Needs DATABASE_URL (empty PostgreSQL DB); no network access at run time.
FROM dev AS check
ENV APP_ENV=test
CMD ["composer", "ci:check"]

# ---------------------------------------------------------------------------------------------------------------------------
FROM base AS vendor
COPY --chown=app:app composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-dev --no-scripts --no-autoloader

# ---------------------------------------------------------------------------------------------------------------------------
# Production-mode preview image (default stage). Runs as uid 1000 with no capabilities; listens on 0.0.0.0:$PORT.
FROM base AS preview
ARG APP_RELEASE=""
ARG PUBLIC_SENTRY_DSN=""
ENV APP_RELEASE=${APP_RELEASE} \
    PUBLIC_SENTRY_DSN=${PUBLIC_SENTRY_DSN} \
    APP_DEBUG=false \
    PORT=8080 \
    PHP_CLI_SERVER_WORKERS=8
COPY --chown=app:app --from=vendor /app/vendor ./vendor
COPY --chown=app:app . .
COPY --chown=app:app --from=dev /app/public/build ./public/build
RUN rm -rf tests node_modules \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && chmod +x docker/start.sh
EXPOSE 8080
CMD ["docker/start.sh"]
