<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use MultiSafepay\PrestaShop\Helper\OrderStatusInstaller;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;

class Multisafepay extends PaymentModule
{

    const MULTISAFEPAY_MODULE_VERSION = '5.0.0';

    /**
     * Multisafepay plugin constructor.
     * @todo Check if we need an instance on load admin. Until now, we don`t
     */
    public function __construct()
    {
        $this->name          = 'multisafepay';
        $this->tab           = 'payments_gateways';
        $this->version       = self::MULTISAFEPAY_MODULE_VERSION;
        $this->author        = 'MultiSafepay';
        $this->need_instance = 1;
        $this->bootstrap     = true;
        parent::__construct();

        $this->displayName            = $this->l('MultiSafepay');
        $this->description            = $this->l('MultiSafepay payment plugin for PrestaShop');
        $this->confirmUninstall       = $this->l('Are you sure you want to uninstall MultiSafepay?');
        $this->ps_versions_compliancy = array('min' => '1.7.0', 'max' => _PS_VERSION_);
    }

    /**
     * @return string
     */
    public static function getVersion(): string
    {
        return self::MULTISAFEPAY_MODULE_VERSION;
    }

    /**
     * Install method
     *
     * @return boolean
     */
    public function install(): bool
    {
        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Begin install process');
        }

        if (extension_loaded('curl') == false) {
            LoggerHelper::logAlert('cURL extension is not enabled.');
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $install = parent::install();

        $orderStatusInstaller = new OrderStatusInstaller();
        $orderStatusInstaller->registerMultiSafepayOrderStatuses();

        (new \MultiSafepay\PrestaShop\Helper\Installer())->install();

        return $install &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('actionEmailSendBefore');
    }

    /**
     * Uninstall method
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        (new \MultiSafepay\PrestaShop\Helper\Uninstaller($this))->uninstall();

        return parent::uninstall();
    }

    /**
     * Load the configuration form or process the submitted data
     *
     * @return string
     */
    public function getContent(): string
    {
        $settingsBuilder = new \MultiSafepay\PrestaShop\Builder\SettingsBuilder($this);

        if (((bool)Tools::isSubmit('submitMultisafepayModule')) == true) {
            $settingsBuilder->postProcess();
        }

        return $settingsBuilder->renderForm();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     *
     * @return void
    */
    public function hookBackOfficeHeader(): void
    {
        if ('multisafepay' === $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     *
     * @return void
     */
    public function hookHeader(): void
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * Return payment options available
     * @todo Check according with each setting if the PaymentOption should be loaded. Filters like currency, total, group, etc
     *
     * @param array $params
     * @return array|null
     */
    public function hookPaymentOptions(array $params)
    {
        if (!$this->active) {
            return null;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return null;
        }

        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->get('multisafepay.payment_option_service');
        return $paymentOptionService->getFilteredMultiSafepayPaymentOptions($params['cart']);
    }

    /**
     * Return payment form
     *
     * @param string $gatewayCode
     * @param array $inputs
     * @return false|string
     * @throws SmartyException
     */
    public function getMultiSafepayPaymentOptionForm(string $gatewayCode, array $inputs = array())
    {
        $this->context->smarty->assign(
            array(
                'action'       => $this->context->link->getModuleLink($this->name, 'payment', array(), true),
                'inputs'       => $inputs
            )
        );
        return $this->context->smarty->fetch('module:multisafepay/views/templates/front/form.tpl');
    }

    /**
     * Disable send emails on order confirmation
     *
     * @param  array $params
     * @return bool
     */
    public function hookActionEmailSendBefore(array $params): bool
    {
        if (isset($params['templateVars']['dont_send_email']) &&  $params['templateVars']['dont_send_email'] === true) {
            return false;
        }
        return true;
    }

    /**
     * @param Cart $cart
     * @return bool
     */
    public function checkCurrency(Cart $cart): bool
    {
        $currencyOrder = new Currency($cart->id_currency);
        $currenciesModule = $this->getCurrency($cart->id_currency);
        if (is_array($currenciesModule)) {
            foreach ($currenciesModule as $currencyModule) {
                if ($currencyOrder->id == $currencyModule['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
}
