# Utiliser l'image de base officielle PHP avec Apache
FROM php:8.3-apache

# Mettre à jour les dépôts et installer les dépendances nécessaires
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y --no-install-recommends \
    zlib1g-dev \
    libwebp-dev \
    libpng-dev \
    libzip-dev \
    default-mysql-client \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install gd zip pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configuration PHP (si nécessaire, ajouter vos propres fichiers php.ini)
RUN echo "date.timezone=Europe/Paris" > /usr/local/etc/php/conf.d/timezone.ini


# Donner les bons droits pour Apache
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Exposer le port 80 pour le serveur web
EXPOSE 80

# Lancer Apache en mode foreground
CMD ["apache2-foreground"]
