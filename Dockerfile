# Stage 1: install dependencies
FROM php:8.4-cli-alpine AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

COPY . .
RUN composer dump-autoload --optimize

# Stage 2: runtime
FROM php:8.4-cli-alpine

LABEL org.opencontainers.image.source="https://github.com/Nextfuture-IT/EVOrdersConnector"

# Nessuna estensione DB extra: il connettore non si collega a MySQL/MariaDB.
# Solo sqlite per la tabella di audit (pdo_sqlite incluso nell'immagine base).

WORKDIR /app

COPY --from=build /app .

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
