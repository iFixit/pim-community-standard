#
# This file is a template Makefile. Some targets are presented here as examples.
# Feel free to customize it to your needs!
#
DOCKER_COMPOSE = docker-compose
CMD_ON_PROJECT = $(DOCKER_COMPOSE) run -u www-data --rm php
PHP_RUN = $(CMD_ON_PROJECT) php
YARN_RUN = $(DOCKER_COMPOSE) run -u node --rm -e YARN_REGISTRY -e PUPPETEER_SKIP_CHROMIUM_DOWNLOAD node yarn
CONSOLE = $(PHP_RUN) bin/console
AKENEO_FIXTURES = vendor/akeneo/pim-community-dev/src/Akeneo/Platform/Bundle/InstallerBundle/Resources/fixtures

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

.PHONY: javascript-extensions
javascript-extensions:
	$(YARN_RUN) run update-extensions

.PHONY: front-packages
front-packages:
	PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=1 $(YARN_RUN) packages:build

.PHONY: assets
assets:
	$(CMD_ON_PROJECT) rm -rf public/bundles public/js
	$(PHP_RUN) bin/console pim:installer:assets --symlink --clean

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
front: assets css front-packages javascript-dev

.PHONY: database
database:
	echo "This would have deleted the database entirely and recreated it empty"
	echo "Uncomment out the below line to actually run the task"
	exit 1
	# $(CONSOLE) pim:installer:db ${O}

.PHONY: cache
cache:
	rm -rf var/cache && $(PHP_RUN) bin/console cache:warmup

composer.lock: composer.json
	$(PHP_RUN) -d memory_limit=4G /usr/local/bin/composer update

vendor: composer.lock
	$(PHP_RUN) -d memory_limit=4G /usr/local/bin/composer install

.PHONY: dependencies
dependencies: vendor node_modules

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
	$(MAKE) front-packages
	$(MAKE) javascript-prod
	$(MAKE) css
	$(MAKE) javascript-extensions

.PHONY: bootstrap-database
bootstrap-database:
	cp fixtures/jobs.yml $(AKENEO_FIXTURES)/minimal/jobs.yml
	APP_ENV=prod $(MAKE) database O="--catalog $(AKENEO_FIXTURES)/minimal"
	$(CONSOLE) pim:user:create --admin --no-interaction -- admin "${ADMIN_PASSWORD}" admin@admin.com Admin Admin en_US
	$(CONSOLE) akeneo:elasticsearch:reset-indexes

.PHONY: reindex
reindex:
	$(CONSOLE) akeneo:elasticsearch:reset-indexes
	$(CONSOLE) pim:product-model:index --all
	$(CONSOLE) pim:product:index --all

.PHONY: pim-dev
pim-dev:
ifndef NO_DOCKER
	APP_ENV=dev $(MAKE) up
	docker/wait_docker_up_dev.sh
endif
	$(MAKE) cache
	$(MAKE) assets
	$(MAKE) front-packages
	$(MAKE) javascript-dev
	$(MAKE) css
	$(MAKE) javascript-extensions

.PHONY: up
up:
	$(DOCKER_COMPOSE) up -d --remove-orphans

.PHONY: down
down:
	$(DOCKER_COMPOSE) down -v

.PHONY: ifixit-upgrade
ifixit-upgrade: dependencies cache assets front-packages javascript-prod css javascript-extensions
	bash vendor/akeneo/pim-community-dev/std-build/install-required-files.sh
	patch -p0 < patches/migrations.patch
	cp patches/migrations/* upgrades/schema/
	cp .env .env.upgrade
	cp .env.local .env
	# Some migrations need elasticsearch to be running
	$(DOCKER_COMPOSE) up --detach elasticsearch
	docker/wait_docker_up.sh
	# Ensure the migrations table exists
	$(CONSOLE) doctrine:migrations:sync-metadata-storage
	# Mark the migrations that were ran during the V4 upgrade as already having
	# been ran. Back then Akeneo didn't have a migrations table or something
	$(CONSOLE) doctrine:migrations:version --add --range-from='Pim\Upgrade\Schema\Version_4_0_20190801083247_remove_indexes' --range-to='Pim\Upgrade\Schema\Version_4_0_20200728092625_add_remove_non_existing_values_job'
	# Start with empty indexes so some of the migrations that deal with
	# elasticsearch don't fail on weird v4 schema
	$(CONSOLE) akeneo:elasticsearch:reset-indexes
	# Run all pending migrations
	$(CONSOLE) doctrine:migrations:migrate
	# Ensure we have the goods (system requirements)
	$(CONSOLE) pim:installer:check-requirements
	# One of the above commands may have created cache files with a different user
	rm -rf var/cache
	# Stop the containers that were run for the migration process
	$(MAKE) down
	# Build the containers, launch the services
	$(MAKE) prod
	# Re-index all the content into elasticsearch
	$(MAKE) reindex
