<?php declare(strict_types=1);

// PrestaShop dependencies
$prestashopDirectory = dirname(__DIR__, 4);
require_once $prestashopDirectory . '/config/config.inc.php';
require_once $prestashopDirectory . '/autoload.php';
require_once $prestashopDirectory . '/vendor/autoload.php';

// Load MultiSafepay dependencies.
$multisafepayModuleDirectory = dirname(__DIR__, 2);
require_once $multisafepayModuleDirectory . '/vendor/autoload.php';
