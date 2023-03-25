DOCKER_COMPOSE = docker-compose
COMPOSER_AUTH_PATH = ~/.config/.composer/auth.json
COMPOSER_FILE_EXISTS = $(shell )

JQ := $(shell command -v jq 2> /dev/null)
all:
	ifndef JQ
		$(error "jq is not available please install jq (sudo apt update && sudo apt install jq OR https://stedolan.github.io/jq/download/)")
	endif

.PHONY: up
up:
	$(DOCKER_COMPOSE) up -d --remove-orphans ${C}

.PHONY: down
down:
	$(DOCKER_COMPOSE) down

.PHONY: reset
reset:
	$(DOCKER_COMPOSE) down -v

.PHONY: info
info:
	@echo Application is now running on http://localhost:$(shell docker inspect crawler-service-php | jq '.[].HostConfig.PortBindings."80/tcp"[].HostPort')
	@echo Database is exposed on localhost:$(shell docker inspect crawler-service-db | jq '.[].HostConfig.PortBindings."3306/tcp"[].HostPort')
	@echo Database Credentials are:
	@echo 	$(shell docker inspect crawler-service-db | jq '.[].Config.Env[] | select(. | contains("MYSQL_DATABASE"))'),
	@echo   $(shell docker inspect crawler-service-db | jq '.[].Config.Env[] | select(. | contains("MYSQL_USER"))'),
	@echo   $(shell docker inspect crawler-service-db | jq '.[].Config.Env[] | select(. | contains("MYSQL_PASSWORD"))'),
