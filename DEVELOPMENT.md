# Development guidelines
This extension has been developed by MultiSafepay following the development guidelines of PrestaShop.

## About the local environment for development
* The local environment of development is based on Docker.

## Setting the environment for development

* Open the terminal.
* Create/Go to the application directory.
* Clone this repository.
```
git clone git@github.com:MultiSafepay/PrestaShop-internal.git ./
```
* Rename the file .env.dist to .env
* Change the values in the .env file to meet your expectations
* Execute the following docker compose command
```
docker compose up -d
```
* Wait a couple of minutes if this is the first time you are executing this project
* Install composer dependencies
```
docker exec --workdir /var/www/html/modules/multisafepay prestashop-1.7 composer install
```
* Now you can access to the browser to the following applications:
    * Prestashop 1.7 (PHP 7.3): ${PS_DOMAIN}:8081*
    * Mailhog: localhost:8025
    
<small>*${PS_DOMAIN} is defined in .env file</small>
    
* To connect MySQL server using any MySQL Client. 
    * Port: 33060
    * User: ${DB_USER}*
    * Password ${DB_PASSWD}*
    * Host: db

<small>*${DB_USER} and ${DB_PASSWD} is defined in .env file</small>
    
* To configure SMTP within Prestashop navigating to Advanced Parameters > E-mail: 
    * Select "Set my own SMTP parameters".
    * Set the following values: 
        * Mail domain name: ${PS_DOMAIN}*
        * SMTP server: mailhog
        * SMTP username:
        * SMTP password: 
        * Encryption: none
        * Port 1025

<small>*${PS_DOMAIN} is defined in .env file</small>

* Is possible to run PHPCS tests executing the following command: 
```
docker exec -e XDEBUG_MODE=off --workdir /var/www/html/modules/multisafepay prestashop-1.7 vendor/bin/phpcs -s --standard=phpcs.xml .
```

* Is possible to run PHPUnit tests executing the following command:
```
docker exec -e XDEBUG_MODE=off --workdir /var/www/html/modules/multisafepay prestashop-1.7 vendor/bin/phpunit --testsuite prestashop-unit-tests
```


