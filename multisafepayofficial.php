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

use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\UpdateRequest;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Adapter\ContextAdapter;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Helper\Installer;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\ManualCaptureHelper;
use MultiSafepay\PrestaShop\Helper\OrderMessageHelper;
use MultiSafepay\PrestaShop\Helper\OrderRequestBuilderHelper;
use MultiSafepay\PrestaShop\Helper\PathHelper;
use MultiSafepay\PrestaShop\Helper\PaymentMethodConfigHelper;
use MultiSafepay\PrestaShop\Helper\Uninstaller;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\RefundService;
use MultiSafepay\PrestaShop\Services\SdkService;
use PrestaShop\PrestaShop\Core\Exception\TypeException;
use Psr\Http\Client\ClientExceptionInterface;

class MultisafepayOfficial extends PaymentModule
{

    /**
     * Prevent rendering the PS 1.7 capture button multiple times
     * when multiple admin order hooks are executed on the same page.
     *
     * @var bool
     */
    private static $legacyAdminOrderCaptureButtonRendered = false;

    /**
     * Simple in-request cache for fetched MultiSafepay transactions.
     *
     * @var array<string, mixed>
     */
    private static $mspTransactionCache = [];

    /**
     * @var string
     */
    private $paymentUrlEmailHook = '';

    /**
     * Multisafepay plugin constructor.
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name          = 'multisafepayofficial';
        $this->tab           = 'payments_gateways';
        $this->version       = '6.3.0';
        $this->author        = 'MultiSafepay';
        $this->need_instance = 0;
        $this->bootstrap     = true;
        parent::__construct();

        $this->displayName            = $this->l('MultiSafepay');
        $this->description            = $this->l('MultiSafepay payment plugin for PrestaShop');
        $this->confirmUninstall       = $this->l('Are you sure you want to uninstall MultiSafepay?');
        $this->ps_versions_compliancy = ['min' => '1.7.6', 'max' => _PS_VERSION_];

        // Ensure the authorized status exists during module initialization
        // This prevents issues when manual capture orders are created
        ManualCaptureHelper::ensureAuthorizedStatusExists();

        // Ensure the partially captured status exists during module initialization
        ManualCaptureHelper::ensurePartialCapturedStatusExists();

        // TEMPORARY: Register new hooks if not already registered (for existing installations)
        // TODO: Remove this after the next version upgrade script is implemented
        $this->registerNewHooksIfNeeded();
    }

    /**
     * TEMPORARY: Register new hooks and tabs for existing installations
     *
     * This method ensures that new hooks added in recent versions are registered
     * for modules that were installed before these hooks were added.
     * This should be removed once a proper upgrade script is implemented.
     *
     * @return void
     * @throws PrestaShopDatabaseException
     */
    private function registerNewHooksIfNeeded(): void
    {
        // Only run if the module is installed
        if (!$this->id) {
            return;
        }

        // Register order-view hooks depending on PS version
        // PS >= 1.7.7: modern hook (Symfony order page action bar buttons)
        // PS < 1.7.7: legacy order page inject into the top action bar
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $hookName = 'actionGetAdminOrderButtons';
        } else {
            $hookName = 'displayBackOfficeOrderActions';
            $this->ensureHookExists($hookName);
        }
        $hookId = Hook::getIdByName($hookName);
        if ($hookId && !$this->isRegisteredInHook($hookName)) {
            $this->registerHook($hookName);
        }

        // Install capture tab if not exists
        $captureTabId = Tab::getIdFromClassName('AdminMultisafepayOfficialCapture');
        if (!$captureTabId) {
            $captureTab = new Tab();
            $captureTab->class_name = 'AdminMultisafepayOfficialCapture';
            $captureTab->id_parent = -1; // Hidden tab
            $captureTab->module = 'multisafepayofficial';
            $captureTab->active = true;
            $languages = Language::getLanguages();
            foreach ($languages as $language) {
                $captureTab->name[$language['id_lang']] = 'MultiSafepay Capture';
            }
            try {
                $captureTab->add();
            } catch (Exception $exception) {
                // Silently fail - tab registration is not critical
            }
        }
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
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

        try {
            (new Installer($this))->install();
        } catch (Throwable $exception) {
            $installResourcesErrorMessage = 'Module installation failed while creating MultiSafepay resources.';

            LoggerHelper::logException(
                'alert',
                $exception,
                $installResourcesErrorMessage
            );
            $this->_errors[] = $installResourcesErrorMessage;

            return false;
        }

