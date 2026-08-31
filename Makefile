COMPOSE = docker compose -f Docker/docker-compose.yml
APP = $(COMPOSE) exec -T app

.DEFAULT_GOAL := help

.PHONY: help up down build restart logs shell composer \
        install setup test pint analyse artisan mcp-boost verify

help: ## Show available commands
	@grep -E '^[a-zA-Z0-9_-]+:.*##' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

up: ## Start all core services (bootstraps .env + dependencies on first run)
	@test -f .env || cp .env.example .env
	$(COMPOSE) up -d --remove-orphans
	@if [ ! -f vendor/autoload.php ]; then \
		echo "==> vendor/ missing — installing PHP dependencies (first run)"; \
		$(APP) composer install --no-interaction; \
	fi
	@grep -q '^APP_KEY=base64:' .env || $(APP) php artisan key:generate --ansi

down: ## Stop all services
	$(COMPOSE) down --remove-orphans

build: ## Build or rebuild Docker images
	$(COMPOSE) build --no-cache

restart: ## Restart core services
	$(COMPOSE) restart app nginx

logs: ## Tail application logs
	$(COMPOSE) logs -f app nginx

shell: ## Open bash shell in the app container
	$(COMPOSE) exec app bash

composer: ## Run composer inside the app container (usage: make composer cmd="require package")
	$(APP) composer $(cmd)

install: up ## Install PHP dependencies
	$(APP) composer install --no-interaction

setup: install ## First-time project setup (env, key)
	@test -f .env || cp .env.example .env
	$(APP) php artisan key:generate --ansi

test: ## Run PHPUnit test suite
	$(APP) php artisan test

pint: ## Format code with Laravel Pint
	$(APP) ./vendor/bin/pint

analyse: ## Run PHPStan static analysis
	$(APP) ./vendor/bin/phpstan analyse --memory-limit=1G

artisan: ## Run an Artisan command (usage: make artisan cmd="route:list")
	$(APP) php artisan $(cmd)

mcp-boost: ## Smoke-test Laravel Boost MCP server
	./Docker/scripts/mcp-boost.sh --check

verify: ## Run health checks (pint, analyse, test)
	$(APP) ./vendor/bin/pint --test
	$(APP) ./vendor/bin/phpstan analyse --memory-limit=1G
	$(APP) php artisan test
