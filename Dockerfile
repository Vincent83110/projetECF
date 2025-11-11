# Utilise l'image officielle PHP avec Apache
FROM php:8.2-apache

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql

# Installer l'extension MongoDB pour PHP
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Copier les fichiers du projet dans le container
COPY . /var/www/html/
# Installer Composer
RUN apt-get update && apt-get install -y unzip curl \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

RUN composer install --no-dev --optimize-autoloader

# Définir le répertoire de travail
WORKDIR /var/www/html

# Exposer le port 80
EXPOSE 80

# Activer le module rewrite d'Apache
RUN a2enmod rewrite

# Commande par défaut pour démarrer Apache
CMD ["apache2-foreground"]
