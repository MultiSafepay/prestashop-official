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

use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\OrderRequestBuilderHelper;
use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\PrestaShop\Helper\CancelOrderHelper;
use MultiSafepay\PrestaShop\Helper\DuplicateCartHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use Psr\Http\Client\ClientExceptionInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class MultisafepayOfficialPaymentModuleFrontController
 *
 * @property MultisafepayOfficial $module
 */
class MultisafepayOfficialPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * Process the payment
     *
     * @return mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ClientExceptionInterface
     * @throws Exception
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function postProcess()
    {
        if (!$this->isContextSetUp()) {
            LoggerHelper::log(
                'warning',
                'It seems postProcess method of MultiSafepay is being called out of context.',
                false,
                null,
                $this->context->cart->id ?? null
            );
            Tools::redirect('/index.php?controller=order&step=1');
            return null;
        }

        LoggerHelper::log(
            'info',
            'Starting the payment process for Shopping Cart',
            true,
            null,
            $this->context->cart->id ?? null
        );

        if (!$this->isValidPaymentMethod()) {
            LoggerHelper::log(
                'warning',
                'The customer address changed just before the end of the checkout process method and now this method is not available any more.',
                false,
                null,
                $this->context->cart->id ?? null
            );
            Tools::redirect('/index.php?controller=order&step=1');
            return null;
        }

        $selectedPaymentOption = $this->getSelectedPaymentOption();
        $cart = $this->context->cart;
        $customer = new Customer($cart->id_customer);

        $orderService = new OrderService($this->module, new SdkService());

        $orderRequestBuilder = OrderRequestBuilderHelper::create($this->module);

        $order = null;

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT')) {
            try {
                $orderService->validateOrder(
                    $cart,
                    (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED'),
                    0,
                    $selectedPaymentOption->getFrontEndName($this->context->language->id ?: null),
                    $this->context->customer->secure_key
                );

                $orderCollection = new PrestaShopCollection('Order');
                $orderCollection->where('id_cart', '=', $cart->id);

                /** @var Order $order */
                $order = $orderCollection->getFirst();
            } catch (PrestaShopException $prestaShopException) {
                LoggerHelper::logException(
                    'error',
                    $prestaShopException,
                    'Error when try to create an order',
                    null,
                    $cart->id ?? null
                );
                Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
            }
        }

        $orderRequest = $orderRequestBuilder->build($cart, $customer, $selectedPaymentOption, $order);

        // Removing Payload from OrderRequest
        $data = $orderRequest->getData();
        if (isset($data['payment_data']['payload'])) {
            unset($data['payment_data']['payload']);
        }
        if (empty($data['payment_data'])) {
            unset($data['payment_data']);
        }
        $jsonOutput = json_encode($data);

        $message = 'An OrderRequest has been created and contains the following information: ' . $jsonOutput;
        LoggerHelper::log(
            'info',
            $message,
            true,
            $order ? (string)$order->id : null,
            $cart->id ?? null
        );

        try {
            $sdkService = new SdkService();
            $transactionManager = $sdkService->getSdk()->getTransactionManager();
            $transaction        = $transactionManager->create($orderRequest);
        } catch (ApiException $apiException) {
            $errorMessage = 'Error when try to create a MultiSafepay transaction using the following OrderRequest data: ' . json_encode($orderRequest->getData());
            LoggerHelper::logException(
                'error',
                $apiException,
                $errorMessage,
                $order ? (string)$order->id : null,
                $cart->id ?? null
            );

            $this->context->smarty->assign(
                [
                    'layout'        => 'full-width-template',
                    'error_message' => $apiException->getMessage(),
                ]
            );

            if (!Configuration::get('MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT')) {
                return $this->setTemplate('module:multisafepayofficial/views/templates/front/error.tpl');
            }

            if (isset($orderCollection) && $orderCollection->count() > 0) {
                // Cancel orders
                CancelOrderHelper::cancelOrder($orderCollection);

                // Duplicate cart
                DuplicateCartHelper::duplicateCart((new Cart($this->context->cart->id)));
            }

            return $this->setTemplate('module:multisafepayofficial/views/templates/front/error.tpl');
        }

        LoggerHelper::log(
            'info',
            'A transaction has been created with payment link ' . $transaction->getPaymentUrl(),
            true,
            $order ? (string)$order->id : null,
            $cart->id ?? null
        );

        Tools::redirect($transaction->getPaymentUrl());
        return null;
    }

    /**
     * Return according the context if the module does not support the payment address.
     *
     * @return boolean
     */
    private function isValidPaymentMethod(): bool
    {
        $isValid = false;
        foreach (Module::getPaymentModules() as $module) {
            if ((string)$module['name'] === 'multisafepayofficial') {
                $isValid = true;
                break;
            }
        }
        return $isValid;
    }

    /**
     * Return if the module does not support the basic variables.
     *
     * @return boolean
     */
    private function isContextSetUp(): bool
    {
        // If the module is not active or is being called out of context
        if (!$this->module->active || !($this->module instanceof MultisafepayOfficial)) {
            return false;
        }

        if (((string)$this->context->cart->id_customer === '0') ||
            ((string)$this->context->cart->id_address_delivery === '0') ||
            ((string)$this->context->cart->id_address_invoice === '0')) {
            return false;
        }
        return true;
    }

    /**
     * Return the PaymentOption selected in checkout
     *
     * @return BasePaymentOption
     * @throws Exception
     */
    private function getSelectedPaymentOption(): BasePaymentOption
    {
        $paymentOptionService = new PaymentOptionService($this->module);
        return $paymentOptionService->getMultiSafepayPaymentOption(Tools::getValue('gateway'));
    }
}
