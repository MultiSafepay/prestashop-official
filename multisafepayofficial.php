<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
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

require _PS_MODULE_DIR_ . 'multisafepayofficial/vendor/autoload.php';

use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Helper\Installer;
use MultiSafepay\PrestaShop\Helper\Uninstaller;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\RefundService;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Psr\Http\Client\ClientExceptionInterface;

class MultisafepayOfficial extends PaymentModule
{

    /**
     * Multisafepay plugin constructor.
     */
    public function __construct()
    {
        $this->name          = 'multisafepayofficial';
        $this->tab           = 'payments_gateways';
        $this->version       = '5.1.0';
        $this->author        = 'MultiSafepay';
        $this->need_instance = 0;
        $this->bootstrap     = true;
        parent::__construct();

        $this->displayName            = $this->l('MultiSafepay');
        $this->description            = $this->l('MultiSafepay payment plugin for PrestaShop');
        $this->confirmUninstall       = $this->l('Are you sure you want to uninstall MultiSafepay?');
        $this->ps_versions_compliancy = ['min' => '1.7.6', 'max' => _PS_VERSION_];
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install(): bool
    {
        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            LoggerHelper::logInfo('Begin install process');
        }

        if (false === extension_loaded('curl')) {
            LoggerHelper::logAlert('cURL extension is not enabled.');
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $install = parent::install();

        (new Installer($this))->install();

        return $install &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('actionSetInvoice') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('actionOrderSlipAdd') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('actionEmailSendBefore');
    }

    /**
     * Uninstall method
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        (new Uninstaller($this))->uninstall();

        return parent::uninstall();
    }

    /**
     * @return string
     * @throws PrestaShopException
     */
    public function getContent(): string
    {
        $settingsBuilder = new SettingsBuilder($this);

        if (true === Tools::isSubmit('submitMultisafepayOfficialModule')) {
            $settingsBuilder->postProcess();
            return $settingsBuilder->renderForm(true);
        }

        return $settingsBuilder->renderForm();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     *
     * @return void
     */
    public function hookActionAdminControllerSetMedia(array $params): void
    {
        $this->context->controller->addCSS($this->_path.'views/css/multisafepay-icon.css');
        if ('multisafepayofficial' === $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/dragula.js');
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     *
     */
    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if ($this->context->controller->php_self !== 'order') {
            return;
        }

        if (!$this->hasSetApiKey()) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'module-multisafepay-styles',
            'modules/multisafepayofficial/views/css/front.css'
        );
        $this->context->controller->registerJavascript(
            'module-multisafepay-javascript',
            'modules/multisafepayofficial/views/js/front.js',
            [
                'priority' => 200,
            ]
        );

        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->get('multisafepay.payment_option_service');

        $paymentOptions = $paymentOptionService->getActivePaymentOptions();
        /** @var BasePaymentOption $paymentOption */
        foreach ($paymentOptions as $paymentOption) {
            $paymentOption->registerJavascript($this->context);
            $paymentOption->registerCss($this->context);
        }
    }

    /**
     * Return payment options available
     *
     * @param array $params
     * @return array|null
     */
    public function hookPaymentOptions(array $params): ?array
    {
        if (!$this->active) {
            return null;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return null;
        }

        if (!$this->hasSetApiKey()) {
            LoggerHelper::logAlert('API Key has not been set up properly');
            return null;
        }

        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->get('multisafepay.payment_option_service');
        return $paymentOptionService->getFilteredMultiSafepayPaymentOptions($params['cart']);
    }

    /**
     * Return payment form
     *
     * @param BasePaymentOption $paymentOption
     * @return false|string
     * @throws SmartyException
     */
    public function getMultiSafepayPaymentOptionForm(BasePaymentOption $paymentOption)
    {
        $this->context->smarty->assign(
            [
                'action'        => $this->context->link->getModuleLink($this->name, 'payment', [], true),
                'paymentOption' => $paymentOption,
            ]
        );
        return $this->context->smarty->fetch('module:multisafepayofficial/views/templates/front/form.tpl');
    }

    /**
     * Disable send emails on order confirmation
     *
     * @param  array $params
     * @return bool
     */
    public function hookActionEmailSendBefore(array $params): bool
    {
        if (!isset($params['templateVars']['send_email'])) {
            return true;
        }

        return !(empty($params['templateVars']['send_email']));
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
                if ($currencyOrder->id === (int)$currencyModule['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Set MultiSafepay transaction as invoiced
     *
     * @param array $params
     *
     * @throws ClientExceptionInterface
     */
    public function hookActionSetInvoice(array $params): void
    {
        if (!(bool)Configuration::get('PS_INVOICE')) {
            return;
        }

        /** @var Order $order */
        $order = $params['Order'];

        if (!$order->module || $order->module !== 'multisafepayofficial') {
            return;
        }

        if (!$order->hasInvoice()) {
            return;
        }

        /** @var OrderInvoice $orderInvoice */
        $orderInvoice = OrderInvoice::getInvoiceByNumber($params['OrderInvoice']->id);
        $orderInvoiceNumber = $orderInvoice->getInvoiceNumberFormatted($order->id_lang, $order->id_shop);

        // Update order with invoice shipping information
        /** @var SdkService $sdkService */
        $sdkService         = $this->get('multisafepay.sdk_service');
        $transactionManager = $sdkService->getSdk()->getTransactionManager();
        $updateOrder        = new UpdateRequest();
        $updateOrder->addData(['invoice_id'  => $orderInvoiceNumber]);
        $transactionManager->update($order->reference, $updateOrder);
    }

    /**
     * Set MultiSafepay transaction as shipped
     *
     * @param array $params
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ClientExceptionInterface
     */
    public function hookActionOrderStatusPostUpdate(array $params): void
    {

        if ((int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_TRIGGER_SHIPPED') !== $params['newOrderStatus']->id) {
            return;
        }

        $order = new Order((int)$params['id_order']);

        if (!$order->module || $order->module !== 'multisafepayofficial') {
            return;
        }

        // Update order with invoice shipping information
        /** @var SdkService $sdkService */
        $sdkService         = $this->get('multisafepay.sdk_service');
        $transactionManager = $sdkService->getSdk()->getTransactionManager();
        $updateOrder        = new UpdateRequest();
        $updateOrder->addData(
            [
                'status' => 'shipped',
                'tracktrace_code' => $order->getWsShippingNumber(),
                'carrier' => (new Carrier((int)$order->id_carrier))->name,
                'ship_date' => date('Y-m-d H:i:s')
            ]
        );
        $transactionManager->update($order->reference, $updateOrder);
    }

    /**
     * Process the refund action
     *
     * @param array $params
     *
     * @return bool
     * @throws ClientExceptionInterface
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionOrderSlipAdd(array $params): bool
    {
        /** @var RefundService $refundService */
        $refundService = $this->get('multisafepay.refund_service');

        /** @var Order $order */
        $order = $params['order'];
        $productList = $params['productList'];

        if (!$refundService->isAllowedToRefund($order, $productList)) {
            return false;
        }

        return $refundService->processRefund($order, $productList);
    }

    /**
     * Display account block with link to MultiSafepay tokens list.
     *
     * @param array $params
     *
     * @return string
     * @throws SmartyException
     */
    public function hookDisplayCustomerAccount(array $params): string
    {
        return $this->display(__FILE__, 'tokens.tpl');
    }

    /**
     * Used to display extra information by third party modules.
     *
     * @param array $params
     * @return bool
     */
    public function hookPaymentReturn(array $params)
    {
        return false;
    }

    private function hasSetApiKey(): bool
    {
        /** @var SdkService $sdkService */
        $sdkService = $this->get('multisafepay.sdk_service');
        $apiKey = $sdkService->getApiKey();

        return !empty($apiKey);
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return false;
    }
}
