FROM composer:latest

RUN apk add --no-cache \
    curl \
    curl-dev \
    libcurl \
    libssl1.1 \
    libxml2-dev \
    libzip-dev \
    && rm -rf /var/cache/apk/*

RUN docker-php-ext-install pdo \
    pdo_mysql \
    curl \
    xml \
    zip \
    posix

RUN mkdir /composer

WORKDIR /composer

RUN composer require --prefer-dist laravel/envoy offline/oc-bootstrapper --no-interaction

RUN ln -s /composer/vendor/bin/october /usr/bin/october
RUN ln -s /composer/vendor/bin/envoy /usr/bin/envoy

ENTRYPOINT []
CMD ["/composer/vendor/bin/october"]
