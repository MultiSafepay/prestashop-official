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

namespace MultiSafepay\PrestaShop\PaymentOptions\Base;

use Address;
use Carrier;
use Cart;
use Configuration;
use Context;
use Country;
use Currency;
use Group;
use Media;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\PrestaShop\Services\TokenizationService;
use MultisafepayOfficial;
use PrestaShopDatabaseException;
use PrestaShopException;

abstract class BasePaymentOption implements BasePaymentOptionInterface
{
    public const CLASS_NAME = 'BasePaymentOption';
    public const MULTISAFEPAY_COMPONENT_JS_URL  = 'https://pay.multisafepay.com/sdk/components/v2/components.js';
    public const MULTISAFEPAY_COMPONENT_CSS_URL = 'https://pay.multisafepay.com/sdk/components/v2/components.css';
    public const REDIRECT_TYPE = 'redirect';
    public const DIRECT_TYPE = 'direct';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $gatewayCode = '';

    /**
     * @var string
     */
    protected $logo = '';

    /**
     * @var int
     */
    protected $sortOrderPosition = null;

    /**
     * @var bool
     */
    protected $canProcessRefunds = true;

    /**
     * @var bool
     */
    protected $hasConfigurableDirect = false;

    /**
     * @var bool
     */
    protected $hasConfigurableTokenization = false;

    /**
     * @var bool
     */
    protected $hasConfigurablePaymentComponent = false;

    /**
     * @var MultisafepayOfficial
     */
    public $module;

    public function __construct(MultisafepayOfficial $module)
    {
        $this->module = $module;
    }

    public function getGatewayCode(): string
    {
        return $this->gatewayCode;
    }

    public function canProcessRefunds(): bool
    {
        return $this->canProcessRefunds;
    }

    public function getDescription(): string
    {
        return Configuration::get('MULTISAFEPAY_OFFICIAL_DESCRIPTION_'.$this->getUniqueName()) ?: $this->description;
    }

    public function getAction(): string
    {
        return Context::getContext()->link->getModuleLink('multisafepayofficial', 'payment', [], true);
    }

    public function getTransactionType(): string
    {
        if ($this->isDirect()) {
            return self::DIRECT_TYPE;
        }

        return self::REDIRECT_TYPE;
    }

