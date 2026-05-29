FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN cd community && composer install --no-dev --optimize-autoloader

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000"]
