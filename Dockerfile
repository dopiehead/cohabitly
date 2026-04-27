FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mysqli \
        intl \
        mbstring \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Wipe cache so it rebuilds fresh at runtime with correct env vars
RUN rm -rf var/cache/prod var/cache/dev

RUN mkdir -p var/cache var/log && chmod -R 777 var/

EXPOSE 8000

CMD ["sh", "start.sh"]
