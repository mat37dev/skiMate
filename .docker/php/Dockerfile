FROM php:8.2-fpm-alpine3.18

# Mettre à jour les dépôts et installer les dépendances nécessaires
RUN apk update \
    && apk add \
    bash \
    vim \
    zip \
    unzip \
    libzip-dev \
    curl \
    git \
    nodejs \
    npm \
    yarn \
    mysql-client \
    icu-dev \
    gcc \
    g++ \
    make \
    autoconf \
    openssl-dev \
    && apk add libpq-dev && docker-php-ext-install pdo_mysql \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl


RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


COPY .docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]


