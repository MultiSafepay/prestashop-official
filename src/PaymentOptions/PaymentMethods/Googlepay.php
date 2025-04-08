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
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Wallet;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultisafepayOfficial;
use PrestaShopDatabaseException;
use PrestaShopException;
use Psr\Http\Client\ClientExceptionInterface;
use Tools;

class Googlepay extends BasePaymentOption
{
    /**
     * @var bool
     */
    public $hasConfigurableDirect = true;

    public const TEST_MERCHANT_NAME = 'Example Merchant';
    public const TEST_MERCHANT_ID = '12345678901234567890';

    public function getTransactionType(): string
    {
        if ($this->hasConfigurableDirect) {
            $checkoutVars = Tools::getAllValues();
            return empty($checkoutVars['payment_token']) ? OrderRequest::REDIRECT_TYPE : OrderRequest::DIRECT_TYPE;
        }

        return OrderRequest::REDIRECT_TYPE;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws Exception
     */
    public function registerJavascript(Context $context): void
    {
        // To avoid problems with the Google Pay button, we need to load them in the footer area
        if ($this->isDirect()) {
            $context->controller->registerJavascript(
                'module-multisafepay-googlepay-direct-call-javascript',
                'https://pay.google.com/gp/p/js/pay.js',
                [
                    'priority' => 1,
                    'server' => 'remote'
                ]
            );

            $context->controller->registerJavascript(
                'module-multisafepay-initialize-common-wallets-javascript',
                'modules/multisafepayofficial/views/js/multisafepay-common-wallets.js',
                [
                    'priority' => 300
                ]
            );

            $context->controller->registerJavascript(
                'module-multisafepay-googlepay-wallet-javascript',
                'modules/multisafepayofficial/views/js/multisafepay-googlepay-wallet.js',
                [
                    'priority' => 200
                ]
            );

            $merchantName = self::TEST_MERCHANT_NAME;
            $merchantId = self::TEST_MERCHANT_ID;
            $environment = $this->getMultiSafepayEnvironment();

            if ($environment === 'LIVE') {
                $merchantName = Configuration::get('MULTISAFEPAY_OFFICIAL_MERCHANT_NAME_GOOGLEPAY') ?: '';
                $merchantId = Configuration::get('MULTISAFEPAY_OFFICIAL_MERCHANT_ID_GOOGLEPAY') ?: '';
            }

            Media::addJsDef([
                'configEnvironment'           => $environment,
                'configGatewayMerchantId'     => $this->getMultiSafepayAccountId(),
                'configGooglePayTotalPrice'   => $context->cart->getOrderTotal(),
                'configGooglePayCurrencyCode' => (new Currency($context->cart->id_currency))->iso_code,
                'configGooglePayCountryCode'  => $this->getCountryCode($context),
                'configGooglePayMerchantName' => $merchantName,
                'configGooglePayMerchantId'   => $merchantId,
                'configGooglePayDebugMode'    => (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')
            ]);
        }
    }

    /**
     * Return the MultiSafepay Merchant Account ID
     *
     * @return int
     */
    private function getMultiSafepayAccountId(): int
    {
        try {
            /** @var SdkService $sdkService */
            $sdkService = $this->module->get('multisafepay.sdk_service');
            $accountManager = $sdkService->getSdk()->getAccountManager();
            $gatewayMerchantId = $accountManager->get()->getAccountId();
        } catch (ApiException|ClientExceptionInterface|Exception $exception) {
            LoggerHelper::logException(
                'alert',
                $exception,
                'Error when try to get the merchant account ID',
                null,
                Context::getContext()->cart->id ?? null
            );
        }

        return $gatewayMerchantId ?? 0;
    }

    /**
     * @return string
     */
    private function getMultiSafepayEnvironment(): string
    {
        return Configuration::get('MULTISAFEPAY_OFFICIAL_TEST_MODE') ? 'TEST' : 'LIVE';
    }

    /**
     * @param Cart $cart
     * @param array $data
     * @return GatewayInfoInterface|null
     */
    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        if (!isset($data['payment_token'])) {
            return null;
        }
        $gatewayInfo = new Wallet();
        $gatewayInfo->addPaymentToken($data['payment_token']);

        return $gatewayInfo;
    }

    /**
     * @return array
     */
    public function getPaymentOptionSettingsFields(): array
    {
        $settings['MULTISAFEPAY_OFFICIAL_MERCHANT_NAME_GOOGLEPAY'] = [
            'type'          => 'text',
            'name'          => $this->module->l('Merchant name', self::CLASS_NAME),
            'value'         => Configuration::get('MULTISAFEPAY_OFFICIAL_MERCHANT_NAME_GOOGLEPAY'),
            'helperText'    => $this->module->l(
                'The merchant name provided at your Google Pay direct account',
                self::CLASS_NAME
            ),
            'default'       => '',
            'order'         => 12,
            'class'         => 'google-pay-direct-name',
        ];
        $settings['MULTISAFEPAY_OFFICIAL_MERCHANT_ID_GOOGLEPAY'] = [
            'type'          => 'text',
            'name'          => $this->module->l('Merchant ID', self::CLASS_NAME),
            'value'         => Configuration::get('MULTISAFEPAY_OFFICIAL_MERCHANT_ID_GOOGLEPAY'),
            'helperText'    => $this->module->l(
                'The merchant ID provided at your Google Pay direct account',
                self::CLASS_NAME
            ),
            'default'       => '',
            'order'         => 13,
            'class'         => 'google-pay-direct-id',
        ];

        return $settings;
    }
}
