# ─────────────────────────────────────────────────────────────
# 8AM Coffee — Dockerfile (Railway / Render / bất kỳ host Docker nào)
# Multi-stage: build asset Vite bằng Node, rồi chạy PHP + Apache.
# ─────────────────────────────────────────────────────────────

# Stage 1: build CSS/JS (Three.js, Alpine, Tailwind)
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm ci
COPY resources resources
RUN npm run build

# Stage 2: PHP runtime
FROM php:8.2-apache

# Extension cần cho Laravel + MySQL + GD (ext-gd cho simple-qrcode)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev unzip git cron \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql bcmath zip gd \
 && rm -rf /var/lib/apt/lists/*

# Crontab cho Laravel scheduler — chạy mỗi phút, log vào /var/log/laravel-cron.log
RUN echo "* * * * * root cd /var/www/html && php artisan schedule:run >> /var/log/laravel-cron.log 2>&1" \
    > /etc/cron.d/laravel-scheduler \
 && chmod 0644 /etc/cron.d/laravel-scheduler

# Apache: chỉ dùng MỘT MPM (prefork) + bật rewrite.
# Xóa thẳng symlink event/worker rồi bật prefork — tránh "More than one MPM loaded".
RUN rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* ; \
    a2enmod mpm_prefork rewrite ; \
    echo "== MPM dang bat ==" ; ls /etc/apache2/mods-enabled/ | grep -i mpm || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
# Lấy asset đã build từ stage 1
COPY --from=assets /app/public/build public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
 && mkdir -p public/images public/images-seed \
 && cp -r public/images/. public/images-seed/ \
 && chown -R www-data:www-data storage bootstrap/cache public/images public/images-seed

# Apache document root -> thư mục public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080
CMD ["/usr/local/bin/entrypoint.sh"]
