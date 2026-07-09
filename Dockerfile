# syntax=docker/dockerfile:1

FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build


FROM dunglas/frankenphp:1-php8.4-alpine AS vendor

WORKDIR /app

RUN apk add --no-cache git icu-dev libzip-dev postgresql-dev sqlite-dev unzip $PHPIZE_DEPS \
    && docker-php-ext-install bcmath intl pdo_pgsql pdo_sqlite zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --no-dev --optimize


FROM dunglas/frankenphp:1-php8.4-alpine AS app

WORKDIR /app

RUN apk add --no-cache curl icu-dev libzip-dev postgresql-dev sqlite-dev $PHPIZE_DEPS \
    && docker-php-ext-install bcmath intl opcache pdo_pgsql pdo_sqlite zip

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/frankenphp/Caddyfile /etc/caddy/Caddyfile
COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/entrypoint.sh /usr/local/bin/kadi-entrypoint

RUN chmod +x /usr/local/bin/kadi-entrypoint \
    && mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["kadi-entrypoint"]
CMD ["app"]
