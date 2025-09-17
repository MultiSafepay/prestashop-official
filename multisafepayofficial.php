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
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Helper\Installer;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\OrderMessageHelper;
use MultiSafepay\PrestaShop\Helper\OrderRequestBuilderHelper;
use MultiSafepay\PrestaShop\Helper\PathHelper;
use MultiSafepay\PrestaShop\Helper\PaymentMethodConfigHelper;
use MultiSafepay\PrestaShop\Helper\Uninstaller;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\RefundService;
use MultiSafepay\PrestaShop\Services\SdkService;
use Psr\Http\Client\ClientExceptionInterface;
use MultiSafepay\PrestaShop\Adapter\ContextAdapter;

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
        $this->version       = '6.0.1';
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
        } catch (PrestaShopException $exception) {
            LoggerHelper::logException(
                'error',
                $exception
            );
        }
        return parent::uninstall();
    }

    /**
     *  Load the configuration form from the admin panel
     *
     * @return string
     * @throws Exception
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
        // Initialize PathHelper only when needed for admin assets
        if (!PathHelper::isInitialized()) {
            PathHelper::initialize($this->_path);
        }

        // Using PathHelper instead of $this->_path for consistency and reusability
        $this->context->controller->addCSS(PathHelper::getAssetPath('multisafepay-icon.css'));
        if ('multisafepayofficial' === $this->name) {
            $this->context->controller->addJS(PathHelper::getAssetPath('dragula.js'));
            $this->context->controller->addJS(PathHelper::getAssetPath('admin.js'));
            $this->context->controller->addCSS(PathHelper::getAssetPath('back.css'));
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

        // Initialize PathHelper only when needed for frontend assets
        if (!PathHelper::isInitialized()) {
            PathHelper::initialize($this->_path);
        }

        $this->context->controller->registerStylesheet(
            'module-multisafepay-styles',
            PathHelper::getAssetPath('front.css'),
            [
                'priority' => 2
            ]
        );

        $this->context->controller->registerJavascript(
            'module-multisafepay-javascript',
            PathHelper::getAssetPath('front.js'),
            [
                'priority' => 200
            ]
        );

        $paymentOptionService = new PaymentOptionService($this);

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

        $paymentOptionService = new PaymentOptionService($this);
        return $paymentOptionService->getFilteredMultiSafepayPaymentOptions(
            $params['cart'],
            $params['cart']->id_lang ?: null
        );
    }

    /**
     * Return the payment form
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
                'customerId'    => $this->context->customer->id ?? 0,
            ]
        );
        return $this->context->smarty->fetch('module:multisafepayofficial/views/templates/front/form.tpl');
    }

    /**
     * @param array $params
     * @return void
     * @throws SmartyException
     */
    public function hookActionEmailAddAfterContent(array &$params): void
    {
        if ($params['template'] !== 'order_conf' || empty($this->paymentUrlEmailHook)) {
            return;
        }

        $paymentLinkText = $this->l('Payment link: ');
        $paymentUrl = $this->paymentUrlEmailHook;

        // Assign variables to Smarty
        $this->context->smarty->assign([
            'payment_url' => $paymentUrl,
            'payment_link_text' => $paymentLinkText
        ]);

        // HTML template rendering
        $paymentLinkHtml = $this->context->smarty->fetch(
            'module:multisafepayofficial/views/templates/hook/payment_link_email_html.tpl'
        );

        // Plain text template rendering
        $paymentLinkTxt = $this->context->smarty->fetch(
            'module:multisafepayofficial/views/templates/hook/payment_link_email_txt.tpl'
        );

        // Replace HTML
        $replacement = '{payment}<br />' . $paymentLinkHtml . '</div>';
        $params['template_html'] = str_replace('{payment}</div>', $replacement, $params['template_html']);

        // Replace plain text
        $replacementTxt = 'Payment: {payment}' . "\n" . $paymentLinkTxt;
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
        $isLoggedInAdminArea = false;
        if ($isAdminArea) {
            $employee = ContextAdapter::getEmployee($this->context);
            $isLoggedInAdminArea = !empty($employee) && $employee->isLoggedBack();
        }
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
            $paymentOptionService = new PaymentOptionService($this);
            $paymentOption = $paymentOptionService->getMultiSafepayPaymentOption('');

            if (!$paymentOption) {
                $paymentOption = new BasePaymentOption(
                    PaymentMethodConfigHelper::createDefaultPaymentMethod(),
                    $this
                );
            }

            $orderRequestBuilder = OrderRequestBuilderHelper::create($this);
            $orderRequest = $orderRequestBuilder->build($cart, $customer, $paymentOption, $order);

            try {
                $sdkService = new SdkService();
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

        if (!$params['OrderInvoice']->id) {
            return;
        }

        /** @var OrderInvoice $orderInvoice */
        $orderInvoice = OrderInvoice::getInvoiceByNumber($params['OrderInvoice']->id);

        $orderInvoiceNumber = $orderInvoice->getInvoiceNumberFormatted($order->id_lang, $order->id_shop);

        // Update order with invoice shipping information
        $sdkService = new SdkService();

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

        $sdkService = new SdkService();

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
        $refundService = new RefundService($this, new SdkService(), new PaymentOptionService($this));

        /** @var Order $order */
        $order = $params['order'];
        $productList = $params['productList'];

        if (!$refundService->isAllowedToRefund($order, $productList)) {
            return false;
        }

        return $refundService->processRefund($order, $productList);
    }

    /**
     * Display account block with a link to MultiSafepay tokens list.
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
    public function hasSetApiKey(): bool
    {
        $sdkService = new SdkService();

        $apiKey = $sdkService->getApiKey();
        return !empty($apiKey);
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return false;
    }

    /**
     * Get the module's context for use in services and builders.
     *
     * @return Context
     */
    public function getModuleContext(): Context
    {
        return $this->context;
    }
}
