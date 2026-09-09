FROM php:8.3.8-cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN mkdir -p data && chmod 777 data

EXPOSE 3000

CMD ["php", "-S", "0.0.0.0:3000", "-t", "public/"]
