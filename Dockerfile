FROM php:8.3-fpm-alpine

# Install packages
RUN apk update \
    && apk --no-cache add \
      php82-json \
      php82-openssl \
      php82-curl \
      php82-zlib \
      php82-session \
      php82-mbstring \
      php82-fileinfo \
      php82-gd \
      nginx \
      supervisor \
      curl

# Copy composer
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# Configure nginx
COPY config/default.conf /etc/nginx/http.d/default.conf

# Configure PHP & PHP-FPM
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
  && sed -i 's/expose_php = On/expose_php = Off/g' /usr/local/etc/php/php.ini
COPY config/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY config/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

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

# Install dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
  && chown -R nobody:nobody /var/www/html/vendor

# Expose nginx port
EXPOSE 8080

# Start nginx & php-fpm via supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/ping
