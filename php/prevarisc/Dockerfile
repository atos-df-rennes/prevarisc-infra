FROM php:7.1-apache

ARG user
ARG uid

# Création de l'utilisateur "prv"
RUN useradd -G www-data -u "$uid" -d /home/"$user" "$user" \
    && usermod -aG prv www-data \
    && mkdir -p /home/"$user"/.composer \
    # Création des dossiers spécifiques à l'application et paramétrage des droits/permissions
    && mkdir -p /home/"$user"/cache \
    && chmod 775 /home/"$user"/cache \
    && mkdir -p /home/"$user"/logs \
    && chown -R "$user":"$user" /home/"$user" \
    && chown -R "$user":"$user" /var/www/html

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN a2enmod authz_core deflate dir ssl headers expires include rewrite \
    && sed -i '/deb http:\/\/deb.debian.org\/debian buster main/d' /etc/apt/sources.list \
    && sed -i 's/^# deb http:\/\/snapshot.debian.org\/archive\/debian\/20191118T000000Z buster main/deb http:\/\/snapshot.debian.org\/archive\/debian\/20191118T000000Z buster main/' /etc/apt/sources.list \
    && sed -i '/deb http:\/\/security.debian.org\/debian-security buster\/updates main/d' /etc/apt/sources.list \
    && sed -i '/deb http:\/\/deb.debian.org\/debian buster-updates main/d' /etc/apt/sources.list \
    && apt update \
    && apt install -y \
        zip \
    && apt clean \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        opcache \
        pdo_mysql \
        mysqli \
        zip \
        gd \
        ldap \
        exif \
        intl \
        xdebug

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer
# Xdebug conf
COPY php/prevarisc/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

USER "$user"