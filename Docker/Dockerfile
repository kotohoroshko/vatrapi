FROM php:8.4-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        bcmath \
        exif \
        pcntl \
        zip \
        opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Build & install the Swiss Ephemeris PHP extension (swephp) — AGPL-3.0.
# Must run as root and before the "USER app" switch below.
ARG SWEPH_EPHE_PATH=/usr/local/share/sweph/ephe
COPY Docker/sweph /usr/src/php-sweph
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends $PHPIZE_DEPS; \
    cd /usr/src/php-sweph; \
    phpize; \
    ./configure; \
    make; \
    make install; \
    echo "extension=swephp.so" > /usr/local/etc/php/conf.d/97-swephp.ini; \
    mkdir -p "${SWEPH_EPHE_PATH}"; \
    cp ephe/* "${SWEPH_EPHE_PATH}/"; \
    php -m | grep -q swephp; \
    phpize --clean; \
    rm -f sweph/src/libswe.a sweph/src/*.o swephp.dep; \
    rm -rf include; \
    apt-get purge -y --auto-remove $PHPIZE_DEPS; \
    rm -rf /var/lib/apt/lists/* /usr/src/php-sweph

RUN groupadd -g "${GID}" app \
    && useradd -u "${UID}" -g app -m -s /bin/bash app

WORKDIR /var/www/html

COPY Docker/php/php.ini /usr/local/etc/php/conf.d/99-laravel.ini
COPY Docker/php/opcache.ini /usr/local/etc/php/conf.d/98-opcache.ini

USER app
