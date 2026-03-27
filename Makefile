PROJECT_PATH=$(realpath ./)

NGROK_HOST_HEADER=dom

build:
	docker-compose build

up:
	docker-compose up -d

down:
	docker-compose down

rebuild:
	docker-compose up --build --force-recreate --no-deps -d

restart:
	docker-compose restart

restart-fpm:
	docker exec -it ds_php service php7.4-fpm restart

tunnel:
	./xtunnel/xtunnel http 8099

symfony:
	docker exec -it ds_php php bin/console $(ARGS)

clear:
	docker exec -it ds_php php bin/console cache:clear --no-warmup

migrate:
	docker exec -it ds_php php bin/console doctrine:migrations:migrate $(ARGS)

fixtures:
	docker exec -it ds_php php bin/console doctrine:fixtures:load

watch:
	docker exec -it ds_php yarn encore dev --watch

worker:
	docker exec -it ds_php php bin/console app:worker

route:
	docker exec -it ds_php php bin/console debug:router $(ARGS)

bash:
	docker exec -it ds_php bash

lms-start:
	lms server start --bind 0.0.0.0 --port 1234
