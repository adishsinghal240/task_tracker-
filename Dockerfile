# Base image with PHP 8.2 and Apache
FROM php:8.2-apache

# Install mysqli extension for MySQL database connectivity
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure PHP upload limits
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 128M" >> /usr/local/etc/php/conf.d/uploads.ini

# Copy project files into web root
WORKDIR /var/www/html
COPY . /var/www/html/

# Ensure uploads directory exists and has proper permissions
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/uploads

EXPOSE 80
CMD ["apache2-foreground"]

