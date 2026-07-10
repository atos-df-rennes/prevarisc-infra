FROM php:7.3-apache

ARG user
ARG uid

# Création de l'utilisateur "prv"
RUN useradd -G www-data -u "$uid" -d /home/"$user" "$user" \
    && usermod -aG prv "$user" \
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
    && sed -i '/security.debian.org/d' /etc/apt/sources.list \
    && sed -i 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' /etc/apt/sources.list \
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
        xdebug

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer
# Xdebug conf
COPY php/prevarisc/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

USER "$user"