FROM phpdockerio/php:8.4-fpm
WORKDIR "/application"

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        git \
        php8.4-mysql \
        php8.4-pgsql \
        php8.4-uploadprogress \
        php8.4-yaml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
