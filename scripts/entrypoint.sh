#!/bin/sh
/usr/local/bin/php /var/www/html/artisan migrate --force
/usr/local/bin/php /var/www/html/artisan route:cache
/usr/local/bin/php /var/www/html/artisan config:cache
/usr/local/bin/php /var/www/html/artisan view:cache
/usr/local/bin/apache2-foreground
