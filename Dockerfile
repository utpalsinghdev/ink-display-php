FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install -j$(nproc) curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set permissions for cache directory
RUN mkdir -p cache && chmod 755 cache

# Expose port 8000 (same as local)
EXPOSE 8000

# Start PHP built-in server with index.php as router (same as local: php -S localhost:8000)
CMD ["php", "-S", "0.0.0.0:8000", "-t", ".", "index.php"]

