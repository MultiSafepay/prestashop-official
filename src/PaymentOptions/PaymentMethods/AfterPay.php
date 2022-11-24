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
use Country;
use Customer;
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

    public const DEFAULT_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_en/default";
    public const INVOICE_ADDRESS_DE_LOCALE_EN_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/de_en/default";
    public const INVOICE_ADDRESS_DE_LOCALE_DE_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/de_de/default";
    public const INVOICE_ADDRESS_AT_LOCALE_EN_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/at_en/default";
    public const INVOICE_ADDRESS_AT_LOCALE_DE_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/at_de/default";
    public const INVOICE_ADDRESS_CH_LOCALE_EN_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_en/default";
    public const INVOICE_ADDRESS_CH_LOCALE_DE_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_de/default";
    public const INVOICE_ADDRESS_CH_LOCALE_FR_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/ch_fr/default";
    public const INVOICE_ADDRESS_NL_LOCALE_EN_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_en/default";
    public const INVOICE_ADDRESS_NL_LOCALE_NL_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/nl_nl/default";
    public const INVOICE_ADDRESS_BE_LOCALE_EN_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_en/default";
    public const INVOICE_ADDRESS_BE_LOCALE_NL_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_nl/default";
    public const INVOICE_ADDRESS_BE_LOCALE_FR_TERMS_URL = "https://documents.riverty.com/terms_conditions/payment_methods/invoice/be_fr/default";

    protected $gatewayCode = 'AFTERPAY';
    protected $logo = 'riverty.png';
    protected $hasConfigurableDirect = true;
    protected $canProcessRefunds = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l('Riverty', self::CLASS_NAME);
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
                'label'         => $this->module->l('I have read and agreed to the Riverty payment terms.', self::CLASS_NAME),
                'url'           => $this->getTermsAndConditionsUrl(
                    Context::getContext()->language->getLocale(),
                    Context::getContext()->cart->id_address_invoice ?? null
                ),
            ]
        ];
    }

    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        if (empty($data['gender']) && empty($data['birthday'])) {
            return null;
        }

        $gatewayInfo = new Meta();
        $gatewayInfo->addEmailAddressAsString((new Customer($cart->id_customer))->email);
        $gatewayInfo->addPhoneAsString((new Address($cart->id_address_invoice))->phone);
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
     * @param int|null $invoiceAddressId
     *
     * @return string
     */
    private function getTermsAndConditionsUrl(string $locale, ?int $invoiceAddressId = null): string
    {
        if (!$invoiceAddressId) {
            return self::DEFAULT_TERMS_URL;
        }

        $address = new Address($invoiceAddressId);
        $countryIsoCode = (new Country($address->id_country))->iso_code;

        if ($countryIsoCode === 'AT') {
            if ($locale === 'de-DE') {
                return self::INVOICE_ADDRESS_AT_LOCALE_DE_TERMS_URL;
            }

            return self::INVOICE_ADDRESS_AT_LOCALE_EN_TERMS_URL;
        }

        if ($countryIsoCode === 'BE') {
            if ($locale === 'nl-NL') {
                return self::INVOICE_ADDRESS_BE_LOCALE_NL_TERMS_URL;
            }
            if ($locale === 'fr-FR') {
                return self::INVOICE_ADDRESS_BE_LOCALE_FR_TERMS_URL;
            }
            return self::INVOICE_ADDRESS_BE_LOCALE_EN_TERMS_URL;
        }

        if ($countryIsoCode === 'CH') {
            if ($locale === 'de-DE') {
                return self::INVOICE_ADDRESS_CH_LOCALE_DE_TERMS_URL;
            }

            if ($locale === 'fr-FR') {
                return self::INVOICE_ADDRESS_CH_LOCALE_FR_TERMS_URL;
            }

            return self::INVOICE_ADDRESS_CH_LOCALE_EN_TERMS_URL;
        }

        if ($countryIsoCode === 'DE') {
            if ($locale === 'de-DE') {
                return self::INVOICE_ADDRESS_DE_LOCALE_DE_TERMS_URL;
            }

            return self::INVOICE_ADDRESS_DE_LOCALE_EN_TERMS_URL;
        }

        if ($countryIsoCode === 'NL') {
            if ($locale === 'nl-NL') {
                return self::INVOICE_ADDRESS_NL_LOCALE_NL_TERMS_URL;
            }
            return self::INVOICE_ADDRESS_NL_LOCALE_EN_TERMS_URL;
        }

        return self::DEFAULT_TERMS_URL;
    }
}
