FROM debian:bookworm-slim as base

ENV PHP_CONF_DATE_TIMEZONE=UTC \
    PHP_CONF_MAX_EXECUTION_TIME=60 \
    PHP_CONF_MEMORY_LIMIT=512M \
    PHP_CONF_OPCACHE_VALIDATE_TIMESTAMP=1 \
    PHP_CONF_MAX_INPUT_VARS=1000 \
    PHP_CONF_UPLOAD_LIMIT=100M \
    PHP_CONF_MAX_POST_SIZE=200M

RUN echo 'APT::Install-Recommends "0" ; APT::Install-Suggests "0" ;' > /etc/apt/apt.conf.d/01-no-recommended && \
    echo 'path-exclude=/usr/share/man/*' > /etc/dpkg/dpkg.cfg.d/path_exclusions && \
    echo 'path-exclude=/usr/share/doc/*' >> /etc/dpkg/dpkg.cfg.d/path_exclusions && \
    apt-get update && \
    apt-get --yes install apt-transport-https ca-certificates curl wget &&\
    wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg &&\
    sh -c 'echo "deb https://packages.sury.org/php/ bookworm main" > /etc/apt/sources.list.d/php.list' &&\
    apt-get update && \
    apt-get --yes install imagemagick \
        libmagickcore-6.q16-6-extra \
        ghostscript \
        php8.1-fpm \
        php8.1-cli \
        php8.1-intl \
        php8.1-opcache \
        php8.1-mysql \
        php8.1-zip \
        php8.1-xml \
        php8.1-gd \
        php8.1-grpc \
        php8.1-curl \
        php8.1-mbstring \
        php8.1-bcmath \
        php8.1-imagick \
        php8.1-apcu \
        php8.1-exif \
        php8.1-memcached \
        openssh-client \
        git \
        unzip \
        aspell \
        aspell-en aspell-es aspell-de aspell-fr && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    ln -s /usr/sbin/php-fpm8.1 /usr/local/sbin/php-fpm && \
    usermod --uid 1000 www-data && groupmod --gid 1000 www-data && \
    mkdir /srv/pim && \
    sed -i "s#listen = /run/php/php8.1-fpm.sock#listen = 9000#g" /etc/php/8.1/fpm/pool.d/www.conf && \
    mkdir -p /run/php

COPY ./build/akeneo.ini /etc/php/8.1/cli/conf.d/99-akeneo.ini
COPY ./build/akeneo.ini /etc/php/8.1/fpm/conf.d/99-akeneo.ini

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

RUN mkdir -p /var/www/.composer /var/www/.cache && chown www-data:www-data /var/www/.composer /var/www/.cache

## There once was a bunch of extra stuff here to support the akeneo development
## environment, but we don't need it cause we mostly *run* akeneo.

VOLUME /srv/pim
