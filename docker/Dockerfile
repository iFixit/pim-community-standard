# Same image as in docker-compose.yaml
FROM akeneo/pim-php-base:4.0
COPY --from=composer:1 /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
RUN mkdir -p /etc/ssl/certs/
ADD https://curl.haxx.se/ca/cacert.pem /etc/ssl/certs/ca-bundle.pem
RUN chmod +r /etc/ssl/certs/ca-bundle.pem
