# Sử dụng Apache làm máy chủ (Cực kỳ ổn định trên nền tảng Cloud như Render)
FROM php:8.2-apache

# Cài đặt các thư viện cần thiết cho Laravel & PostgreSQL / MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip mbstring exif pcntl bcmath gd intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Bật mod_rewrite của Apache (Bắt buộc cho Laravel)
RUN a2enmod rewrite

# Cấu hình lại DocumentRoot của Apache trỏ vào thư mục public của Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Chép mã nguồn vào container
WORKDIR /var/www/html
COPY . .

# Cài đặt PHP dependencies
# Chú ý: Cài đặt không dev dependencies để tối ưu kích thước
RUN composer install --no-dev --optimize-autoloader

# Cài đặt Node.js & NPM để build Frontend (Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install \
    && npm run build \
    && rm -rf node_modules # Xóa node_modules sau khi build xong để làm nhẹ image

# Phân quyền cho storage và bootstrap/cache để Apache có thể ghi file
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cổng mặc định
EXPOSE 80

# CHÚ Ý QUAN TRỌNG:
# Đã loại bỏ lệnh `php artisan migrate` ở CMD để tránh Race Condition (Lỗi Deadlock DB) khi scale nhiều instance.
# Trên Render.com hoặc AWS, bạn PHẢI cấu hình lệnh migrate trong mục "Release Command" (ví dụ: php artisan migrate --force).
CMD ["apache2-foreground"]
