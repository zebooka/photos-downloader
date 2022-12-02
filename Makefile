.PHONY: help all docker-images composer start stop sh test build clean purge
.DEFAULT := help

MAKEFILE_PATH := $(abspath $(lastword $(MAKEFILE_LIST)))
CURRENT_DIR := $(dir $(MAKEFILE_PATH))

help:
	@awk 'BEGIN {FS = ":.*##"; printf "\n\033[1mUsage:\n  make \033[36m<target>\033[0m\n"} \
	/^[a-zA-Z0-9_-]+:.*?##/ { printf "  \033[36m%-40s\033[0m %s\n", $$1, $$2 } /^##@/ \
	{ printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)



##@ Development

all: ## Setup, test & build phar
all: docker-images composer test build

docker-images: ## Create docker images
docker-images:
	docker-compose up --no-start --build

composer: ## Install composer dependencies
composer:
	docker-compose run --rm -e COMPOSER_NO_DEV=1 -- php73 composer install -v -n -d /app
	docker-compose run --rm -e COMPOSER_VENDOR_DIR=vendor-dev -- php73 composer install -v -n -d /app

start: ## Start services in background
start:
	docker-compose start

stop: ## Stop services
stop:
	docker-compose stop

sh: ## Open shell in container
sh:
	docker-compose exec -- php73 /bin/sh

test: ## Run tests
test:
	docker-compose run --rm -- php73 /app/tests/run.sh
	docker-compose run --rm -- php74 /app/tests/run.sh
	docker-compose run --rm -- php80 /app/tests/run.sh
	docker-compose run --rm -- php81 /app/tests/run.sh
	docker-compose run --rm -- php82 /app/tests/run.sh

build: ## Build PHAR
build:
	docker-compose run --rm -e PHAR_SKELETON_ALIAS="photos-downloader.phar" -e PHAR_SKELETON_NAMESPACE="Zebooka" -- php73 /app/build-phar.php

clean: ## Clean built PHAR
clean:
	cd "${CURRENT_DIR}" && rm -fv ./build/photos-downloader.phar

purge: ## Stop, clean and remove all docker-images/logs/vendor files
purge: stop clean
	docker-compose down --rmi local
	cd "${CURRENT_DIR}" && rm -rfv ./log ./vendor ./vendor-dev
