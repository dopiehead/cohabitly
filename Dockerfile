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

# Generate JWT keys if not present (Railway won't have them from .gitignore)
RUN mkdir -p config/jwt && \
    if [ ! -f config/jwt/private.pem ]; then \
        openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096 2>/dev/null; \
        openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem 2>/dev/null; \
    fi

# Ensure var/ is writable
RUN mkdir -p var/cache var/log && chmod -R 777 var/

RUN php bin/console cache:warmup --env=prod --no-debug --no-interaction 2>/dev/null || true

EXPOSE 8000

CMD ["sh", "start.sh"]
