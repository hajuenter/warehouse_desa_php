FROM php:8.3.8-cli

# Install supervisor and PHP extensions
RUN apt-get update && apt-get install -y \
    supervisor \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Copy supervisor config
RUN mkdir -p /etc/supervisor/conf.d
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copy application files
COPY . .

# Copy entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Ensure data directory is writable for admin.json and reset_tokens.json
RUN mkdir -p data && chmod -R 777 data

EXPOSE 3000

ENTRYPOINT ["/entrypoint.sh"]
