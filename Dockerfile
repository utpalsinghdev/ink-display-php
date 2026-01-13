FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install -j$(nproc) curl

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions for cache directory
RUN mkdir -p cache && chmod 755 cache

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]

