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

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Meta;
use Tools;
use Order;
use Address;
use Context;

class AfterPay extends BasePaymentOption
{
    public const CLASS_NAME = 'AfterPay';
    public const DEFAULT_TERMS_AND_CONDITIONS = "https://www.afterpay.nl/en/about/pay-with-afterpay/payment-conditions";
    public const NL_TERMS_AND_CONDITIONS = "https://www.afterpay.nl/nl/algemeen/betalen-met-afterpay/betalingsvoorwaarden";
    public const BE_NL_TERMS_AND_CONDITIONS = "https://www.afterpay.be/be/footer/betalen-met-afterpay/betalingsvoorwaarden";
    public const BE_FR_TERMS_AND_CONDITIONS = "https://www.afterpay.be/fr/footer/payer-avec-afterpay/conditions-de-paiement";

    protected $gatewayCode = 'AFTERPAY';
    protected $logo = 'afterpay.png';
    protected $hasConfigurableDirect = true;
    protected $canProcessRefunds = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('AfterPay', self::CLASS_NAME);
    }

    /**
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getTransactionType(): string
    {
        $checkoutVars = Tools::getAllValues();
        return (empty($checkoutVars['gender']) || empty($checkoutVars['birthday']) || !isset($checkoutVars['terms-conditions'])) ? self::REDIRECT_TYPE : self::DIRECT_TYPE;
    }

    public function getDirectTransactionInputFields(): array
    {
        return [
            [
                'type'          => 'select',
                'name'          => 'gender',
                'placeholder'   => $this->module->l('Salutation', self::CLASS_NAME),
                'options'       => [
                    [
                        'value' => 'male',
                        'name'  => 'Mr.',
                    ],
                    [
                        'value' => 'female',
                        'name'  => 'Mrs.',
                    ],
                ],
            ],
            [
                'type'          => 'date',
                'name'          => 'birthday',
                'placeholder'   => $this->module->l('Birthday', self::CLASS_NAME),
                'value'         => Context::getContext()->customer->birthday ?? '',
            ],
            [
                'type'          => 'checkbox',
                'name'          => 'terms-conditions',
                'label'         => $this->module->l('I have read and agreed to the AfterPay payment terms.', self::CLASS_NAME),
                'url'           => $this->getTermsAndConditionsUrl(Context::getContext()->language->getLocale()),
            ]
        ];
    }

    public function getGatewayInfo(Order $order, array $data = []): ?GatewayInfoInterface
    {
        if (empty($data['gender']) && empty($data['birthday'])) {
            return null;
        }

        $gatewayInfo = new Meta();
        $gatewayInfo->addEmailAddressAsString($order->getCustomer()->email);
        $gatewayInfo->addPhoneAsString((new Address($order->id_address_invoice))->phone);
        if (!empty($data['gender'])) {
            $gatewayInfo->addGenderAsString($data['gender']);
        }
        if (!empty($data['birthday'])) {
            $gatewayInfo->addBirthdayAsString($data['birthday']);
        }

        return $gatewayInfo;
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    private function getTermsAndConditionsUrl(string $locale): string
    {
        if ($locale === 'nl-NL') {
            return self::NL_TERMS_AND_CONDITIONS;
        }

        if ($locale === 'be-NL') {
            return self::BE_NL_TERMS_AND_CONDITIONS;
        }

        if ($locale === 'be-FR') {
            return self::BE_FR_TERMS_AND_CONDITIONS;
        }

        return self::DEFAULT_TERMS_AND_CONDITIONS;
    }
}
