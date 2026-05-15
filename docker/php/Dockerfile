FROM php:8.4.2-fpm

WORKDIR /var/www

ARG USER_ID=1001
ARG GROUP_ID=1001
ARG USER_NAME=xdocker

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libonig-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    libzip-dev \
    openssh-server \
    libcurl4-openssl-dev \
    libpq-dev \
    pkg-config \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    # and admin tools
    openssh-client \
    net-tools \
    iputils-ping \
    telnet \
    nmap \
    git \
    git-flow \
    vim \
    nano \
    mc

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

#Install composer
RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Install extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql mbstring zip bcmath exif pcntl sockets curl
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

RUN pecl install xdebug
RUN docker-php-ext-enable xdebug

# Add user at application
RUN groupadd -g $GROUP_ID $USER_NAME
RUN useradd -u $USER_ID -ms /bin/bash -g $USER_NAME $USER_NAME


COPY --chown=$USER_NAME:$USER_NAME . /var/www

# Change current user to www
USER $USER_NAME

EXPOSE 9000
CMD ["php-fpm"]
