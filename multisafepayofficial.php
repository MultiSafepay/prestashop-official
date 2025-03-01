<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require _PS_MODULE_DIR_ . 'multisafepayofficial/vendor/autoload.php';

use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Builder\OrderRequestBuilder;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Helper\Installer;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\OrderMessageHelper;
use MultiSafepay\PrestaShop\Helper\Uninstaller;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\RefundService;
use MultiSafepay\PrestaShop\Services\SdkService;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class MultisafepayOfficial extends PaymentModule
{

    /**
     * @var string
     */
    private $paymentUrlEmailHook = '';

    /**
     * Multisafepay plugin constructor.
     */
    public function __construct()
    {
        $this->name          = 'multisafepayofficial';
        $this->tab           = 'payments_gateways';
        $this->version       = '5.17.0';
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
     */
    public function install(): bool
    {
        LoggerHelper::log(
            'info',
            'Begin install process',
            true
        );

        if (false === extension_loaded('curl')) {
            LoggerHelper::log(
                'alert',
                'cURL extension is not enabled.'
            );
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $install = parent::install();
        if (!$install) {
            LoggerHelper::log(
                'alert',
                'Parent install failed.'
            );
            return false;
        }

        (new Installer($this))->install();

        $hooks = [
            'actionFrontControllerSetMedia',
            'actionAdminControllerSetMedia',
            'paymentOptions',
            'actionSetInvoice',
            'actionOrderStatusPostUpdate',
            'actionOrderSlipAdd',
            'displayCustomerAccount',
            'actionEmailSendBefore',
            'actionValidateOrder',
            'actionEmailAddAfterContent'
        ];

        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Uninstall method
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        try {
            (new Uninstaller($this))->uninstall();
        } catch (PrestaShopException|PrestaShopDatabaseException $exception) {
            LoggerHelper::logException(
                'error',
                $exception
            );
        }
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
            $result = $settingsBuilder->postProcess();
            return $settingsBuilder->renderForm($result);
        }

        return $settingsBuilder->renderForm();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     *
     * @param array $params
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
     * @param array $params
     * @return void
     * @throws Exception
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
            'modules/multisafepayofficial/views/css/front.css',
            [
                'priority' => 2
            ]
        );

        $this->context->controller->registerJavascript(
            'module-multisafepay-javascript',
            'modules/multisafepayofficial/views/js/front.js',
            [
                'priority' => 200
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
     * @throws SmartyException
     * @throws Exception
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
            LoggerHelper::log(
                'alert',
                'API Key has not been set up properly',
                false,
                null,
                $params['cart']->id ?? null
            );
            return null;
        }

        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->get('multisafepay.payment_option_service');
        return $paymentOptionService->getFilteredMultiSafepayPaymentOptions($params['cart']);
    }

    /**
     * Return payment form
     *
     * @param BasePaymentOption  $paymentOption
     * @return ?string
     * @throws SmartyException
     */
    public function getMultiSafepayPaymentOptionForm(
        BasePaymentOption $paymentOption
    ): ?string {
        $this->context->smarty->assign(
            [
                'action'        => $this->context->link->getModuleLink($this->name, 'payment', [], true),
                'paymentOption' => $paymentOption,
            ]
        );
        return $this->context->smarty->fetch('module:multisafepayofficial/views/templates/front/form.tpl');
    }

    /**
     * @param array $params
     * @return void
     */
    public function hookActionEmailAddAfterContent(array &$params): void
    {
        if ($params['template'] !== 'order_conf' || empty($this->paymentUrlEmailHook)) {
            return;
        }

        $paymentLinkText = $this->l('Payment link: ');
        $paymentUrl = $this->paymentUrlEmailHook;

        // Replace HTML payment link
        $span = '<span class="label" style="font-weight: 700;">' . $paymentLinkText . '</span> ';
        $link = '<a style="word-break:break-all;" href="' . $paymentUrl . '">' . $paymentUrl . '</a>';
        $replacement = '{payment}<br />' . $span . $link . '</div>';
        $params['template_html'] = str_replace('{payment}</div>', $replacement, $params['template_html']);

        // Replace text payment link
        $replacementTxt = 'Payment: {payment}' . "\n" . $paymentLinkText . $paymentUrl;
        $params['template_txt'] = str_replace('Payment: {payment}', $replacementTxt, $params['template_txt']);
    }

    /**
     * Disable send emails on order confirmation
     *
     * @param array $params
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
     * @param array $params
     * @return void
     * @throws ClientExceptionInterface
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */
    public function hookActionValidateOrder(array $params): void
    {
        $cart = $params['cart'];
        $isAdminArea = defined('_PS_ADMIN_DIR_');
        $isLoggedInAdminArea = !empty(Context::getContext()->employee) &&
            Context::getContext()->employee->isLoggedBack();
        $isNotGuest = (string)$cart->id_guest === '0';

        if (empty($cart) || !$isAdminArea || !$isLoggedInAdminArea || !$isNotGuest) {
            return;
        }

        $order = $params['order'];
        if ($order && ((string)$order->module !== 'multisafepayofficial')) {
            return;
        }

        $customer = $params['customer'];
        $paymentUrl = false;

        // Order is created from the back-end
        if ($customer) {
            /** @var PaymentOptionService $paymentOptionService */
            $paymentOptionService = $this->get('multisafepay.payment_option_service');
            $paymentOption = $paymentOptionService->getMultiSafepayPaymentOption('');

            /** @var OrderRequestBuilder $orderRequestBuilder */
            $orderRequestBuilder = $this->get('multisafepay.order_request_builder');
            $orderRequest = $orderRequestBuilder->build($cart, $customer, $paymentOption, $order);

            try {
                /** @var SdkService $sdkService */
                $sdkService         = $this->get('multisafepay.sdk_service');
                $transactionManager = $sdkService->getSdk()->getTransactionManager();
                $transaction        = $transactionManager->create($orderRequest);
                $paymentUrl         = $transaction->getPaymentUrl();
            } catch (ApiException $apiException) {
                LoggerHelper::logException(
                    'error',
                    $apiException,
                    'Error while trying to set payment url',
                    $order ? (string)$order->id : null,
                    $cart->id ?? null
                );
            }

            if ($paymentUrl) {
                $message = $this->l('Payment link: ') . $paymentUrl;
                $this->paymentUrlEmailHook = $paymentUrl;
                OrderMessageHelper::addMessage($order, $message);
                LoggerHelper::log(
                    'info',
                    $message,
                    true,
                    $order ? (string)$order->id : null,
                    $cart->id ?? null
                );
            }
        }
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
     * @throws Exception
     */
    public function hookActionSetInvoice(array $params): void
    {
        if (!Configuration::get('PS_INVOICE')) {
            return;
        }

        /** @var Order $order */
        $order = $params['Order'];

        if (!$order->module || ($order->module !== 'multisafepayofficial')) {
            return;
        }

        if (!$order->hasInvoice()) {
            return;
        }

        /** @var OrderInvoice $orderInvoice */
        $orderInvoice = OrderInvoice::getInvoiceByNumber($params['OrderInvoice']->id);

        if (!$orderInvoice) {
            return;
        }

        $orderInvoiceNumber = $orderInvoice->getInvoiceNumberFormatted($order->id_lang, $order->id_shop);

        // Update order with invoice shipping information
        try {
            /** @var SdkService $sdkService */
            $sdkService = $this->get('multisafepay.sdk_service');
        } catch (ServiceNotFoundException $serviceNotFoundException) {
            LoggerHelper::logException(
                'alert',
                $serviceNotFoundException,
                'Error when try to get the Sdk Service'
            );
            $sdkService = new SdkService();
        }

        $transactionManager = $sdkService->getSdk()->getTransactionManager();
        $updateOrder        = new UpdateRequest();
        $updateOrder->addData(['invoice_id'  => $orderInvoiceNumber]);

        $orderId = $order->id_cart;
        if (Configuration::get('MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT')) {
            $orderId = $order->reference;
        }

        try {
            $transactionManager->update((string) $orderId, $updateOrder);
        } catch (ApiException $apiException) {
            LoggerHelper::logException(
                'alert',
                $apiException,
                'Error when try to set the transaction as invoiced',
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
            return;
        }
    }

    /**
     * Set MultiSafepay transaction as shipped
     *
     * @param array $params
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        if ((int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_TRIGGER_SHIPPED') !== $params['newOrderStatus']->id) {
            return;
        }

        $order = new Order((int)$params['id_order']);
        if (!$order->module || ($order->module !== 'multisafepayofficial')) {
            return;
        }

        try {
            /** @var SdkService $sdkService */
            $sdkService = $this->get('multisafepay.sdk_service');
        } catch (ServiceNotFoundException $serviceNotFoundException) {
            LoggerHelper::logException(
                'alert',
                $serviceNotFoundException,
                'Error when try to get the Sdk Service'
            );
            $sdkService = new SdkService();
        }

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

        $orderId = $order->id_cart;
        if (Configuration::get('MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT')) {
            $orderId = $order->reference;
        }

        try {
            $transactionManager->update((string) $orderId, $updateOrder);
        } catch (ApiException $apiException) {
            LoggerHelper::logException(
                'alert',
                $apiException,
                'Error when try to set the transaction as shipped',
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
            return;
        }
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
     * @throws Exception
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
     */
    public function hookDisplayCustomerAccount(array $params): string
    {
        return $this->display(__FILE__, 'tokens.tpl');
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function hasSetApiKey(): bool
    {
        try {
            /** @var SdkService $sdkService */
            $sdkService = $this->get('multisafepay.sdk_service');
            $apiKey = $sdkService->getApiKey();
            return !empty($apiKey);
        } catch (ApiException $apiException) {
            LoggerHelper::logException(
                'alert',
                $apiException,
                'Error when try to get the Api Key',
                null,
                $this->context->cart->id ?? null
            );
            return false;
        }
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return false;
    }
}
