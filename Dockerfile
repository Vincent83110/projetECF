# Étape 1 : image PHP avec Apache
FROM php:8.2-apache

# Installer les extensions nécessaires : PostgreSQL, PDO, MongoDB, utils
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Installer l'extension MongoDB via PECL
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Activer mod_rewrite pour Apache
RUN a2enmod rewrite

# Copier le code source dans le container
COPY . /var/www/html/

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer les dépendances PHP via Composer
RUN composer install --no-dev --optimize-autoloader

# Donner les droits au serveur Apache
RUN chown -R www-data:www-data /var/www/html

# Exposer le port 80 pour Render
EXPOSE 80

# Commande par défaut pour lancer Apache
CMD ["apache2-foreground"]