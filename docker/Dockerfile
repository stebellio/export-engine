FROM php:7.3-cli

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        default-mysql-client \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath zip intl pcntl gd

RUN pecl install redis-5.3.7 \
    && docker-php-ext-enable redis

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN groupadd -o -g ${GID} app \
    && useradd -o -u ${UID} -g ${GID} -m -s /bin/bash app \
    && mkdir -p /var/www/html \
    && chown -R ${UID}:${GID} /var/www/html

WORKDIR /var/www/html

USER app

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
