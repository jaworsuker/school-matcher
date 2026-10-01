UID := $(shell id -u)
GID := $(shell id -g)
export UID GID

DC      = docker compose
PHP     = $(DC) exec php
CONSOLE = $(PHP) php bin/console

.DEFAULT_GOAL := help
.PHONY: help setup build up down reset install migrate import test sh logs

help: ## Lista dostępnych komend
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

setup: build up install migrate import ## Pełna instalacja od zera (build, start, composer, migracje, import szkół)

build: ## Budowa obrazów
	$(DC) build

up: ## Start kontenerów
	$(DC) up -d --wait

down: ## Zatrzymanie kontenerów
	$(DC) down

reset: ## Zatrzymanie kontenerów i usunięcie bazy danych
	$(DC) down -v

install: ## Instalacja zależności composera
	$(PHP) composer install --no-interaction

migrate: ## Migracje bazy danych
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

import: ## Import listy szkół z docs/schools.txt
	$(CONSOLE) app:schools:import docs/schools.txt

test: ## Testy (przygotowuje bazę testową)
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test
	$(PHP) bin/phpunit

sh: ## Shell w kontenerze PHP
	$(PHP) sh

logs: ## Logi kontenerów
	$(DC) logs -f
