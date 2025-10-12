# استخدم صورة PHP رسمية مع Composer
FROM php:8.2-fpm

# تعيين مجلد العمل داخل الحاوية
WORKDIR /var/www/html

# تثبيت إضافات PostgreSQL
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev zip curl libzip-dev \
    libpq-dev && \
    docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip pdo_pgsql

# تثبيت Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# نسخ ملفات المشروع إلى داخل الحاوية
COPY . .

# تثبيت الاعتمادات عبر Composer
RUN composer install --no-dev --optimize-autoloader

# إعداد صلاحيات المجلدات
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# فتح المنفذ
EXPOSE 9000

# تشغيل خادم PHP
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=9000"]

