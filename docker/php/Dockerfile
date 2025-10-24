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

# Установка расширений PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip
RUN pecl install redis && docker-php-ext-enable redis

# После установки PHP расширений добавить:
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем пользователя для приложения
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Копируем код приложения
COPY app /var/www/html
# После копирования php.ini добавить копию xdebug конфига:
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Меняем владельца файлов
RUN chown -R www:www /var/www/html

# Переключаемся на пользователя www
USER www

# Рабочая директория
WORKDIR /var/www/html

# Устанавливаем зависимости Symfony (если есть composer.json)
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader; fi

EXPOSE 9000
CMD ["php-fpm"]
