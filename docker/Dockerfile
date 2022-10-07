# Same image as in docker-compose.yaml
FROM akeneo/pim-php-base:5.0
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
RUN mkdir -p /etc/ssl/certs/
# USER 1000
