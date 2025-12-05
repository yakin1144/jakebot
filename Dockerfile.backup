FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Copy all files
COPY . .

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Update Apache configuration to allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Create a script to handle dynamic port assignment
RUN echo '#!/bin/bash\n\nPORT=${PORT:-80}\nsed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf\n\nexec apache2-foreground' > /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Start Apache with dynamic port
ENTRYPOINT ["/entrypoint.sh"]