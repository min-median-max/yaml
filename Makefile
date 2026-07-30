.PHONY: help build build-prod test test-coverage test-php cs-check cs-fix analyse phpstan parse refactor check install clean shell composer

IMAGE_NAME = purrphp-yaml
DEV_IMAGE = $(IMAGE_NAME):dev

# Forward extra goals to composer, e.g. make composer require vendor/pkg
ifeq (composer,$(firstword $(MAKECMDGOALS)))
  COMPOSER_ARGS := $(or $(cmd),$(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS)))
  $(eval $(filter-out composer,$(MAKECMDGOALS)):;@:)
endif

help: ## Show this help message
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Build Docker image for development
	docker build --target development -t $(DEV_IMAGE) .

build-no-cache: ## Build Docker image for development
	docker build --no-cache --target development -t $(DEV_IMAGE) .

test-coverage: build ## Run tests with coverage report in Docker container
	docker run --rm -v $(PWD)/coverage:/app/coverage $(DEV_IMAGE) composer test-coverage

test-unit: build ## Run only unit tests in Docker container
	docker run --rm -v $(PWD):/app $(DEV_IMAGE) ./vendor/bin/phpunit --testsuite="Unit Tests"

cs-check: build ## Check code style in Docker container
	docker run --rm -v $(PWD):/app $(DEV_IMAGE) composer cs-check

cs-fix: build ## Fix code style issues in Docker container (copy changes out)
	docker run --name temp-cs-fix $(DEV_IMAGE) composer cs-fix; \
	docker cp temp-cs-fix:/app/src/. ./src/ 2>/dev/null || true; \
	docker cp temp-cs-fix:/app/test/. ./test/ 2>/dev/null || true; \
	docker rm temp-cs-fix

analyse: build ## Run static analysis in Docker container
	docker run --rm $(DEV_IMAGE) composer analyse

phpstan: build ## Run PHPStan static analysis in Docker container (optional: path=<file|dir>)
	docker run --rm $(DEV_IMAGE) ./vendor/bin/phpstan analyse --memory-limit=1G $(path)

parse: build ## Parse YAML file (usage: make parse path=file.yaml)
	@test -n "$(path)" || (echo "usage: make parse path=<file.yaml>" >&2; exit 1)
	@test -f "$(path)" || (echo "file not found: $(path)" >&2; exit 1)
	docker run --rm -v $(PWD):/app -v $(abspath $(path)):/tmp/input.yaml:ro $(DEV_IMAGE) php -r 'require "vendor/autoload.php"; var_export((new \Purr\Yaml\Yaml)->parseFile($$argv[1])); echo PHP_EOL;' -- /tmp/input.yaml

check: build ## Run all checks (style, analysis, tests) in Docker container
	docker run --rm $(DEV_IMAGE) composer check

shell: build ## Open interactive shell in development container
	docker run --rm -it $(DEV_IMAGE) sh

composer: build ## Run Composer command in Docker container (usage: make composer require vendor/pkg)
	docker run --rm -v $(PWD):/app $(DEV_IMAGE) composer $(COMPOSER_ARGS)

install: build ## Install Composer dependencies in Docker container
	docker run --rm -v $(PWD):/app $(DEV_IMAGE) composer install

update: build ## Update Composer dependencies in Docker container and copy out
	docker run --rm -v $(PWD):/app $(DEV_IMAGE) composer update

validate: build ## Validate composer.json in Docker container
	docker run --rm $(DEV_IMAGE) composer validate --strict
