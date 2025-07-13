FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath sockets

# Add custom PHP configuration
COPY php-custom.ini /usr/local/etc/php/conf.d/php-custom.ini

# Configure Git for container use
RUN git config --global --add safe.directory /var/www/html

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Start PHP-FPM server
CMD ["bash", "-c", "composer update && php-fpm"]