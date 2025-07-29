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

namespace MultiSafepay\PrestaShop\Services;

use Address;
use Cart;
use Configuration;
use Context;
use Country;
use Currency;
use Exception;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultisafepayOfficial;
use PrestaShopCollection;
use PrestaShopDatabaseException;
use PrestaShopException;
use Psr\Http\Client\ClientExceptionInterface;
use Tools;

/**
 * Class OrderService
 *
 * @package MultiSafepay\PrestaShop\Services
 */
class OrderService
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
     * @var string|null
     */
    private $paymentComponentApiToken = null;

    /**
     * OrderService constructor.
     *
     * @param MultisafepayOfficial $module
     * @param SdkService $sdkService
     */
    public function __construct(
        MultisafepayOfficial $module,
        SdkService $sdkService
    ) {
        $this->module     = $module;
        $this->sdkService = $sdkService;
    }

    /**
     * Return the arguments required to initialize the payment component.
     *
     * @param string $gatewayCode
     * @param string|null $customerString
     * @param string|null $recurringModel
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function createPaymentComponentOrder(
        string $gatewayCode,
        ?string $customerString,
        ?string $recurringModel
    ): array {
        $paymentComponentArguments = [
            'debug'     => (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE'),
            'env'       => $this->sdkService->getTestMode() ? 'test' : 'live',
            'apiToken'  => $this->getPaymentComponentApiToken() ?? '',
            'orderData' => [
                'currency'  => (new Currency(Context::getContext()->cart->id_currency))->iso_code,
                'amount'    => MoneyHelper::priceToCents(
                    Context::getContext()->cart->getOrderTotal(true, Cart::BOTH)
                ),
                'customer'  => [
                    'locale'    => Tools::substr(Context::getContext()->language->getLocale(), 0, 2),
                    'country'   => (new Country(
                        (new Address((int)Context::getContext()->cart->id_address_invoice))->id_country
                    ))->iso_code,
                ],
                'payment_options' => [
                    'template'  => [
                        'settings' => [
                            'embed_mode' => true,
                        ],
                        'merge' => true,
                    ],
                ],
            ],
            'recurring' => null,
        ];

        // Payment Component Template ID.
        $templateId = Configuration::get('MULTISAFEPAY_OFFICIAL_TEMPLATE_ID_VALUE');
        if (!empty($templateId)) {
            $paymentComponentArguments['orderData']['payment_options']['template_id'] = $templateId;
        }

        if ($recurringModel) {
            $paymentComponentArguments['recurring']['model'] = $recurringModel;
            $paymentComponentArguments['recurring']['tokens'] = $this->getPaymentTokens($customerString, $gatewayCode);
        }

        return $paymentComponentArguments;
    }

    /**
     * Return an array of payment tokens.
     *
     * @param string $customerString
     * @param string $gatewayCode
     * @return array
     */
    private function getPaymentTokens(string $customerString, string $gatewayCode): array
    {
        try {
            $tokens = $this->sdkService->getSdk()->getTokenManager()->getListByGatewayCodeAsArray(
                $customerString,
                $gatewayCode
            );
        } catch (ClientExceptionInterface | ApiException $exception) {
            $tokens = [];
        }

        return $tokens;
    }

    /**
     * @return string|null
     * @throws ClientExceptionInterface
     * @throws InvalidDataInitializationException
     */
    public function getPaymentComponentApiToken(): ?string
    {
        if (!isset($this->paymentComponentApiToken)) {
            try {
                $this->paymentComponentApiToken = (
                    $this->sdkService->getSdk()->getApiTokenManager()->get()
                )->getApiToken();
            } catch (ApiException $apiException) {
                LoggerHelper::logException(
                    'alert',
                    $apiException,
                    'Error when try to get the Api Token',
                    null,
                    Context::getContext()->cart->id ?? null
                );
                return '';
            }
        }
        return $this->paymentComponentApiToken;
    }

    /**
     * @param Cart $cart
     * @param int $orderStateId
     * @param float $amount
     * @param string $paymentMethod
     * @param string $secureKey
     * @param array $extraVars
     *
     * @throws PrestaShopException
     */
    public function validateOrder(
        Cart $cart,
        int $orderStateId,
        float $amount,
        string $paymentMethod,
        string $secureKey,
        array $extraVars = []
    ): void {
        // If order already exists, we don't have to validate it again
        if ($cart->OrderExists()) {
            return;
        }

        $this->module->validateOrder(
            $cart->id,
            $orderStateId,
            $amount,
            $paymentMethod,
            null,
            array_merge(
                ['send_email' => (Configuration::get('MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL'))],
                $extraVars
            ),
            $cart->id_currency, // @phpstan-ignore-line
            false,
            $secureKey // @phpstan-ignore-line
        );

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            $orderCollection = new PrestaShopCollection('Order');
            $orderCollection->where('id_cart', '=', $cart->id);

            $ordersIds = $this->getOrdersIdsFromCollection($orderCollection);
            $tempOrderId = implode(',', array_filter($ordersIds, static function ($value) {
                return !empty($value);
            }));
            $orderId = !empty($tempOrderId) ? $tempOrderId : null;

            LoggerHelper::log(
                'info',
                'Order has been validated.',
                false,
                $orderId,
                $cart->id ?? null
            );
        }
    }

    /**
     * Return an array of Orders IDs for the given PrestaShopCollection
     *
     * @param PrestaShopCollection $orderCollection
     *
     * @return array
     */
    public function getOrdersIdsFromCollection(PrestaShopCollection $orderCollection): array
    {
        $ordersIds = [];
        foreach ($orderCollection->getResults() as $order) {
            $ordersIds[] = $order->id;
        }

        return $ordersIds;
    }
}
