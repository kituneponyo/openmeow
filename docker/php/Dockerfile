# イメージを指定
FROM php:7.4.33-apache

COPY --from=composer /usr/bin/composer /usr/bin/composer

# MySQLに接続するためのパッケージをインストールする
RUN apt update \
  && apt install -y git vim systemctl zip unzip \
  && docker-php-ext-install mysqli \
  && a2enmod rewrite \
  && systemctl restart apache2

COPY ./conf "$PHP_INI_DIR/conf.d"
