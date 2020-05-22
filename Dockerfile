FROM alpine:3.11

# Install packages
RUN apk --no-cache add \
    php7 \
    php7-fpm \
    php7-json \
    php7-openssl \
    php7-curl \
    php7-zlib \
    php7-session \
    php7-mbstring \
    php7-fileinfo \
    php7-gd \
    nginx \
    supervisor \
    curl

# Configure nginx
COPY config/nginx.conf /etc/nginx/nginx.conf
COPY config/default.conf /etc/nginx/conf.d/default.conf

# Configure PHP-FPM
COPY config/fpm-pool.conf /etc/php7/php-fpm.d/www.conf
COPY config/php.ini /etc/php7/conf.d/custom.ini

# Configure supervisord
COPY config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /run \
  && chown -R nobody.nobody /var/lib/nginx \
  && chown -R nobody.nobody /var/log/nginx

# Setup document root
RUN mkdir -p /var/www/html

# Make the document root a volume
VOLUME /var/www/html

# Switch to use a non-root user from here on
USER nobody

# Add application
WORKDIR /var/www/html
COPY --chown=nobody . /var/www/html/

# Expose nginx port
EXPOSE 8080

# Start nginx & php-fpm via supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/ping
