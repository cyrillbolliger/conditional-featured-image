FROM wordpress:latest

RUN apt-get update
RUN apt-get install -y \
    sudo \
    less

# Add xdebug
RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# Add WP-CLI
RUN curl -o /bin/wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
COPY wp-su.sh /bin/wp
RUN chmod +x /bin/wp-cli.phar /bin/wp