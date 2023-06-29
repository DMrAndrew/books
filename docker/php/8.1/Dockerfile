FROM php:8.1-fpm

ARG WWWGROUP
ARG XDEBUG

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && apt-get install -y libcurl4-openssl-dev gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev libpq-dev libonig-dev libzip-dev libicu-dev libxml2-dev libjpeg-dev libfreetype6-dev jpegoptim optipng pngquant gifsicle vim libmagickwand-dev \
    && mkdir -p ~/.gnupg \
    && chmod 600 ~/.gnupg \
    && echo "disable-ipv6" >> ~/.gnupg/dirmngr.conf \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -sL https://deb.nodesource.com/setup_16.x | bash - \
    && apt-get install -y nodejs yarn mariadb-client \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install -o -f redis \
    && pecl install imagick \
    && rm -rf /tmp/pear \
    && docker-php-ext-configure gd --with-jpeg  \
    && docker-php-ext-install mysqli pdo pdo_mysql curl mbstring zip intl gd xml exif opcache \
    && docker-php-ext-enable pdo pdo_mysql curl mbstring zip intl gd xml exif redis imagick

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install \
    pcntl

RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

RUN if [ "$XDEBUG" = "true" ] ; then \
    pecl install xdebug  \
    && docker-php-ext-enable xdebug \
    && echo "[xdebug]" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=false" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "php_admin_value[xdebug.start_with_request]=\"yes\";" >>  /usr/local/etc/php-fpm.d/www.conf; \
    fi

COPY start-container /usr/local/bin/start-container
COPY php.ini /usr/local/etc/php/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container
RUN git config --global http.sslverify false

STOPSIGNAL SIGQUIT

EXPOSE 8000
ENTRYPOINT ["start-container"]
