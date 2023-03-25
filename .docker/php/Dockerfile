FROM php:8.0-apache

ARG PACKAGES="bash vim nano mariadb-client git libxml2-dev libzip-dev libgmp10-dev iputils-ping"
RUN apt-get update && apt-get install libzip-dev zip $PHPIZE_DEPS $PACKAGES -y

RUN docker-php-ext-install zip

ARG EXTENSIONS="pdo_mysql intl soap zip pcntl mysqli bcmath gmp"
RUN docker-php-ext-install -j$(nproc) \
$EXTENSIONS \
&& docker-php-ext-enable $EXTENSIONS

ARG PECL="xdebug redis"
RUN pecl install $PECL && docker-php-ext-enable $PECL

#Disable SSH Host Checking (Solves the container erroring out during composer install)
#The sources for the composer should be ok anyways (You'd have much worse problems regardless)
RUN mkdir -p /root/.ssh/ && \
echo "Host * \nStrictHostKeyChecking no \nUserKnownHostsFile=/dev/null " > /root/.ssh/config

ARG COMPOSER_URL
RUN curl $COMPOSER_URL --output ./composer && \
mv composer /usr/local/bin/ && \
chmod 0777 /usr/local/bin/composer

# Enable Apache Modules
RUN a2enmod ssl && a2enmod rewrite
