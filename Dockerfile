FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Copy all files
COPY . .

# Enable Apache modules
RUN a2enmod rewrite headers

# Update Apache configuration to allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Configure Apache to listen on the PORT environment variable
RUN echo 'Listen ${PORT:-80}' > /etc/apache2/ports.conf
RUN sed -i 's/80/${PORT:-80}/g' /etc/apache2/sites-available/000-default.conf

# Set proper permissions for data files
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 777 /var/www/html/users.json /var/www/html/game-logs.json

# Start Apache
CMD ["apache2-foreground"]
