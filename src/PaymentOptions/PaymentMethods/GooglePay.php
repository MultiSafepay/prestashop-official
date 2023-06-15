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
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods;

use Cart;
use Configuration;
use Context;
use Exception;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Wallet;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\SdkService;
use PrestaShop\PrestaShop\Adapter\Entity\Media;
use Psr\Http\Client\ClientExceptionInterface;
use Tools;

class GooglePay extends BasePaymentOption
{
    public const CLASS_NAME = 'GooglePay';
    public const TEST_MERCHANT_NAME = 'Example Merchant';
    public const TEST_MERCHANT_ID = '12345678901234567890';

    protected $gatewayCode = 'GOOGLEPAY';
    protected $logo = 'googlepay.png';
    protected $hasConfigurableDirect = true;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('Google Pay', self::CLASS_NAME);
    }

    public function getTransactionType(): string
    {
        if ($this->isDirect()) {
            $checkoutVars = Tools::getAllValues();
            return empty($checkoutVars['payment_token']) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
        }
        return self::REDIRECT_TYPE;
    }

    public function registerJavascript(Context $context): void
    {
        // To avoid problems with the Google Pay button, we need to load them in the footer area
        if ($this->isDirect()) {
            $gatewayMerchantId = '';

            $context->controller->registerJavascript(
                'module-multisafepay-googlepay-direct-call-javascript',
                'https://pay.google.com/gp/p/js/pay.js',
                [
                    'priority' => 1,
                    'inline' => true,
                    'attributes' => 'async',
                    'server' => 'remote'
                ]
            );

            $context->controller->registerJavascript(
                'module-multisafepay-googlepay-direct-javascript',
                'modules/multisafepayofficial/views/js/multisafepay-googlepay-direct.js',
                [
                    'priority' => 200
                ]
            );

            try {
                /** @var SdkService $sdkService */
                // get the multisafepay.sdk_service service from the container
                $sdkService = $this->module->get('multisafepay.sdk_service');
                $environment = $sdkService->getTestMode() ? 'TEST' : 'LIVE';
                if (!is_null($sdkService->getSdk())) {
                    $accountManager = $sdkService->getSdk()->getAccountManager();
                    $gatewayMerchantId = $accountManager->get()->getAccountId();
                }
            } catch (ApiException|ClientExceptionInterface|Exception $exception) {
                LoggerHelper::logAlert(
                    'Error when try to set the merchant credentials: ' . $exception->getMessage()
                );
                return;
            }

            $totalPrice = $context->cart->getOrderTotal() ?: 0.00;
            $currencyCode = $context->currency->iso_code ?: 'EUR';
            $countryCode = $context->country->iso_code ?: 'NL';
            $merchantName = self::TEST_MERCHANT_NAME;
            $merchantId = self::TEST_MERCHANT_ID;

            if ($environment === 'LIVE') {
                $merchantName = Configuration::get('MULTISAFEPAY_OFFICIAL_MERCHANT_NAME_GOOGLEPAY') ?: '';
                $merchantId = Configuration::get('MULTISAFEPAY_OFFICIAL_MERCHANT_ID_GOOGLEPAY') ?: '';
            }

            Media::addJsDef([
                'configEnvironment'           => $environment,
                'configGatewayMerchantId'     => $gatewayMerchantId,
                'configTotalPrice'            => $totalPrice,
                'configCurrencyCode'          => $currencyCode,
                'configCountryCode'           => $countryCode,
                'configMerchantName'          => $merchantName,
                'configMerchantId'            => $merchantId
            ]);

            parent::registerJavascript($context);
        }
    }

    /**
     * @param Cart $cart
     * @param array $data
     * @return GatewayInfoInterface|null
     *
     * @phpcs:disable -- Disable to avoid trigger a warning in validator about unused parameter
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
        $settings['MULTISAFEPAY_OFFICIAL_MERCHANT_NAME_GOOGLEPAY'] = [
            'type'          => 'text',
            'name'          => $this->module->l('Merchant name', 'BasePaymentOption'),
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
            'name'          => $this->module->l('Merchant ID', 'BasePaymentOption'),
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
