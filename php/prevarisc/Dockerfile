FROM php:8.1-apache

ARG user
ARG uid

# Création de l'utilisateur "prv", des dossiers spécifiques à l'application et paramétrage des droits/permissions
RUN useradd -G www-data -u "$uid" -d /home/"$user" "$user" \
    && usermod -aG prv "$user" \
    && usermod -aG prv www-data \
    && mkdir -p /home/"$user"/.composer \
    && mkdir -p /home/"$user"/cache \
    && chmod 775 /home/"$user"/cache \
    && mkdir -p /home/"$user"/logs \
    && chown -R "$user":"$user" /home/"$user" \
    && chown -R "$user":"$user" /var/www/html

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN a2enmod authz_core deflate dir ssl headers expires include rewrite \
    && apt-get update \
    && apt-get install -y \
        zip \
        git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        opcache \
        pdo_mysql \
        mysqli \
        zip \
        gd \
        ldap \
        exif \
        xdebug \
        intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY php/prevarisc/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

USER "$user"