FROM php:8.3-apache

# Update and install necessary packages, and PHP extensions
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y --no-install-recommends \
        zlib1g-dev \
        libwebp-dev \
        libpng-dev \
        libzip-dev \
        default-mysql-client && \
    docker-php-ext-install gd zip pdo_mysql && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files to the working directory
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html

# Set the environment variables
ENV MYSQL_HOST=mysql \
    MYSQL_USER=skimate_user \
    MYSQL_PASSWORD=skimate \
    MYSQL_DATABASE=skimate

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]