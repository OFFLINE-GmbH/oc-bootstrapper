FROM composer:latest

RUN apk add --no-cache \
                curl \
                curl-dev \
        libcurl \
        libxml2-dev     \
    && rm -rf /var/cache/apk/*


RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install curl
RUN docker-php-ext-install xml
RUN docker-php-ext-install zip
RUN docker-php-ext-install posix

RUN mkdir /tmp/bootstrapper /build

RUN composer global require --prefer-dist laravel/envoy --no-interaction

ADD . /tmp/bootstrapper

WORKDIR /tmp/bootstrapper
RUN composer install --no-interaction --prefer-dist

RUN ln -s /tmp/bootstrapper/october /usr/bin/october
RUN ln -s /tmp/vendor/bin/envoy /usr/bin/envoy

WORKDIR /build

ENTRYPOINT []
CMD ["/tmp/bootstrapper/october"]


