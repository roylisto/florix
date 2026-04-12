FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    netcat-openbsd \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    sqlite3 \
    libsqlite3-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mbstring bcmath gd zip pdo_sqlite pcntl exif

# Use custom PHP configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Optimize: Copy only composer files first to leverage Docker layer caching for dependencies
COPY composer.json composer.lock ./

# Install dependencies before copying the rest of the source code
RUN composer install --no-interaction --no-scripts --no-autoloader --no-dev

# Copy existing application directory contents
COPY . /var/www

# Finish composer installation (scripts and autoloader)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Setup entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 9000 and start php-fpm server
EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
