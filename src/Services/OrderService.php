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

use Address;
use Cart;
use Configuration;
use Context;
use Country;
use Currency;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultisafepayOfficial;
use PrestaShopCollection;
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
     * @var string
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
     * @param string|null $customerString
     * @param string|null $recurringModel
     *
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function createPaymentComponentOrder(?string $customerString, ?string $recurringModel): array
    {
        return
            [
                'debug'     => (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE') ?? false,
                'env'       => $this->sdkService->getTestMode() ? 'test' : 'live',
                'apiToken'  => $this->getPaymentComponentApiToken(),
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
                        'reference' => $customerString,
                    ],
                    'recurring' => [
                        'model' => $recurringModel,
                    ],
                    'template'  => [
                        'settings' => [
                            'embed_mode' => true,
                        ],
                    ],
                ],
            ];
    }

    /**
     * @return string
     */
    public function getPaymentComponentApiToken(): string
    {
        if (!isset($this->paymentComponentApiToken)) {
            try {
                $this->paymentComponentApiToken = (
                    $this->sdkService->getSdk()->getApiTokenManager()->get()
                )->getApiToken();
            } catch (ApiException $apiException) {
                LoggerHelper::logAlert(
                    'Error when try to get the Api Token: ' . $apiException->getMessage()
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
     * @throws \PrestaShopException
     */
    public function validateOrder(
        Cart $cart,
        int $orderStateId,
        float $amount,
        string $paymentMethod,
        string $secureKey,
        array $extraVars = []
    ): void {
        // If order already exist we don't have to validate it again
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
                ['send_email' => (Configuration::get('MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL') ?? false)],
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
            LoggerHelper::logInfo(
                'Order with Cart ID:'.$cart->id.' has been validated and as result the following orders IDS: '.implode(
                    ',',
                    $ordersIds
                ).' has been registered.'
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
