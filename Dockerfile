FROM composer:2 as composer
COPY . /app
WORKDIR /app
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.1-fpm-alpine

# Install packages
RUN apk update \
    && apk --no-cache add \
      php81-json \
      php81-openssl \
      php81-curl \
      php81-zlib \
      php81-session \
      php81-mbstring \
      php81-fileinfo \
      php81-gd \
      nginx \
      supervisor \
      curl

# Configure nginx
COPY config/nginx.conf /etc/nginx/nginx.conf
COPY config/default.conf /etc/nginx/conf.d/default.conf

# Configure PHP-FPM
COPY config/fpm-pool.conf /etc/php81/php-fpm.d/www.conf
COPY config/php.ini /etc/php81/conf.d/custom.ini

# Configure supervisord
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /run \
  && chown -R nobody.nobody /var/lib/nginx \
  && chown -R nobody.nobody /var/log/nginx \
  # Setup document root
  && mkdir -p /var/www/html \
  # Cleanup
  && rm -rf /var/cache/apk/* \
    /usr/share/doc \
    /usr/share/man/ \
    /usr/share/info/* \
    /var/cache/man/* \
    /tmp/*

# Make the document root a volume
VOLUME /var/www/html

# Switch to use a non-root user from here on
USER nobody

# Add application
WORKDIR /var/www/html
COPY --chown=nobody . /var/www/html/
COPY --from=composer --chown=nobody /app/vendor /var/www/html/vendor

# Expose nginx port
EXPOSE 8080

# Start nginx & php-fpm via supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/ping
