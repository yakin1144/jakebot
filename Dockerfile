FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Copy all files
COPY . .

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Update Apache configuration to allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Use Render's PORT environment variable
RUN echo 'Listen ${PORT:-80}' > /etc/apache2/ports.conf
RUN sed -i 's/80/${PORT:-80}/g' /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD ["apache2-foreground"]