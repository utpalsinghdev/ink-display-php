FROM php:8.2-cli

# Install required PHP extensions
RUN docker-php-ext-install -j$(nproc) curl

# Set permissions for cache directory
RUN mkdir -p cache && chmod 755 cache

# Expose port 8000 (same as local)
EXPOSE 8000

# Start PHP built-in server (same as local: php -S localhost:8000)
CMD ["php", "-S", "0.0.0.0:8000"]

