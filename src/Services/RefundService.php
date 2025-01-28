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
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Services;

use Configuration;
use Currency;
use Exception;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\PrestaShop\Helper\OrderMessageHelper;
use MultisafepayOfficial;
use Order;
use PrestaShopCollection;
use PrestaShopException;
use Tools;

/**
 * Class RefundService
 * @package MultiSafepay\PrestaShop\Services
 */
class RefundService
{
    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * @var SdkService
     */
    private $sdkService;

    /**
     * @var PaymentOptionService
     */
    private $paymentOptionService;

    /**
     * RefundService constructor.
     *
     * @param MultisafepayOfficial $module
     * @param SdkService $sdkService
     * @param PaymentOptionService $paymentOptionService
     */
    public function __construct(
        MultisafepayOfficial $module,
        SdkService $sdkService,
        PaymentOptionService $paymentOptionService
    ) {
        $this->module               = $module;
        $this->sdkService           = $sdkService;
        $this->paymentOptionService = $paymentOptionService;
    }

    /**
     * @param Order $order
     * @param array $productList
     *
     * @return bool
     * @throws \PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function processRefund(Order $order, array $productList): bool
    {
        $transactionManager = $this->sdkService->getSdk()->getTransactionManager();
        try {
            $transaction = $transactionManager->get($order->reference);
        } catch (Exception $exception) {
            $transaction = $transactionManager->get((string)$order->id_cart);
        }

        $paymentOption = $this->paymentOptionService->getMultiSafepayPaymentOption(
            $transaction->getPaymentDetails()->getType()
        );

        // Do not process refunds for gateways that requires ShoppingCart
        if (!$paymentOption->canProcessRefunds()) {
            $this->handleMessage(
                $order,
                "Refund for Order ID: $order->id has failed, because the Payment Method used for this Order does not support refunds."
            );

            return false;
        }

        $refundRequest = $transactionManager->createRefundRequest($transaction);
        $refundRequest->addDescription(Description::fromText('Refund order'));
        $refundData = $this->getRefundData($order, $productList);
        $refundRequest->addMoney(MoneyHelper::createMoney((float)$refundData['amount'], $refundData['currency']));

        try {
            $transactionManager->refund($transaction, $refundRequest);
        } catch (ApiException $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Error processing the refund.',
                (string)$order->id ?: null,
                $order->id_cart ?: null
            );
            $this->handleMessage($order, "Refund for Order ID: $order->id has failed.");

            return false;
        }

        $amount = $refundData['amount'];
        $currency = $refundData['currency'];
        $message = 'A refund of ' . $amount . ' ' . $currency . ' has been processed';

        OrderMessageHelper::addMessage($order, $message . ' for Order ID: ' . $order->id);
        LoggerHelper::log(
            'info',
            $message,
            true,
            (string)$order->id ?: null,
            $order->id_cart ?: null
        );

        return true;
    }

    /**
     * @param Order $order
     * @param array $productList
     *
     * @return array
     */
    public function getRefundData(Order $order, $productList = []): array
    {
        $currency           = new Currency($order->id_currency);
        $refund             = [];
        $refund['currency'] = $currency->iso_code;
        $refund['amount']   = $this->getProductsRefundAmount($productList);
        $refund['amount']   += $this->getShippingRefundAmount($order);
        $refund['amount']   = Tools::ps_round($refund['amount'], 2, $order->round_mode);

        return $refund;
    }

    /**
     * @param array $productList
     *
     * @return float
     */
    public function getProductsRefundAmount($productList = []): float
    {
        $refundAmount = 0;

        $refundKey = 'total_refunded_tax_incl';

        if (version_compare(_PS_VERSION_, '1.7.7') <= 0) {
            $refundKey = 'amount';
        }

        foreach ($productList as $productListItem) {
            $refundAmount += $productListItem[$refundKey];
        }

        return $refundAmount;
    }

    /**
     * @param Order $order
     *
     * @return float
     */
    private function getShippingRefundAmount(Order $order): float
    {
        $cancelProduct = Tools::getValue('cancel_product');
        $partialRefundShippingCost = Tools::getValue('partialRefundShippingCost');

        // If total shipping is being refunded (standard refund), then shipping_amount is equal to 0
        // and shipping value is 1.
        if (isset($cancelProduct['shipping']) && '1' === $cancelProduct['shipping']) {
            return (float)$order->total_shipping;
        }

        // If shipping amount is is being partially refunded, the "shipping" key is not set
        // and shipping_amount value reflects the total amount to be refunded.
        if (isset($cancelProduct['shipping_amount']) && '0' !== $cancelProduct['shipping_amount']) {
            return (float)$cancelProduct['shipping_amount'];
        }

        if ($partialRefundShippingCost && '0' !== $partialRefundShippingCost) {
            return (float) $partialRefundShippingCost;
        }

        return 0;
    }

    /**
     * @return bool
     */
    public function isVoucherRefund(): bool
    {
        $cancelProduct = Tools::getValue('cancel_product');
        $generateDiscountRefund = Tools::getValue('generateDiscountRefund');

        if (isset($cancelProduct['voucher']) && '1' === $cancelProduct['voucher'] ||
            isset($generateDiscountRefund) && 'on' === $generateDiscountRefund
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Order $order
     * @param ?array $productList
     *
     * @return bool
     */
    public function isAllowedToRefund(Order $order, ?array $productList): bool
    {
        if (!$order->module || $this->module->name !== $order->module) {
            return false;
        }

        if (!isset($productList)) {
            $this->handleMessage(
                $order,
                "Refund for Order ID: $order->id has failed, due to a missing productList"
            );

            return false;
        }

        if ($this->isVoucherRefund()) {
            $this->handleMessage(
                $order,
                "Refund for Order ID: $order->id will not be processed, due to a voucher being generated"
            );

            return false;
        }

        if ($this->isSplitOrder($order->reference)) {
            $this->handleMessage(
                $order,
                "Refund for Order ID: $order->id has failed, due to the order coming from a split shopping cart"
            );

            return false;
        }

        return true;
    }

    /**
     * @param string $orderReference
     *
     * @return bool
     */
    public function isSplitOrder(string $orderReference): bool
    {
        /** @var PrestaShopCollection $orderCollection */
        $orderCollection = Order::getByReference($orderReference);

        return count($orderCollection->getResults()) > 1;
    }

    /**
     * @param Order $order
     * @param string $message
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function handleMessage(Order $order, string $message): void
    {
        OrderMessageHelper::addMessage($order, $message);
        LoggerHelper::log(
            'warning',
            $message
        );
        Tools::displayError($message);
    }
}
