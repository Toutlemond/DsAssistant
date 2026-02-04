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

redis-cli:
	docker exec -it gb-redis /usr/local/bin/redis-cli

mysql:
	docker exec -it gb-database mysql -u root --password=1 -h 127.0.0.1

# example: make composer ARGS='require firebase/php-jwt:5.2.0 --ignore-platform-reqs'
composer:
	docker run --rm -i -v $(PWD)/app:/app composer $(ARGS)

frontend-install:
	docker run --rm --user $(shell id -u):$(shell id -g) -v $(PWD):/mnt -it node /bin/bash /mnt/docker/frontend/install.sh

frontend-build-all:
	docker run --rm --user $(shell id -u):$(shell id -g) -v $(PWD):/mnt -it node /bin/bash /mnt/docker/frontend/build.sh

# example: make frontend-build ARGS="module/widget"
frontend-build:
	docker run --rm --user $(shell id -u):$(shell id -g) -v $(PWD):/mnt -it -w /mnt/new/frontend/$(ARGS) node /bin/bash -c 'npm run lint && npm run test && npm run build'

# example: make frontend-dev ARGS="module/widget"
frontend-dev:
	docker run --rm --user $(shell id -u):$(shell id -g) -v $(PWD):/mnt -it -w /mnt/new/frontend/$(ARGS) --net host node npm run dev