# Same image as in docker-compose.yaml
FROM akeneo/pim-php-base:4.0
COPY --from=composer:1 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
