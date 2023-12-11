.PHONY: install
install:
	docker-compose exec -T --workdir /var/www/html/modules/multisafepayofficial app composer install
	docker-compose exec -T app php bin/console prestashop:module install multisafepayofficial

.PHONY: post-install
post-install:
	docker-compose exec -T app rm -rf /var/www/html/var/cache/dev
	docker-compose exec -T app rm -rf /var/www/html/var/cache/prod
	docker-compose exec -T app chown -R www-data:www-data /var/www/html

.PHONY: phpcs
phpcs:
	docker-compose exec -T --workdir /var/www/html/modules/multisafepayofficial app vendor/bin/phpcs -s --standard=phpcs.xml .

.PHONY: phpunit
phpunit:
	docker-compose exec -T --workdir /var/www/html/modules/multisafepayofficial app vendor/bin/phpunit --testsuite prestashop-unit-tests

.PHONY: phpcbf
phpcbf:
	docker-compose exec -T --workdir /var/www/html/modules/multisafepayofficial app vendor/bin/phpcbf --standard=phpcs.xml .

.PHONY: phpstan
phpstan:
	docker-compose exec -T -e _PS_ROOT_DIR_=./../../ --workdir /var/www/html/modules/multisafepayofficial app vendor/bin/phpstan analyse --configuration=tests/phpstan/phpstan.neon
