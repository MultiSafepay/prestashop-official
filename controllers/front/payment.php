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

use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
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
            LoggerHelper::logWarning('Warning: It seems postProcess method of MultiSafepay is being called out of context.');
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Starting the payment process for Cart ID: ' . $this->context->cart->id);
        }

        if (!$this->isValidPaymentMethod()) {
            LoggerHelper::logWarning('The customer address changed just before the end of the checkout process method and now this method is not available any more.');
            Tools::redirect('index.php?controller=order&step=1');
            return;
        }

        try {
            $validate = $this->module->validateOrder($this->context->cart->id, Configuration::get('MULTISAFEPAY_OS_INITIALIZED'), 0, $this->module->displayName, null, array('dont_send_email' => true), $this->context->cart->id_currency, false, $this->context->customer->secure_key);
        } catch (PrestaShopException $prestaShopException) {
            LoggerHelper::logError('Error when try to create an order using Cart ID ' . $this->context->cart->id);
            LoggerHelper::logError($prestaShopException->getMessage());
            Tools::redirectLink($this->context->link->getPageLink('order', true, null, array('step' => '3')));
        }

        $order_collection = new PrestaShopCollection('Order');
        $order_collection->where('id_cart', '=', $this->context->cart->id);

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            $orders_ids = $this->getOrdersIdsFromCollection($order_collection);
            LoggerHelper::logInfo('Order with Cart ID:' . $this->context->cart->id . ' has been validated and as result the following orders IDS: ' . implode(',', $orders_ids) . ' has been registered.');
        }

        $order_service                  = new OrderService($this->module->id, $this->context->customer->secure_key);
        $multisafepay_gateway_code      = Tools::getValue('gateway');
        $multisafepay_transaction_type  = Tools::getValue('type');
        $multisafepay_gateway_info_vars = Tools::getAllValues();

        $order_request = $order_service->createOrderRequest($order_collection, $multisafepay_gateway_code, $multisafepay_transaction_type, $multisafepay_gateway_info_vars);

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('An OrderRequest for the Cart ID: ' . $this->context->cart->id . ' has been created and contains the following information: ' . json_encode($order_request->getData()));
        }

        $transaction = $this->createMultiSafepayTransaction($order_request);

        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Ending payment process. A transaction has been created for Cart ID: ' . $this->context->cart->id . ' with payment link ' . $transaction->getPaymentUrl());
        }

        Tools::redirectLink($transaction->getPaymentUrl());
    }


    /**
     * Create a MultiSafepay Transaction
     *
     * @param OrderRequest $order_request
     * @return TransactionResponse
     */
    private function createMultiSafepayTransaction(OrderRequest $order_request): TransactionResponse
    {
        $transaction_manager    = ((new SdkService())->getSdk())->getTransactionManager();
        try {
            $transaction = $transaction_manager->create($order_request);
        } catch (ApiException $api_exception) {
            LoggerHelper::logError('Error when try to create a MultiSafepay transaction using the following OrderRequest data: ' . json_encode($order_request->getData()));
            LoggerHelper::logError($api_exception->getMessage());
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
        $is_valid = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'multisafepay') {
                $is_valid = true;
                break;
            }
        }
        return $is_valid;
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
     * @param PrestaShopCollection $order_collection
     * @return array
     */
    private function getOrdersIdsFromCollection(PrestaShopCollection $order_collection): array
    {
        $orders_ids = array();
        foreach ($order_collection->getResults() as $order) {
            $orders_ids[] = $order->id;
        }
        return $orders_ids;
    }
}
