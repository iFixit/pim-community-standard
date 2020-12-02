#
# This file is a template Makefile. Some targets are presented here as examples.
# Feel free to customize it to your needs!
#
DOCKER_COMPOSE = docker-compose
CMD_ON_PROJECT = $(DOCKER_COMPOSE) run -u www-data --rm php
PHP_RUN = $(CMD_ON_PROJECT) php
YARN_RUN = $(DOCKER_COMPOSE) run -u node --rm -e YARN_REGISTRY -e PUPPETEER_SKIP_CHROMIUM_DOWNLOAD node yarn
CONSOLE = $(PHP_RUN) bin/console

ifdef NO_DOCKER
  CMD_ON_PROJECT =
  YARN_RUN = yarnpkg
  PHP_RUN = php
endif

.DEFAULT_GOAL := dev

yarn.lock: package.json
	PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=1 $(YARN_RUN) install

node_modules: yarn.lock
	PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=1 $(YARN_RUN) install

.PHONY: assets
assets:
	$(CMD_ON_PROJECT) rm -rf public/bundles public/js
	$(CONSOLE) pim:installer:assets --symlink --clean

.PHONY: css
css:
	$(CMD_ON_PROJECT) rm -rf public/css
	$(YARN_RUN) run less

.PHONY: javascript-prod
javascript-prod:
	$(CMD_ON_PROJECT) rm -rf public/dist
	$(YARN_RUN) run webpack

.PHONY: javascript-dev
javascript-dev:
	$(CMD_ON_PROJECT) rm -rf public/dist
	$(YARN_RUN) run webpack-dev

.PHONY: front
front: assets css javascript-dev

.PHONY: database
database:
	echo "This would have deleted the database entirely and recreated it empty"
	echo "Uncomment out the below line to actually run the task"
	exit 1
	# $(CONSOLE) pim:installer:db ${O}

.PHONY: cache
cache:
	$(CMD_ON_PROJECT) rm -rf var/cache && $(CONSOLE) --verbose cache:warmup

vendor: composer.lock
	$(PHP_RUN) -d memory_limit=4G /usr/local/bin/composer install

autoload:
	$(PHP_RUN) -d memory_limit=4G /usr/local/bin/composer dump-autoload

.PHONY: dependencies
dependencies: vendor node_modules autoload

.PHONY: dev
dev:
	$(MAKE) dependencies
	$(MAKE) pim-dev

.PHONY: prod
prod:
	$(MAKE) dependencies
	$(MAKE) pim-prod

.PHONY: pim-prod
pim-prod:
	$(MAKE) cache
ifndef NO_DOCKER
	APP_ENV=prod $(MAKE) up
	docker/wait_docker_up.sh
endif
	$(MAKE) assets
	$(MAKE) javascript-prod

.PHONY: bootstrap-database
bootstrap-database:
	cp fixtures/jobs.yml vendor/akeneo/pim-community-dev/src/Akeneo/Platform/Bundle/InstallerBundle/Resources/fixtures/minimal/jobs.yml
	APP_ENV=prod $(MAKE) database O="--catalog vendor/akeneo/pim-community-dev/src/Akeneo/Platform/Bundle/InstallerBundle/Resources/fixtures/minimal"
	$(CONSOLE) pim:user:create
	$(CONSOLE) akeneo:elasticsearch:reset-indexes

.PHONY: pim-dev
pim-dev:
	$(MAKE) cache
ifndef NO_DOCKER
	APP_ENV=dev $(MAKE) up
	docker/wait_docker_up_dev.sh
endif
	$(MAKE) assets
	$(MAKE) javascript-dev
	APP_ENV=dev $(MAKE) database O="--catalog vendor/akeneo/pim-community-dev/src/Akeneo/Platform/Bundle/InstallerBundle/Resources/fixtures/icecat_demo_dev"

.PHONY: up
up:
	$(DOCKER_COMPOSE) up -d --remove-orphan

.PHONY: down
down:
	$(DOCKER_COMPOSE) down -v

