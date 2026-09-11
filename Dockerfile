FROM php:8.4-fpm

WORKDIR /var/www/html

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    ca-certificates \
    dnsutils \
    iputils-ping \
    build-essential \
    autoconf \
    pkg-config \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install phpredis from source
RUN git clone --depth 1 https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis

# PHP extensions required for Laravel, Horizon & Concurrency
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    pcntl \
    bcmath \
    intl

# Roadrunner binary (Mandatory Technical Requirement: Section 3)
COPY --from=spiralscout/roadrunner:2024.1 /usr/bin/rr /usr/bin/rr

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

EXPOSE 8000

CMD ["php-fpm"]