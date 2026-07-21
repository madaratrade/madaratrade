FROM php:8.3-fpm

RUN printf "Types: deb\nURIs: http://mirror.arvancloud.ir/debian\nSuites: trixie trixie-updates\nComponents: main\nSigned-By: /usr/share/keyrings/debian-archive-keyring.pgp\n" > /etc/apt/sources.list.d/debian.sources

RUN apt-get update && apt-get install -y \
    git unzip libssl-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# PHP upload limits
RUN { \
    echo "upload_max_filesize=20M"; \
    echo "post_max_size=120M"; \
    echo "max_file_uploads=20"; \
    echo "memory_limit=256M"; \
    echo "max_execution_time=60"; \
    echo "max_input_time=60"; \
} > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
