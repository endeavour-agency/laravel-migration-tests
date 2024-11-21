export php := 8.2
export composerflags :=

test-all:
	@make test php=8.2 composerflags="--quiet"
	@make test php=8.3 composerflags="--quiet"

test:
	@make up
	@make install-vendor
	@docker compose exec php-$(php) vendor/bin/phpunit
	@make down

install-vendor:
	@docker compose exec php-$(php) composer install $(composerflags)

up:
	@docker compose up -d php-$(php)

down:
	@docker compose down --remove-orphans
