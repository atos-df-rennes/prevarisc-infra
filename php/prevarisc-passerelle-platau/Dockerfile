FROM php:8.3-fpm

ARG user
ARG uid

# Création de l'utilisateur "prv"
RUN useradd -u "$uid" -d /home/"$user" "$user" \
    && mkdir -p /home/"$user"/.composer \
    && chown -R "$user":"$user" /home/"$user" \
    && chown -R "$user":"$user" /var/www/html

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apt update \
    && apt install -y \
        zip \
    && apt clean \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        opcache \
        pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer