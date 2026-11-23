FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache directory permissions
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose default HTTP port
EXPOSE 80

# Dynamically bind Apache port to Railway $PORT environment variable
CMD sh -c "sed -i 's/Listen 80/Listen '\${PORT:-80}'/g' /etc/apache2/ports.conf && sed -i 's/:80/:'\${PORT:-80}'/g' /etc/apache2/sites-available/000-default.conf && apache2-foreground"
