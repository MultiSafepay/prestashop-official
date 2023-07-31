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

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use Cart;
use Configuration;
use Context;
use Currency;
use Exception;
use Media;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Wallet;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use PrestaShopDatabaseException;
use PrestaShopException;
use Tools;

class ApplePay extends BasePaymentOption
{
    public const CLASS_NAME = 'ApplePay';
    protected $gatewayCode = 'APPLEPAY';
    protected $logo = 'applepay.png';
    protected $hasConfigurableDirect = true;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('Apple Pay', self::CLASS_NAME);
    }

    public function getTransactionType(): string
    {
        if ($this->isDirect()) {
            $checkoutVars = Tools::getAllValues();
            return empty($checkoutVars['payment_token']) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
        }

        return self::REDIRECT_TYPE;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws Exception
     */
    public function registerJavascript(Context $context): void
    {
        $context->controller->registerJavascript(
            'module-multisafepay-applepay-javascript',
            'modules/multisafepayofficial/views/js/multisafepay-applepay.js',
            [
                'priority' => 200
            ]
        );

        // To avoid problems with the Apple Pay button, we need to load them in the footer area
        if ($this->isDirect()) {
            $context->controller->registerJavascript(
                'module-multisafepay-initialize-common-wallets-javascript',
                'modules/multisafepayofficial/views/js/multisafepay-common-wallets.js',
                [
                    'priority' => 300
                ]
            );

            $context->controller->registerJavascript(
                'module-multisafepay-applepay-wallet-javascript',
                'modules/multisafepayofficial/views/js/multisafepay-applepay-wallet.js',
                [
                    'priority' => 200
                ]
            );

            $applePayMerchantName = Configuration::get('MULTISAFEPAY_OFFICIAL_MERCHANT_NAME_APPLEPAY') ?: '';

            Media::addJsDef([
                'configApplePayTotalPrice'   => $context->cart->getOrderTotal(),
                'configApplePayCurrencyCode' => (new Currency($context->cart->id_currency))->iso_code,
                'configApplePayCountryCode'  => $this->getCountryCode($context),
                'configApplePayMerchantName' => $applePayMerchantName,
                'configApplePayDebugMode'    => (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')
            ]);
        }

        parent::registerJavascript($context);
    }

    /**
     * @param Cart $cart
     * @param array $data
     * @return GatewayInfoInterface|null
     *
     * @phpcs:disable -- Disable to avoid triggering a warning in validator about unused parameter
     */
    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        if (!isset($data['payment_token'])) {
            return null;
        }
        $gatewayInfo = new Wallet();
        $gatewayInfo->addPaymentToken($data['payment_token']);

        return $gatewayInfo;
        // phpcs:enable
    }

    /**
     * @return array
     */
    public function getPaymentOptionSettingsFields(): array
    {
        $settings['MULTISAFEPAY_OFFICIAL_MERCHANT_NAME_APPLEPAY'] = [
            'type'          => 'text',
            'name'          => $this->module->l('Merchant name', 'BasePaymentOption'),
            'value'         => Configuration::get('MULTISAFEPAY_OFFICIAL_MERCHANT_NAME_APPLEPAY'),
            'helperText'    => $this->module->l(
                'The merchant name provided at your Apple Pay account',
                self::CLASS_NAME
            ),
            'default'       => '',
            'order'         => 12,
            'class'         => 'apple-pay-direct-name',
        ];

        return $settings;
    }
}
