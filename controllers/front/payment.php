<?php declare(strict_types=1);

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

use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\SdkService;
use PaymentModule;

class MultisafepayPaymentModuleFrontController extends ModuleFrontController
{

    /**
     * Process checkout form and register the order.
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess(): void
    {

        if (!$this->isContextSetUp()) {
            LoggerHelper::logWarning(
                'Warning: It seems postProcess method of MultiSafepay is being called out of context.'
            );
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Starting the payment process for Cart ID: '.$this->context->cart->id);
        }

        if (!$this->isValidPaymentMethod()) {
            LoggerHelper::logWarning(
                'The customer address changed just before the end of the checkout process method and now this method is not available any more.'
            );
            Tools::redirect('index.php?controller=order&step=1');

            return;
        }

        try {
            $validate = $this->module->validateOrder(
                $this->context->cart->id,
                Configuration::get('MULTISAFEPAY_OS_INITIALIZED'),
                0,
                $this->module->displayName,
                null,
                ['dont_send_email' => true],
                $this->context->cart->id_currency,
                false,
                $this->context->customer->secure_key
            );
        } catch (PrestaShopException $prestaShopException) {
            LoggerHelper::logError('Error when try to create an order using Cart ID '.$this->context->cart->id);
            LoggerHelper::logError($prestaShopException->getMessage());
            Tools::redirectLink($this->context->link->getPageLink('order', true, null, ['step' => '3']));
        }

        $orderCollection = new PrestaShopCollection('Order');
        $orderCollection->where('id_cart', '=', $this->context->cart->id);

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            $ordersIds = $this->getOrdersIdsFromCollection($orderCollection);
            LoggerHelper::logInfo(
                'Order with Cart ID:'.$this->context->cart->id.' has been validated and as result the following orders IDS: '.implode(
                    ',',
                    $ordersIds
                ).' has been registered.'
            );
        }

        /** @var OrderService $orderService */
        $orderService = $this->get('multisafepay.order_service');
        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->get('multisafepay.payment_option_service');
        $paymentOption        = $paymentOptionService->getMultiSafepayPaymentOption(Tools::getValue('gateway'));

        $orderRequest = $orderService->createOrderRequest($orderCollection, $paymentOption);

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo(
                'An OrderRequest for the Cart ID: '.$this->context->cart->id.' has been created and contains the following information: '.json_encode(
                    $orderRequest->getData()
                )
            );
        }

        $transaction = $this->createMultiSafepayTransaction($orderRequest);

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo(
                'Ending payment process. A transaction has been created for Cart ID: '.$this->context->cart->id.' with payment link '.$transaction->getPaymentUrl(
                )
            );
        }

        Tools::redirectLink($transaction->getPaymentUrl());
    }


    /**
     * Create a MultiSafepay Transaction
     *
     * @param OrderRequest $orderRequest
     *
     * @return TransactionResponse
     */
    private function createMultiSafepayTransaction(OrderRequest $orderRequest): TransactionResponse
    {
        /** @var SdkService $sdkService */
        $sdkService = $this->module->get('multisafepay.sdk_service');
        $transactionManager = $sdkService->getSdk()->getTransactionManager();
        try {
            $transaction = $transactionManager->create($orderRequest);
        } catch (ApiException $apiException) {
            LoggerHelper::logError(
                'Error when try to create a MultiSafepay transaction using the following OrderRequest data: '.json_encode(
                    $orderRequest->getData()
                )
            );
            LoggerHelper::logError($apiException->getMessage());
        }

        return $transaction;
    }

    /**
     * Return according the context if the payment address is not supported by the module.
     *
     * @return boolean
     */
    private function isValidPaymentMethod(): bool
    {
        $isValid = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'multisafepay') {
                $isValid = true;
                break;
            }
        }

        return $isValid;
    }

    /**
     * Return if the basic variables are not supported by the module.
     *
     * @return boolean
     */
    private function isContextSetUp(): bool
    {
        // If the module is not active or is being called out of context
        if (!$this->module->active || !($this->module instanceof Multisafepay)) {
            return false;
        }

        // If the cart context is not properly setup
        if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0) {
            return false;
        }

        return true;
    }

    /**
     *
     * Return an array of Orders IDs for the given PrestaShopCollection
     *
     * @param PrestaShopCollection $orderCollection
     *
     * @return array
     */
    private function getOrdersIdsFromCollection(PrestaShopCollection $orderCollection): array
    {
        $ordersIds = [];
        foreach ($orderCollection->getResults() as $order) {
            $ordersIds[] = $order->id;
        }

        return $ordersIds;
    }
}
