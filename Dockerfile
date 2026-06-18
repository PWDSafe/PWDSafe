FROM composer:2.9 AS vendor
COPY database/ database/
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-progress \
    --prefer-dist

FROM node:24-slim AS frontend
WORKDIR /app/
COPY public /app/public/
COPY app/ /app/app/
COPY resources/ /app/resources/
COPY package.json package-lock.json vite.config.js /app/
RUN npm ci --no-audit --no-fund --prefer-offline --loglevel=error && npm run build

FROM php:8.5-apache-trixie
ENV DEBIAN_FRONTEND=noninteractive
WORKDIR /var/www/html

RUN sed -i /etc/apache2/sites-enabled/000-default.conf -e 's,DocumentRoot /var/www/html, DocumentRoot /var/www/html/public,g' -e 's,:80,:8080,g'
RUN sed -i /etc/apache2/ports.conf -e 's,Listen 80,Listen 8080,g'
RUN sed -i /etc/apache2/sites-enabled/000-default.conf -e 's,^\t#LogLevel.*,\tSetEnvIf Request_URI "^/health$" dontlog,g'
RUN sed -i /etc/apache2/sites-enabled/000-default.conf -e 's,^\tCustomLog.*,\tCustomLog ${APACHE_LOG_DIR}/access.log combined env=!dontlog,g'
ENV APACHE_HTTP_PORT=8080
EXPOSE 8080
RUN apt-get update -qq && apt-get install -y -qq \
      libldap2-dev libkrb5-dev nmap inetutils-ping net-tools \
      libxml2-dev libxslt1-dev libcurl4-openssl-dev zip unzip git \
      libfreetype6-dev libjpeg62-turbo-dev libpng-dev libpq-dev \
    && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mysqli pdo_pgsql gettext xsl ldap gd
RUN echo "uploads_max_filesize = 8M\npost_max_size = 8M" > /usr/local/etc/php/conf.d/uploads.ini

COPY . /var/www/html
COPY --from=vendor /app/vendor/ /var/www/html/vendor/
COPY --from=frontend /app/public/build/ /var/www/html/public/build/
RUN chmod 777 -R storage/
ENTRYPOINT ["/var/www/html/scripts/entrypoint.sh"]
