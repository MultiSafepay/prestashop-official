# Development guidelines
This extension has been developed by MultiSafepay following the development guidelines of PrestaShop.

## Installation

1. `git clone git@github.com:MultiSafepay/PrestaShop-internal.git`
2. `cp .env.example .env` 
3. Make the required configuration changes in the .env file
4. `docker compose up -d`
5. `make install`

## Code quality
 - phpcs: `make phpcs`
 - PHPUnit: `make phpunit`
 - phpcbf: `make phpcbf`
 - PHPStan: `make phpstan`
