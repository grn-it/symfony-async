install:
	@make build
	@make up
	@docker-compose exec app make install-commands
	@make fix-permissions
	@make restart

install-commands:
	@composer install
	@symfony console doctrine:migrations:migrate -n
	@symfony console app:fixtures:load -e dev -n

fix-permissions:
	@docker-compose exec app make fix-permissions-commands uid=$(shell id -u)

fix-permissions-commands:
	@setfacl -dR -m u:$(uid):rwX .
	@setfacl -R -m u:$(uid):rwX .

build:
	@docker-compose build --force-rm

up:
	@docker-compose up -d
	
down:
	@docker-compose down

restart:
	@make down
	@make up

app:
	@docker-compose exec app bash

swoole-restart:
	@docker-compose exec app symfony console cache:clear
	@docker-compose exec app make swoole-restart-cmd
	
swoole-restart-cmd:
	@kill -USR1 $$(ps -a | grep -m1 "php public/index.php" | awk '{printf $$1}')

microservices-restart:
	@docker-compose exec app supervisorctl restart microservices:
	
benchmark:
	@docker-compose run k6 run -d 10s benchmark.k6.js
