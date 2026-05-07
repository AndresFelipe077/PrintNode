# Use an official PHP Apache image
FROM php:8.2-apache

# Install necessary PHP extensions if any (this app is simple, but good for future)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite for friendly URLs (optional but recommended)
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy the project files to the container
# We only need the web files for the VPS deployment
COPY index.php ./
COPY api.php ./
COPY docs/ ./docs/

# Create an empty queue file and set permissions
RUN touch orders_queue.json && chmod 777 orders_queue.json

# Adjust permissions for the web server
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# The default command is already to start Apache in the foreground
