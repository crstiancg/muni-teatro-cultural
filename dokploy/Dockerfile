# Imagen de producción para Dokploy: una sola imagen con el front compilado,
# Laravel sin dependencias de desarrollo, nginx + php-fpm y el scheduler.
# Se construye desde la RAÍZ del repo:  docker build -f dokploy/Dockerfile .

# ---------- 1. front (Quasar) ----------
FROM node:24-bookworm-slim AS front
WORKDIR /front
COPY fronted/package.json fronted/package-lock.json ./
# sin scripts: el postinstall ("quasar prepare") necesita quasar.config.js, que llega después
RUN npm ci --no-audit --no-fund --ignore-scripts
COPY fronted/ ./
# API vacía: front y API van en el mismo dominio (rutas relativas)
RUN printf 'QCLI_API_BACKEND_URL=\n' > .env.production && npx quasar build

# ---------- 2. dependencias PHP de producción ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-reqs
COPY backend/ ./
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ---------- 3. imagen final ----------
FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor default-mysql-client unzip \
        libzip-dev libicu-dev libonig-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip bcmath intl gd exif opcache \
    && rm -rf /var/lib/apt/lists/* \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

WORKDIR /var/www/html
COPY --from=vendor /app ./
COPY --from=front /front/dist/spa /tmp/spa

# el build del front dentro de Laravel (assets en public/, index.html como plantilla)
RUN php artisan package:discover --ansi \
    && php artisan spa:publicar /tmp/spa \
    && rm -rf /tmp/spa \
    && rm -f public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

COPY dokploy/ /opt/racc-dokploy/
RUN cp /opt/racc-dokploy/nginx.conf /etc/nginx/sites-available/default \
    && cp /opt/racc-dokploy/supervisord.conf /etc/supervisor/conf.d/racc.conf \
    && cp /opt/racc-dokploy/php.ini /usr/local/etc/php/conf.d/zz-racc.ini \
    && cp /opt/racc-dokploy/php-fpm.conf /usr/local/etc/php-fpm.d/zz-racc.conf \
    && cp /opt/racc-dokploy/entrypoint.sh /usr/local/bin/racc-entrypoint \
    && sed -i 's/\r$//' /usr/local/bin/racc-entrypoint \
    && chmod +x /usr/local/bin/racc-entrypoint

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/racc-entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
