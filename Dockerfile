# Sử dụng Apache làm máy chủ (Cực kỳ ổn định trên Render)
FROM php:8.2-apache

# Cài đặt các thư viện cần thiết cho Laravel & PostgreSQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    && docker-php-ext-install zip pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Bật mod_rewrite của Apache (Bắt buộc cho Laravel)
RUN a2enmod rewrite

# Cấu hình lại DocumentRoot của Apache trỏ vào thư mục public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Chép mã nguồn vào container
WORKDIR /var/www/html
COPY . .

# Cài đặt PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Cài đặt Node.js & NPM để build Frontend (Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install \
    && npm run build

# Phân quyền cho storage và bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Render sử dụng cổng 80 mặc định cho Apache
EXPOSE 80

# Tự động chạy migrate và nạp dữ liệu xe khi khởi động container
CMD ["sh", "-c", "php artisan migrate --force && php artisan vehicle:sync && apache2-foreground"]
