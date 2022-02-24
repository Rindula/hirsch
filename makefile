SHELL = /bin/bash
COMPOSER = composer
YARN = yarn
GIT = git
EXEC_PHP = php
ENV = prod
ifneq (, $(shell which ddev))
  # If we are in a ddev project, we need to use the ddev-composer
  # command to install dependencies.
  COMPOSER = ddev composer
  YARN = ddev exec yarn
  EXEC_PHP = ddev exec php
  ENV = dev
endif
ifdef APP_ENV
	ENV = $(APP_ENV)
endif
SYMFONY = $(EXEC_PHP) bin/console
ARTIFACT_NAME = artifact.tar
MAKEFLAGS := --jobs=$(shell nproc)

help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

msg: ## Run symfony message consumer
	$(SYMFONY) messenger:consume async -vv --time-limit=3600

install: install_deps install_db  ## Install the project

install_deps: vendor .env.local.php public/build/manifest.json ## Install and build all dependencies

.git/lfs:
	git lfs install

install_db: vendor .env.local.php migrations ## Install the database
	$(SYMFONY) doctrine:migrations:migrate --no-interaction
	touch $@

replace: config/packages/security.yaml
	sed -i 's/127.0.0.1, ::1/$(NOPASSWDIPS)/g' config/packages/security.yaml

vendor vendor/autoload.php: composer.json composer.lock
	$(COMPOSER) validate
	$(COMPOSER) install --prefer-dist --no-interaction

.env.local:
	@echo 'APP_ENV=$(ENV)' | tee .env.local
	@if [ -n "$(DBPASS)" ]; then echo 'DATABASE_URL="mysql://hirsch:$(DBPASS)@localhost:3306/hirsch?serverVersion=mariadb-10.6.4"' | tee -a .env.local; fi;
	@if [ -n "$(SALT)" ]; then echo 'APP_SECRET="$(SALT)"' | tee -a .env.local; fi;
	@echo 'MailAccess_host="{sslin.df.eu/imap/ssl}INBOX"' | tee -a .env.local
	@echo 'MailAccess_username="essen@hochwarth-e.com"' | tee -a .env.local
	@if [ -n "$(EMAILPASS)" ]; then echo 'MailAccess_password="$(EMAILPASS)"' | tee -a .env.local; echo 'EMAILPASS="$(EMAILPASS)"' | tee -a .env.local; fi;
	@echo 'EMAILUSER="essen@hochwarth-e.com"' | tee -a .env.local
	@echo 'MAILER_DSN=smtp://sslout.df.eu:465' | tee -a .env.local
	@if [ -n "$(VERSION)" ]; then echo 'APP_VERSION="$(VERSION)"' | tee -a .env.local; fi;
	@if [ -n "$(HT_USER)" ]; then echo 'HT_USERNAME="$(HT_USER)"' | tee -a .env.local; fi;
	@if [ -n "$(HT_PASS)" ]; then echo 'HT_PASSWORD="$(HT_PASS)"' | tee -a .env.local; fi;
	@if [ -n "$(MS_GRAPH_TENANT)" ]; then echo 'MS_GRAPH_TENANT="$(MS_GRAPH_TENANT)"' | tee -a .env.local; fi;
	@if [ -n "$(MS_GRAPH_CLIENT_SECRET)" ]; then echo 'MS_GRAPH_CLIENT_SECRET="$(MS_GRAPH_CLIENT_SECRET)"' | tee -a .env.local; fi;
	@if [ -n "$(MS_GRAPH_CLIENT_ID)" ]; then echo 'MS_GRAPH_CLIENT_ID="$(MS_GRAPH_CLIENT_ID)"' | tee -a .env.local; fi;
	@if [ -n "$(CI)" ]; then grep -qxF 'FcgidWrapper "/home/httpd/cgi-bin/php81-fcgi-starter.fcgi" .php' public/.htaccess || echo 'FcgidWrapper "/home/httpd/cgi-bin/php81-fcgi-starter.fcgi" .php' | tee -a public/.htaccess; fi;


.env.local.php: .env.local vendor
	$(COMPOSER) dump-env $(ENV) --no-interaction

node_modules node_modules/.bin/encore: vendor
	$(YARN) install --force

build public public/build public/build/manifest.json: node_modules/.bin/encore vendor assets/app.js assets/styles/app.scss assets/js/scripts.js assets
	$(YARN) build

$(ARTIFACT_NAME):
	tar -cf "$(ARTIFACT_NAME)" .

tests_db:
	$(SYMFONY) doctrine:database:drop --env=test --force || true
	$(SYMFONY) doctrine:database:create --env=test
	$(SYMFONY) doctrine:migrations:migrate --env=test --no-interaction

tests: export APP_ENV=test
tests: .git/lfs tests_db
	$(EXEC_PHP) vendor/bin/phpstan
	$(EXEC_PHP) bin/phpunit

coverage coverage-xml coverage-html: tests_db
ifneq (, $(shell which ddev))
	ddev xdebug on
endif
	$(EXEC_PHP) -d xdebug.mode=coverage ./bin/phpunit --coverage-html coverage-html --coverage-xml coverage-xml --coverage-clover coverage.xml
ifneq (, $(shell which ddev))
	ddev xdebug off
endif
	$(EXEC_PHP) ./bin/coverage-checker coverage.xml 50

infection_test: export APP_ENV=test
infection_test: tests_db coverage
ifneq (, $(shell which ddev))
	$(error "Infection test is not supported on ddev")
endif
	$(EXEC_PHP) -d xdebug.mode=coverage ./vendor/bin/infection --only-covered --min-msi=100

clean: ## Clean up the project
	rm -rf vendor
	rm -rf var
	rm -rf node_modules
	rm -rf .env.local
	rm -rf .env.local.php
	rm -rf public/build
	rm -rf coverage-html coverage-xml clover.xml

.PHONY: tests install msg help clean install_deps build replace infection_test tests_db coverage