    public function getFrontEndName(): string
    {
        return Configuration::get('MULTISAFEPAY_OFFICIAL_TITLE_'.$this->getUniqueName()) ?: $this->getName();
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function getInputFields(): array
    {
        $inputFields = [];

        if ($this->allowTokenization() && !$this->allowPaymentComponent()) {
            /** @var TokenizationService $tokenizationService */
            $tokenizationService = $this->module->get('multisafepay.tokenization_service');
            $inputFields         = array_merge(
                $inputFields,
                $tokenizationService->createTokenizationCheckoutFields(
                    (string)Context::getContext()->customer->id,
                    $this
                )
            );
        }

        if ($this->isDirect()) {
            $inputFields = array_merge($inputFields, $this->getDirectTransactionInputFields());
        }

        if ($this->allowTokenization() && !$this->allowPaymentComponent()) {
            /** @var TokenizationService $tokenizationService */
            $tokenizationService = $this->module->get('multisafepay.tokenization_service');
            $inputFields         = array_merge(
                $inputFields,
                $tokenizationService->createTokenizationSavePaymentDetailsCheckbox()
            );
        }

        return $inputFields;
    }

    public function sortInputFields(array $inputFields): array
    {
        uasort(
            $inputFields,
            function ($a, $b) {
                return $a['order'] - $b['order'];
            }
        );

        return $inputFields;
    }

    /**
     * @return array
     */
    public function getDirectTransactionInputFields(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getUniqueName(): string
    {
        return $this->getGatewayCode();
    }

    /**
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getSortOrderPosition(): int
    {
        if (!isset($this->sortOrderPosition)) {
            $this->sortOrderPosition = (int)Configuration::get(
                'MULTISAFEPAY_OFFICIAL_SORT_ORDER_'.$this->getUniqueName()
            );
        }

        return $this->sortOrderPosition;
    }

    /**
     * @return array
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getGatewaySettings(): array
    {
        $settings = [
            'MULTISAFEPAY_OFFICIAL_GATEWAY_'.$this->getUniqueName()         => [
                'type'    => 'switch',
                'name'    => $this->getName(),
                'value'   => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_'.$this->getUniqueName()),
                'default' => '0',
                'order'   => 10,
            ],
            'MULTISAFEPAY_OFFICIAL_TITLE_'.$this->getUniqueName()           => [
                'type'       => 'text',
                'name'       => $this->module->l('Title', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_TITLE_'.$this->getUniqueName()),
                'helperText' => $this->module->l('The title will be shown to the customer at the checkout page. When using translations, please leave this field empty.', self::CLASS_NAME),
                'default'    => '',
                'order'      => 20,
            ],
            'MULTISAFEPAY_OFFICIAL_DESCRIPTION_'.$this->getUniqueName()     => [
                'type'       => 'text',
                'name'       => $this->module->l('Description', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_DESCRIPTION_'.$this->getUniqueName()),
                'helperText' => $this->module->l(
                    'The description will be shown to the customer at the checkout page.',
                    self::CLASS_NAME
                ),
                'default'    => '',
                'order'      => 30,
            ],
            'MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_'.$this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Minimum amount', self::CLASS_NAME),
                'value'   => Configuration::get('MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_'.$this->getUniqueName()),
                'default' => '',
                'order'   => 40,
            ],
            'MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_'.$this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Maximum amount', self::CLASS_NAME),
                'value'   => Configuration::get('MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_'.$this->getUniqueName()),
                'default' => '',
                'order'   => 50,
            ],
            'MULTISAFEPAY_OFFICIAL_COUNTRIES_'.$this->getUniqueName()       => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select countries', self::CLASS_NAME),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_OFFICIAL_COUNTRIES_'.$this->getUniqueName())
                ),
                'options'    => $this->mapArrayForSettings(
                    Country::getCountries((int)Context::getContext()->language->id, true),
                    'id_country'
                ),
                'helperText' => $this->module->l('Leave blank to support all countries', self::CLASS_NAME),
                'default'    => '',
                'order'      => 60,
            ],
            'MULTISAFEPAY_OFFICIAL_CURRENCIES_'.$this->getUniqueName()      => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select currencies', self::CLASS_NAME),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_OFFICIAL_CURRENCIES_'.$this->getUniqueName())
                ),
                'options'    => Currency::getCurrencies(false, true, true),
                'helperText' => $this->module->l('Leave blank to support all currencies', self::CLASS_NAME),
                'default'    => '',
                'order'      => 70,
            ],
            'MULTISAFEPAY_OFFICIAL_CUSTOMER_GROUPS_'.$this->getUniqueName() => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select customer groups', self::CLASS_NAME),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_OFFICIAL_CUSTOMER_GROUPS_'.$this->getUniqueName())
                ),
                'options'    => $this->mapArrayForSettings(
                    Group::getGroups((int)Context::getContext()->language->id),
                    'id_group'
                ),
                'helperText' => $this->module->l('Leave blank to support all customer groups', self::CLASS_NAME),
                'default'    => '',
                'order'      => 80,
            ],
            'MULTISAFEPAY_OFFICIAL_CARRIERS_'.$this->getUniqueName()        => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select carriers', self::CLASS_NAME),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_OFFICIAL_CARRIERS_'.$this->getUniqueName())
                ),
                'options'    => $this->mapArrayForSettings(
                    Carrier::getCarriers(
                        Context::getContext()->language->id,
                        false,
                        false,
                        false,
                        null,
                        Carrier::ALL_CARRIERS
                    ),
                    'id_carrier'
                ),
                'helperText' => $this->module->l('Leave blank to support all carriers', self::CLASS_NAME),
                'default'    => '',
                'order'      => 81,
            ],
            'MULTISAFEPAY_OFFICIAL_SORT_ORDER_'.$this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Sort order', self::CLASS_NAME),
                'value'   => Configuration::get('MULTISAFEPAY_OFFICIAL_SORT_ORDER_'.$this->getUniqueName()),
                'default' => '',
                'order'   => 90,
                'class'   => 'sort-order',
            ]
        ];

        if ($this->hasConfigurableDirect) {
            $settings['MULTISAFEPAY_OFFICIAL_DIRECT_'.$this->getUniqueName()] = [
                'type'       => 'switch',
                'name'       => $this->module->l('Enable direct', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_DIRECT_'.$this->getUniqueName()),
                'helperText' => $this->module->l(
                    'If enabled, additional information can be entered during checkout. If disabled, additional information will be requested on the MultiSafepay payment page.',
                    self::CLASS_NAME
                ),
                'default'    => '1',
                'order'      => 11,
            ];
        }

        if ($this->hasConfigurableTokenization) {
            $settings['MULTISAFEPAY_OFFICIAL_TOKENIZATION_'.$this->getUniqueName()] = [
                'type'       => 'switch',
                'name'       => $this->module->l('Enable tokenization', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_TOKENIZATION_'.$this->getUniqueName()) ?? 0,
                'helperText' => $this->module->l(
                    'If enabled, payment details entered during checkout can be saved by the customer for future purchases.',
                    self::CLASS_NAME
                ),
                'default'    => '0',
                'order'      => 14,
            ];
        }

        if ($this->hasConfigurablePaymentComponent) {
            $settings['MULTISAFEPAY_OFFICIAL_COMPONENT_'.$this->getUniqueName()] = [
                'type'       => 'switch',
                'name'       => $this->module->l('Enable payment component', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_COMPONENT_'.$this->getUniqueName()) ?? 0,
                'helperText' => $this->module->l(
                    'If enabled, embedded form will be used during checkout.',
                    self::CLASS_NAME
                ),
                'default'    => '0',
                'order'      => 15,
            ];
        }

        if (!empty($this->getPaymentOptionSettingsFields())) {
            $settings = array_merge($this->getPaymentOptionSettingsFields(), $settings);
        }

        return $this->sortInputFields($settings);
    }

    /**
     * @param string $setting
     *
     * @return array
     */
    protected function settingToArray($setting): array
    {
        if (is_string($setting) && !empty($setting)) {
            return json_decode($setting);
        }

        return [];
    }

    /**
     * @param array $list
     * @param string $idKey
     *
     * @return array
     */
    protected function mapArrayForSettings(array $list, string $idKey)
    {
        $result = [];
        foreach ($list as $item) {
            $resultItem         = [];
            $resultItem['id']   = $item[$idKey];
            $resultItem['name'] = $item['name'];
            $result[]           = $resultItem;
        }

        return $result;
    }

    public function isActive(): bool
    {
        return Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_'.$this->getUniqueName()) === '1';
    }

    /**
     * @param Cart $cart
     * @param array $data
     *
     * @return GatewayInfoInterface|null
     *
     * @phpcs:disable -- Disable to avoid trigger a warning in validator about unused parameter
     */
    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        // phpcs:enable
        return null;
    }

    /**
     * @return bool
     */
    public function isDirect(): bool
    {
        if ($this->hasConfigurableDirect) {
            return (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DIRECT_'.$this->getUniqueName());
        }

        return false;
    }

    /**
     * @return bool
     */
    public function allowTokenization(): bool
    {
        $customer = Context::getContext()->customer;
        if ($this->hasConfigurableTokenization && $customer !== null && !(bool)$customer->is_guest) {
            return (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_TOKENIZATION_'.$this->getUniqueName());
        }

        return false;
    }

    /**
     * @return bool
     */
    public function allowPaymentComponent(): bool
    {
        if ($this->hasConfigurablePaymentComponent) {
            return (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_COMPONENT_'.$this->getUniqueName());
        }

        return false;
    }

    /**
     * @param Context $context
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function registerJavascript(Context $context): void
    {
        if ($this->allowPaymentComponent()) {
            $context->controller->registerJavascript(
                'module-multisafepay-payment-component-javascript',
                self::MULTISAFEPAY_COMPONENT_JS_URL,
                [
                    'server' => 'remote',
                ]
            );

            /** @var OrderService $orderService */
            $orderService = $this->module->get('multisafepay.order_service');

            Media::addJsDef(
                [
                    'multisafepayPaymentComponentConfig'.$this->getGatewayCode(
                    ) => $orderService->createPaymentComponentOrder(
                        $this->getGatewayCode(),
                        $this->allowTokenization() ? (string) Context::getContext()->customer->id : null,
                        $this->allowTokenization() ? 'cardOnFile' : null
                    )
                ]
            );

            $context->controller->registerJavascript(
                'module-multisafepay-initialize-payment-component-javascript',
                'modules/multisafepayofficial/views/js/multisafepayofficial.js'
            );
        }
    }

    /**
     * @param Context $context
     *
     * @return void
     */
    public function registerCss(Context $context): void
    {
        if ($this->allowPaymentComponent()) {
            $context->controller->registerStylesheet(
                'module-multisafepay-payment-component',
                self::MULTISAFEPAY_COMPONENT_CSS_URL,
                [
                    'server' => 'remote',
                ]
            );
        }
    }

    /**
     * Return the country code from the context object
     *
     * @param Context $context
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCountryCode(Context $context): string
    {
        $idCountry = (new Address((int)$context->cart->id_address_invoice))->id_country;
        $country = (new Country($idCountry));

        return $country->iso_code;
    }

    /**
     * @return array
     */
    public function getPaymentOptionSettingsFields(): array
    {
        return [];
    }
}
