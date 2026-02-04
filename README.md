# DsAssistant
# Заходим в контейнер как root
docker exec -u root -it symfony_php bash

# Меняем права
chown -R www:www /var/www/html/var/
chmod -R 755 /var/www/html/var/
exit

для работы бота - идем на адресhttps://cabinet.xtunnel.ru/
получаем публичный адрес типа https://6f8c78ab-e41c-4c60-98b1-77b18ba3c29e.tunnel4.com

Указываем его в app/.env 
Потом в консоли вызываем 
make bash
php bin/console app:set-web-hook

Хук зареган и должно работать 
