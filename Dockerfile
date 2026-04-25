# Sử dụng FrankenPHP làm nền tảng (Cực nhanh và nhẹ cho Laravel)
FROM dunglas/frankenphp:1-php8.2-bookworm

# Cài đặt các thư viện cần thiết cho Laravel
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

# Cấu hình FrankenPHP
ENV SERVER_NAME=:80
ENV FRANKENPHP_CONFIG="worker ./public/index.php"

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Chép toàn bộ mã nguồn vào container
WORKDIR /app
COPY . .

# Cài đặt PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Cài đặt Node.js & NPM để build Frontend (Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install \
    && npm run build

# Phân quyền cho storage và bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose cổng của Render (Render dùng biến $PORT, mặc định FrankenPHP lắng nghe trên 80)
EXPOSE 80

# Chạy server
CMD ["frankenphp", "php-server", "--root", "./public"]
