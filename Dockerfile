FROM library/php:latest

ENV GWENT_ID 123
ENV BITLY_LOGIN "example"
ENV BITLY_TOKEN "example"
ENV MYSQL_DB_HOST "localhost"
ENV MYSQL_DB_USER "root"
ENV MYSQL_DB_PASS "gwent_stats"
ENV MYSQL_DB_NAME "gwent_stats"
ENV APP_ENV "dev"
ENV PORT 8080

WORKDIR /var
RUN mkdir /var/www/ && mkdir /var/www/gwent
COPY bin /var/www/gwent/bin
COPY config /var/www/gwent/config
COPY public /var/www/gwent/public
COPY src /var/www/gwent/src
COPY templates /var/www/gwent/templates
COPY tests /var/www/gwent/tests
COPY translations /var/www/gwent/translations
RUN mkdir /var/www/gwent/var && mkdir /var/www/gwent/var/cache && mkdir /var/www/gwent/var/log && mkdir /var/www/gwent/var/sessions
COPY composer.json /var/www/gwent/composer.json
COPY composer.lock /var/www/gwent/composer.lock
COPY symfony.lock /var/www/gwent/symfony.lock
COPY phpunit.xml.dist /var/www/gwent/phpunit.xml.dist
COPY run.sh /run.sh
RUN chmod 777 /run.sh && chmod 777 /var/www/gwent/var/cache && chmod 777 /var/www/gwent/var/log && chmod 777 /var/www/gwent/var/sessions

WORKDIR /var/www/gwent
RUN apt-get update && apt-get install -y wget git zip unzip && docker-php-ext-install pdo pdo_mysql && wget https://getcomposer.org/composer.phar && cp composer.phar /usr/bin/composer && chmod 777 /usr/bin/composer && composer update && useradd -s /bin/bash -u 1000 docker_user && chown -R docker_user:docker_user /var/www/gwent/
USER docker_user
CMD /run.sh

