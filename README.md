# DsAssistant
# Заходим в контейнер как root
docker exec -u root -it symfony_php bash

# Меняем права
chown -R www:www /var/www/html/var/
chmod -R 755 /var/www/html/var/
exit