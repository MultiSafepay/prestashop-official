<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

// PrestaShop dependencies
$prestashopDirectory = dirname(__DIR__, 4);
require_once $prestashopDirectory . '/config/config.inc.php';
require_once $prestashopDirectory . '/autoload.php';
require_once $prestashopDirectory . '/vendor/autoload.php';

if (!defined('PS_INSTALLATION_IN_PROGRESS')) {
    define('PS_INSTALLATION_IN_PROGRESS', true);
}

// Load MultiSafepay dependencies.
$multisafepayModuleDirectory = dirname(__DIR__, 2);
require_once $multisafepayModuleDirectory . '/vendor/autoload.php';

// Ensure a minimal controller is available in Context for third-party hooks during tests.
$context = Context::getContext();
if (null === $context->controller) {
    $context->controller = new stdClass();
}

if (!property_exists($context->controller, 'php_self')) {
    $context->controller->php_self = 'phpunit';
}

if (!property_exists($context->controller, 'controller_name')) {
    $context->controller->controller_name = 'phpunit';
}

if (!property_exists($context->controller, 'controller_type')) {
    $context->controller->controller_type = 'admin';
}

if (!property_exists($context->controller, 'module')) {
    $context->controller->module = null;
}

if (!property_exists($context->controller, 'ajax')) {
    $context->controller->ajax = false;
}

if (!property_exists($context->controller, 'ssl')) {
    $context->controller->ssl = false;
}

// Load the main module file where MultisafepayOfficial class is defined
require_once $multisafepayModuleDirectory . '/multisafepayofficial.php';