        // Some legacy hooks used in templates may not exist in older DBs.
        if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            $this->ensureHookExists('displayBackOfficeOrderActions');
        }

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
            'actionEmailAddAfterContent',
        ];

        // Order-view button hooks differ across PS versions
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $hooks[] = 'actionGetAdminOrderButtons';
        } else {
            $hooks[] = 'displayBackOfficeOrderActions';
        }

        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a hook if it does not exist in DB (useful for legacy installations).
     *
     * @param string $hookName
     * @return void
     * @throws PrestaShopDatabaseException
     */
    private function ensureHookExists(string $hookName): void
    {
        if (Hook::getIdByName($hookName)) {
            return;
        }

        $hook = new Hook();
        $hook->name = $hookName;
        $hook->title = $hookName;
        $hook->description = $hookName;

        try {
            $hook->add();
        } catch (Exception $exception) {
            // Non-critical: if we can't create the hook, the feature will fall back to other hooks.
        }
    }

    /**
     * Resolve the current order id from the request context.
     *
     * - PS < 1.7.7 (legacy): id_order is in query params.
     * - PS >= 1.7.7 (Symfony): orderId is a route attribute.
     *
     * @return int
     */
    private function resolveAdminOrderIdFromRequest(): int
    {
        if (Tools::getValue('id_order')) {
            return (int)Tools::getValue('id_order');
        }

        if (Tools::getValue('orderId')) {
            return (int)Tools::getValue('orderId');
        }

        // Symfony pages (PS 1.7.7+ / 8+): orderId is in Request attributes, not GET.
        if (class_exists('PrestaShop\\PrestaShop\\Adapter\\SymfonyContainer')) {
            $container = \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance();
            if (method_exists($container, 'has') && $container->has('request_stack')) {
                $requestStack = $container->get('request_stack');
                if (method_exists($requestStack, 'getCurrentRequest')) {
                    $request = $requestStack->getCurrentRequest();
                    if ($request) {
                        $orderId = (int)$request->attributes->get('orderId', 0);
                        if ($orderId) {
                            return $orderId;
                        }

                        $orderId = (int)$request->attributes->get('id_order', 0);
                        if ($orderId) {
                            return $orderId;
                        }
                    }
                }
            }
        }

        // Fallback: parse from URL path segments (e.g., /orders/{orderId}/view, /sell/orders/{orderId}/status, ...)
        if (!empty($_SERVER['REQUEST_URI'])) {
            $path = parse_url((string)$_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $segments = explode('/', trim($path, '/'));
                $segmentsCount = count($segments);
                for ($i = 0; $i < $segmentsCount - 1; $i++) {
                    if ($segments[$i] === 'orders' && ctype_digit((string)$segments[$i + 1])) {
                        return (int)$segments[$i + 1];
                    }
                }
            }
        }

        return 0;
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
        } catch (PrestaShopException $prestaShopException) {
            LoggerHelper::logException(
                'error',
                $prestaShopException,
                'Error during uninstall'
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionAdminControllerSetMedia(array $params): void
    {
        // Initialize PathHelper only when needed for admin assets
        if (!PathHelper::isInitialized()) {
            PathHelper::initialize($this->_path);
        }

        // Using PathHelper instead of $this->_path for consistency and reusability
        $this->context->controller->addCSS(PathHelper::getAssetPath('multisafepay-icon.css'));

        // Load full admin assets on MultiSafepay configuration pages
        if ('multisafepayofficial' === $this->name) {
            $this->context->controller->addJS(PathHelper::getAssetPath('dragula.js'));
            $this->context->controller->addJS(PathHelper::getAssetPath('admin.js'));
            $this->context->controller->addCSS(PathHelper::getAssetPath('back.css'));
        }

        // PS < 1.7.7: legacy positioning for Capture button inside the AdminOrders action bar
        if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            $controllerName = '';
            if (isset($this->context->controller->controller_name)) {
                $controllerName = (string)$this->context->controller->controller_name;
            } elseif (Tools::getValue('controller')) {
                $controllerName = (string)Tools::getValue('controller');
            }

            if ('AdminOrders' === $controllerName) {
                $this->context->controller->addCSS(PathHelper::getAssetPath('admin-order-actions.css'));
            }
        }

        // Warn when leaving Authorized status without capturing (admin order view).
        $orderId = $this->resolveAdminOrderIdFromRequest();

        if ($orderId > 0) {
            $order = new Order($orderId);
            if ($order->module === 'multisafepayofficial') {
                $authorizedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED');
                if ($authorizedStatusId && (int)$order->current_state === $authorizedStatusId) {
                    $this->context->controller->addJS(PathHelper::getAssetPath('admin-order-status-warning.js'));

                    if (class_exists('Media')) {
                        Media::addJsDef([
                            'mspCaptureWarning' => [
                                'enabled' => true,
                                'orderId' => $orderId,
                                'authorizedStateId' => $authorizedStatusId,
                                'currentStateId' => (int)$order->current_state,
                                'message' => $this->l('This order payment is authorized but not captured. If you
                                 change the status now, the Capture action will no longer be available until you revert
                                  to Authorized. Do you want to continue?'),
                            ],
                        ]);
                    }
                }
            }
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
                $params['cart']->id ?: null
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

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DISABLE_BACKOFFICE_ORDER_PAYMENT_LINK')) {
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
                    (string)$order->id ?: null,
                    $cart->id ?: null
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
                    (string)$order->id ?: null,
                    $cart->id ?: null
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

        /* @phpstan-ignore-next-line */
        if (empty($orderInvoice) || !$orderInvoice->id) {
            return;
        }

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
     * Set MultiSafepay transaction as shipped and handle captures when order status changes
     *
     * @param array $params
     * @throws PrestaShopDatabaseException
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function hookActionOrderStatusPostUpdate(array $params): void
    {
        $newOrderStatusId = (int)$params['newOrderStatus']->id;
        $configuredShippedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_TRIGGER_SHIPPED');
        $cancelledStatusId = (int)Configuration::get('PS_OS_CANCELED');

        $orderId = (int)$params['id_order'];
        $order = new Order($orderId);

        if (!$order->module || ($order->module !== 'multisafepayofficial')) {
            return;
        }

        $sdkService = new SdkService();

        $sdk = $sdkService->getSdk();
        if (!$sdk) {
            LoggerHelper::log(
                'alert',
                'SDK is null, cannot process order status update',
                false,
                (string)$orderId ?: null
            );
            return;
        }

        $transactionManager = $sdk->getTransactionManager();
        $orderId = Configuration::get('MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT') ?
            $order->reference : $order->id_cart;

        // Handle order shipped status
        if ($configuredShippedStatusId === $newOrderStatusId) {
            $this->handleOrderShipped($order, $transactionManager, (string)$orderId);
        }

        // Handle order cancelled status
        if ($cancelledStatusId === $newOrderStatusId) {
            $this->handleOrderCancelled($order, $transactionManager, (string)$orderId);
        }
    }

    /**
     * Handle order shipped status update and capture funds if needed
     *
     * This method is triggered when an order status is changed to "Shipped"
     * For manual capture transactions, it will capture the funds and update the order status
     *
     * @param Order $order The order object
     * @param TransactionManager $transactionManager MultiSafepay transaction manager
     * @param string $orderId Order ID
     *
     * @return void
     * @throws ClientExceptionInterface
     */
    private function handleOrderShipped(Order $order, TransactionManager $transactionManager, string $orderId): void
    {
        $trackingNumber = $order->getWsShippingNumber() ?? '';
        $carrierName = (new Carrier((int)$order->id_carrier))->name ?: '';

        try {
            // Now mark the transaction as shipped
            $updateOrder = new UpdateRequest();
            $updateOrder->addData([
                'status' => Transaction::SHIPPED,
                'tracktrace_code' => $trackingNumber,
                'carrier' => $carrierName,
                'ship_date' => date('Y-m-d H:i:s')
            ]);

            $updateRequest = $transactionManager->update($orderId, $updateOrder);
            $responseUpdateRawData = json_decode($updateRequest->getRawData(), false);

            if (!empty($responseUpdateRawData->success)) {
                LoggerHelper::log(
                    'info',
                    "Order set as 'shipped'",
                    false,
                    (string)$order->id ?: null,
                    $order->id_cart ?: null
                );
            } else {
                LoggerHelper::log(
                    'alert',
                    "Order could not be set as 'shipped'. Response data: " .
                    json_encode($responseUpdateRawData),
                    false,
                    (string)$order->id ?: null,
                    $order->id_cart ?: null
                );
            }
        } catch (ApiException $apiException) {
            LoggerHelper::logException(
                'alert',
                $apiException,
                'Error when processing order shipment',
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
        }
    }

    /**
     * Handle order cancelled status
     *
     * @param Order $order The order object
     * @param TransactionManager $transactionManager MultiSafepay transaction manager
     * @param string $orderId Order ID
     *
     * @return void
     * @throws ClientExceptionInterface
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function handleOrderCancelled(Order $order, TransactionManager $transactionManager, string $orderId): void
    {
        try {
            $transaction = $transactionManager->get($orderId);
            $isCancellableStatus = ManualCaptureHelper::isCancellableStatus($transaction);
            $isManualCapture = ManualCaptureHelper::isManualCaptureTransaction($transaction);

            if ($isCancellableStatus && $isManualCapture) {
                $captureRequest = new CaptureRequest();
                $captureRequest->addData([
                    'status' => Transaction::CANCELLED,
                    'reason' => 'Order #' . $order->id . ' using manual capture cancelled',
                    'description' => 'Cancellation processed by MultiSafepay module for PrestaShop' .
                    defined('_PS_VERSION_') ? ' ' . _PS_VERSION_ : ''
                ]);

                try {
                    $captureResponse = $transactionManager->captureReservationCancel($orderId, $captureRequest);
                    $responseCaptureData = $captureResponse->getResponseData();

                    $success = !empty($responseCaptureData['success']);
                    $statusText = $success ? 'successfully' : 'unsuccessfully';
                    $additionalInfo = $success ? ' to release the funds back' : '. Response data: ' .
                        json_encode($responseCaptureData);

                    // Add an order message for manual capture cancellation
                    $message = 'Manual capture was ' . $statusText . ' cancelled' . $additionalInfo;
                    LoggerHelper::log(
                        $success ? 'info' : 'alert',
                        $message,
                        false,
                        (string)$order->id ?: null,
                        $order->id_cart ?: null
                    );
                    OrderMessageHelper::addMessage($order, $message);
                } catch (ApiException $apiException) {
                    // Add an order message for the API exception
                    $message = 'Manual capture payment cancellation failed due to an API error. ' .
                               'Please try again or contact support.';
                    LoggerHelper::logException(
                        'alert',
                        $apiException,
                        $message,
                        (string)$order->id ?: null,
                        $order->id_cart ?: null
                    );
                    OrderMessageHelper::addMessage($order, $message);
                }
            } else {
                // For non-manual capture orders, simply update the transaction status to cancelled
                $updateOrder = new UpdateRequest();
                $updateOrder->addData([
                    'status' => Transaction::CANCELLED,
                    'reason' => 'Order #' . $order->id . ' cancelled',
                    'description' => 'Cancellation processed by MultiSafepay module for PrestaShop' .
                        (defined('_PS_VERSION_') ? ' ' . _PS_VERSION_ : '')
                ]);

                try {
                    $transactionManager->update($orderId, $updateOrder);

                    $message = 'Transaction successfully cancelled';
                    LoggerHelper::log(
                        'info',
                        $message,
                        false,
                        (string)$order->id ?: null,
                        $order->id_cart ?: null
                    );
                    OrderMessageHelper::addMessage($order, $message);
                } catch (ApiException $apiException) {
                    $message = 'Failed to cancel transaction due to an API error. Please try again or contact support.';
                    LoggerHelper::logException(
                        'alert',
                        $apiException,
                        $message,
                        (string)$order->id ?: null,
                        $order->id_cart ?: null
                    );
                    OrderMessageHelper::addMessage($order, $message);
                }
            }
        } catch (ApiException $apiException) {
            LoggerHelper::logException(
                'alert',
                $apiException,
                'Error when getting transaction data',
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
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
     * Display the account block with a link to the MultiSafepay tokens list.
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

    /**
     * Resolve the MultiSafepay order id used by the API for a given PrestaShop order.
     */
    private function getMultiSafepayOrderId(Order $order): ?string
    {
        $mspOrderId = Configuration::get('MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT') ?
            (string)$order->reference : (string)$order->id_cart;

        $mspOrderId = trim($mspOrderId);
        if ($mspOrderId === '' || $mspOrderId === '0') {
            return null;
        }

        return $mspOrderId;
    }

    /**
     * Fetch the MultiSafepay transaction for the provided order.
     *
     * This method uses an in-request (static) cache keyed by the MultiSafepay order id
     * so the API is called at most once per order view/page load. Both successful responses and
     * failures are cached (as `null`) to avoid repeated calls within the same PHP request.
     *
     * @return mixed|null TransactionResponse from SDK or null when not available.
     * @throws ClientExceptionInterface
     */
    private function getMultiSafepayTransaction(Order $order)
    {
        $mspOrderId = $this->getMultiSafepayOrderId($order);
        if (!$mspOrderId) {
            return null;
        }

        if (array_key_exists($mspOrderId, self::$mspTransactionCache)) {
            return self::$mspTransactionCache[$mspOrderId];
        }

        try {
            $sdkService = new SdkService();
            $sdk = $sdkService->getSdk();
            if (!$sdk) {
                self::$mspTransactionCache[$mspOrderId] = null;
                return null;
            }

            $transactionManager = $sdk->getTransactionManager();
            $transaction = $transactionManager->get($mspOrderId);
            self::$mspTransactionCache[$mspOrderId] = $transaction;
            return $transaction;
        } catch (ApiException | Exception $apiException) {
            self::$mspTransactionCache[$mspOrderId] = null;
            return null;
        }
    }

    /**
     * Returns true when the MultiSafepay transaction has at least one related capture.
     *
     * We use the API response field `related_transactions` to detect previous (partial) captures
     * and prevent rendering the Capture button again.
     *
     * @param Order $order
     * @param mixed|null $transaction Optional transaction fetched from API to avoid extra calls
     * @return bool
     * @throws ClientExceptionInterface
     */
    private function hasRelatedCaptureTransaction(Order $order, $transaction = null): bool
    {
        if ($transaction === null) {
            $transaction = $this->getMultiSafepayTransaction($order);
        }
        if (!$transaction) {
            return false;
        }

        foreach ((array)$transaction->getRelatedTransactions() as $relatedTransaction) {
            if (strtolower((string)$relatedTransaction->getType()) === 'capture') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true when a full capture has already been performed.
     *
     * In full capture responses, `related_transactions` can be null, but
     * `payment_details.capture_remain` becomes 0.
     *
     * @param Order $order
     * @param mixed|null $transaction Optional transaction fetched from API to avoid extra calls
     * @return bool
     * @throws ClientExceptionInterface
     */
    private function hasFullCaptureTransaction(Order $order, $transaction = null): bool
    {
        if ($transaction === null) {
            $transaction = $this->getMultiSafepayTransaction($order);
        }
        if (!$transaction) {
            return false;
        }

        $paymentDetails = $transaction->getPaymentDetails();
        if (strtolower((string)$paymentDetails->getCapture()) !== 'manual') {
            return false;
        }

        return (int)$paymentDetails->getCaptureRemain() === 0;
    }

    /**
     * Add "Capture" button to order detail page for MultiSafepay authorized orders
     *
     * This hook adds a button to the admin order view page that allows merchants
     * to manually capture funds for orders that are in "authorized" status.
     *
     * @param array $params Contains:
     *  - 'controller': The admin controller instance
     *  - 'id_order': The order ID
     *  - 'actions_bar_buttons_collection': buttons collection to add
     *  to (PS 1.7.7+: PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButtonsCollection;
     *  PS 8+: PrestaShop\PrestaShop\Core\Action\ActionsBarButtonsCollection)
     *
     * @return void
     * @throws PrestaShopException
     * @throws TypeException
     * @throws ClientExceptionInterface
     */
    public function hookActionGetAdminOrderButtons(array $params): void
    {
        // Hook executed on the Symfony order page.
        // - PS 1.7.7+ provides PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButtonsCollection
        // - PS 8+ provides PrestaShop\PrestaShop\Core\Action\ActionsBarButtonsCollection
        $orderId = (int)$params['id_order'];
        $order = new Order($orderId);

        // Only show the button for MultiSafepay orders
        if (!$order->module || $order->module !== 'multisafepayofficial') {
            return;
        }

        // Check if the order is in "authorized" status
        $authorizedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED');
        if ((int)$order->current_state !== $authorizedStatusId) {
            return;
        }

        // Fetch the transaction only once and reuse it for checks
        $transaction = $this->getMultiSafepayTransaction($order);

        // Do not show the Capture button when:
        // - there are partial captures (related_transactions type=capture)
        // - there is a full capture (capture_remain=0, financial_status=completed)
        if ($this->hasRelatedCaptureTransaction($order, $transaction) ||
            $this->hasFullCaptureTransaction($order, $transaction)
        ) {
            return;
        }

        // Generate the URL for the capture action
        $captureUrl = $this->context->link->getAdminLink(
            'AdminMultisafepayOfficialCapture',
            true,
            [],
            ['id_order' => $orderId]
        );

        // Add the "Capture" button
        $actionsBarButtonsCollection = $params['actions_bar_buttons_collection'] ?? null;
        if (!$actionsBarButtonsCollection ||
            !is_object($actionsBarButtonsCollection) ||
            !method_exists($actionsBarButtonsCollection, 'add')
        ) {
            return;
        }

        $ps17ButtonClass = 'PrestaShopBundle\\Controller\\Admin\\Sell\\Order\\ActionsBarButton';
        $ps17CollectionClass = 'PrestaShopBundle\\Controller\\Admin\\Sell\\Order\\ActionsBarButtonsCollection';
        $ps8ButtonClass = 'PrestaShop\\PrestaShop\\Core\\Action\\ActionsBarButton';
        $ps8CollectionClass = 'PrestaShop\\PrestaShop\\Core\\Action\\ActionsBarButtonsCollection';

        $buttonClass = null;
        if (class_exists($ps17CollectionClass) &&
            $actionsBarButtonsCollection instanceof $ps17CollectionClass &&
            class_exists($ps17ButtonClass)
        ) {
            $buttonClass = $ps17ButtonClass;
        } elseif (class_exists($ps8CollectionClass) &&
            $actionsBarButtonsCollection instanceof $ps8CollectionClass &&
            class_exists($ps8ButtonClass)) {
            $buttonClass = $ps8ButtonClass;
        } elseif (class_exists($ps8ButtonClass)) {
            $buttonClass = $ps8ButtonClass;
        } elseif (class_exists($ps17ButtonClass)) {
            $buttonClass = $ps17ButtonClass;
        }

        if (!$buttonClass) {
            return;
        }

        if (!class_exists($buttonClass)) {
            return;
        }

        /** @var class-string $buttonClass */
        $actionsBarButtonsCollection->add(
            new $buttonClass(
                'btn-action multisafepay-capture-btn',
                [
                    'href' => $captureUrl,
                ],
                $this->l('Full Capture')
            )
        );
    }

    /**
     * PS 1.7: inject Capture into the top "Orders Actions" bar.
     *
     * @param array $params
     * @return string
     * @throws PrestaShopException
     * @throws ClientExceptionInterface
     */
    public function hookDisplayBackOfficeOrderActions(array $params): string
    {
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            return '';
        }

        if (self::$legacyAdminOrderCaptureButtonRendered) {
            return '';
        }

        $orderId = isset($params['id_order']) ? (int)$params['id_order'] : 0;
        if (!$orderId) {
            return '';
        }

        $order = new Order($orderId);

        if (!$order->module || $order->module !== 'multisafepayofficial') {
            return '';
        }

        $authorizedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED');
        if ((int)$order->current_state !== $authorizedStatusId) {
            return '';
        }

        // Fetch the transaction only once and reuse it for checks
        $transaction = $this->getMultiSafepayTransaction($order);

        // Do not show the Capture button when:
        // - there are partial captures (related_transactions type=capture)
        // - there is a full capture (capture_remain=0)
        if ($this->hasRelatedCaptureTransaction($order, $transaction) ||
            $this->hasFullCaptureTransaction($order, $transaction)
        ) {
            return '';
        }

        $captureUrl = $this->context->link->getAdminLink(
            'AdminMultisafepayOfficialCapture',
            true,
            [],
            ['id_order' => $orderId]
        );

        $this->context->smarty->assign([
            'multisafepay_capture_url' => $captureUrl,
        ]);

        // Prevent rendering an extra legacy panel later on the page.
        self::$legacyAdminOrderCaptureButtonRendered = true;

        return $this->display(__FILE__, 'admin_order_capture_action.tpl');
    }
}
