FROM php:8.2-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    cron \
    nano

# Очистка кеша
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка расширений PHP (БЕЗ curl - он уже есть в базовом образе)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip sockets

# Установка PECL расширений
RUN pecl install redis && docker-php-ext-enable redis
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Ручная установка Node.js
# RUN curl -fsSL https://nodejs.org/dist/v18.20.4/node-v18.20.4-linux-x64.tar.xz -o node.tar.xz \
#    && tar -xf node.tar.xz -C /usr/local --strip-components=1 \
#    && rm node.tar.xz \
#    && ln -s /usr/local/bin/node /usr/local/bin/nodejs

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем пользователя для приложения
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Копируем код приложения
COPY app /var/www/html
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Создаем директории и настраиваем права
RUN mkdir -p /var/www/html/var/log /var/www/html/var/cache \
    && chown -R www:www /var/www/html/var \
    && chmod -R 755 /var/www/html/var

# Меняем владельца файлов
RUN chown -R www:www /var/www/html

# Переключаемся на пользователя www
USER www

# Рабочая директория
WORKDIR /var/www/html

# Устанавливаем зависимости Symfony (игнорируя ext-http)
RUN if [ -f "composer.json" ]; then composer install --optimize-autoloader --ignore-platform-req=ext-http; fi

EXPOSE 9000
CMD ["php-fpm"]