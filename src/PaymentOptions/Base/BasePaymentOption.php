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
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\Base;

use Address;
use Carrier;
use Cart;
use Configuration;
use Context;
use Country;
use Currency;
use Exception;
use Group;
use Language;
use Media;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\Helper\PathHelper;
use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\PrestaShop\Services\TokenizationService;
use MultisafepayOfficial;
use PrestaShopDatabaseException;
use PrestaShopException;
use Psr\Http\Client\ClientExceptionInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BasePaymentOption
{
    /**
     * @var string
     */
    public const CLASS_NAME = 'BasePaymentOption';

    /**
     * @var string
     */
    public const MULTISAFEPAY_COMPONENT_JS_URL = 'https://pay.multisafepay.com/sdk/components/v2/components.js';

    /**
     * @var string
     */
    public const MULTISAFEPAY_COMPONENT_CSS_URL = 'https://pay.multisafepay.com/sdk/components/v2/components.css';

    /**
     * @var array
     */
    public const CANNOT_PROCESS_REFUNDS_GIFTCARDS = [
        'BABYCAD', 'BEAUTYANDWELLNESS', 'BOEKENBON', 'FASHIONCHEQUE',
        'FASHIONGIFTCARD', 'FIETSENBON', 'GEZONDHEIDSBON', 'GIVACARD',
        'GOOD4FUN', 'GOODCARD', 'NATIONALETUINBON', 'PARFUMCADEAUKAART',
        'PODIUM', 'SPORTENFIT', 'VVVGIFTCRD', 'WEBSHOPGIFTCARD',
        'WELLNESSGIFTCARD', 'WIJNCADEAU', 'WINKELCHEQUE', 'YOURGIFT'
    ];

    /**
     * @var array
     */
    public const CANNOT_PROCESS_REFUNDS_PAYMENT_METHODS = [
        'AFTERPAY', 'EINVOICE', 'IN3', 'IN3B2B', 'KLARNA', 'PAYAFTER'
    ];

    /**
     * @var string
     */
    public $gatewayCode = '';

    /**
     * @var string
     */
    public $gatewayName = '';

    /**
     * @var string
     */
    public $parentGateway = '';

    /**
     * @var string
     */
    public $parentName = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var bool
     */
    protected $canProcessRefunds = true;

    /**
     * @var bool
     */
    public $hasConfigurableDirect = false;

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

    /**
     * @var PaymentMethod
     */
    private $paymentMethod;

    /**
     * @var float
     */
    private $maxAmount;

    /**
     * @var float
     */
    private $minAmount;

    public function __construct(PaymentMethod $paymentMethod, MultisafepayOfficial $module)
    {
        $this->paymentMethod = $paymentMethod;
        $this->module = $module;
        $this->gatewayCode = $this->paymentMethod->getId();
        $this->gatewayName = $this->paymentMethod->getName() ?: '';
        $this->description = $this->getDescription();
        $this->canProcessRefunds = $this->canProcessRefunds();
        $this->hasConfigurableTokenization = $this->paymentMethod->supportsTokenization();
        $this->hasConfigurablePaymentComponent = $this->paymentMethod->supportsPaymentComponent();
        $this->maxAmount = $this->paymentMethod->getMaxAmount() ?: 0.0;
        $this->minAmount = $this->paymentMethod->getMinAmount() ?: 0.0;
    }

    /**
     * @param bool $fromCheckout
     *
     * @return string
     */
    public function getGatewayCode(bool $fromCheckout = false): string
    {
        return $this->gatewayCode;
    }

    /**
     * This sanitization is necessary to avoid XSS attacks
     * while &amp; is being replaced by '&', so payment methods
     * including that character will be displayed correctly
     *
     * @return string
     */
    public function getName(): string
    {
        $escapedName = htmlspecialchars(
            $this->gatewayName,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
            false
        );
        return str_replace('&amp;', '&', $escapedName);
    }

    /**
     * @return string
     */
    public function getBrandName(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getUniqueName(): string
    {
        return $this->gatewayCode;
    }

    /**
     * @return string
     */
    public function getPaymentComponentId(): string
    {
        return $this->getUniqueName();
    }

    /**
     * @return array
     */
    public function getAllowedCountries(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    public function canProcessRefunds(): bool
    {
        if (in_array($this->gatewayCode, self::CANNOT_PROCESS_REFUNDS_GIFTCARDS, true) ||
            in_array($this->gatewayCode, self::CANNOT_PROCESS_REFUNDS_PAYMENT_METHODS, true)) {
            return false;
        }
        return !$this->paymentMethod->isShoppingCartRequired() && $this->paymentMethod->getType() !== 'COUPON';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return Configuration::get('MULTISAFEPAY_OFFICIAL_DESCRIPTION_' . $this->getUniqueName()) ?: $this->description;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return Context::getContext()->link->getModuleLink('multisafepayofficial', 'payment', [], true);
    }

    /**
     * @return bool
     */
    public function isDirect(): bool
    {
        if ($this->allowPaymentComponent()) {
            return true;
        }

        if ($this->hasConfigurableDirect) {
            return (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_DIRECT_' . $this->getUniqueName());
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTransactionType(): string
    {
        if ($this->isDirect()) {
            return OrderRequest::DIRECT_TYPE;
        }

        return OrderRequest::REDIRECT_TYPE;
    }

    /**
     * Returns the maximum allowed amount for this payment method
     *
     * @return float
     */
    public function getMaxAmount(): float
    {
        // Handle null, non-numeric
        // values, and invalid types
        if (!is_numeric($this->maxAmount)) {
            return 0.0;
        }
        $amount = (float)$this->maxAmount;

        return $amount / 100;
    }

    /**
     * Returns the minimum allowed amount for this payment method
     *
     * @return float
     */
    public function getMinAmount(): float
    {
        if (!is_numeric($this->minAmount)) {
            return 0.0;
        }
        $amount = (float)$this->minAmount;

        return $amount / 100;
    }

    /**
     * Get the frontend display name for the payment method
     *
     * @param int|null $langId Language ID. If null, uses current context language
     * @return string
     */
    public function getFrontEndName(?int $langId = null): string
    {
        // If no language ID provided, use default language
        if (is_null($langId)) {
            $langId = (int)Configuration::get('PS_LANG_DEFAULT');
        }

        // Get the ISO code for the language
        $langIsoCode = Language::getIsoById($langId);
        $baseConfigKey = 'MULTISAFEPAY_OFFICIAL_TITLE_' . $this->getUniqueName();

        // Step 1: Try language-specific title first
        if ($langIsoCode) {
            $langCode = strtoupper(trim($langIsoCode));

            // Validate language code format (2-letter ISO code)
            if (!empty($langCode) && strlen($langCode) === 2 && ctype_alpha($langCode)) {
                $langConfigKey = $baseConfigKey . '_' . $langCode;
                $languageSpecificTitle = Configuration::get($langConfigKey);

                // Check if value exists and is not null/empty
                if (!empty($languageSpecificTitle)) {
                    return $languageSpecificTitle;
                }
            }
        }

        // Step 2: Try base title
        $baseTitle = Configuration::get($baseConfigKey);
        if (!empty($baseTitle)) {
            return $baseTitle;
        }

        // Step 3: Final fallback to default name
        return $this->getName();
    }

    public function getLogo(): string
    {
        return $this->paymentMethod->getMediumIconUrl() ?: '';
    }

    /**
     *  Get the input fields for the payment methods in the front end
     *  Used in views/templates/front/form.tpl
     *  @noinspection PhpUnused
     *
     * @throws ClientExceptionInterface
     * @throws Exception
     */
    public function getInputFields(): array
    {
        $inputFields = [];

        if ($this->allowTokenization() && !$this->allowPaymentComponent()) {
            $tokenizationService = new TokenizationService($this->module, new SdkService());
            $inputFields         = array_merge(
                $inputFields,
                $tokenizationService->createTokenizationCheckoutFields(
                    (string)Context::getContext()->customer->id,
                    $this
                )
            );
        }

        if ($this->allowTokenization() && !$this->allowPaymentComponent()) {
            $tokenizationService = new TokenizationService($this->module, new SdkService());
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
            static function ($a, $b) {
                $orderA = $a['order'] ?? 0;
                $orderB = $b['order'] ?? 0;
                return $orderA - $orderB;
            }
        );

        return $inputFields;
    }

    /**
     * @used by PaymentOptionService::sortOrderPaymentOptions()
     *
     * @return int
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getSortOrderPosition(): int
    {
        return (int)Configuration::get('MULTISAFEPAY_OFFICIAL_SORT_ORDER_' . $this->getUniqueName());
    }

    /**
     * @return array
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getGatewaySettings(): array
    {
        $settings = [
            'MULTISAFEPAY_OFFICIAL_GATEWAY_' . $this->getUniqueName()         => [
                'type'    => 'switch',
                'name'    => $this->getName(),
                'value'   => Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_' . $this->getUniqueName()),
                'default' => '0',
                'order'   => 10,
            ],
        ];

        // Add multi-language title settings (includes base title)
        $settings = array_merge($settings, $this->getMultiLanguageTitleSettings());

        $settings = array_merge($settings, [
            'MULTISAFEPAY_OFFICIAL_DESCRIPTION_' . $this->getUniqueName()     => [
                'type'       => 'text',
                'name'       => $this->module->l('Description', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_DESCRIPTION_' . $this->getUniqueName()),
                'helperText' => $this->module->l(
                    'The description will be shown to the customer at the checkout page.',
                    self::CLASS_NAME
                ),
                'default'    => '',
                'order'      => 30,
            ],
            'MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_' . $this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Minimum amount', self::CLASS_NAME),
                'value'   => Configuration::get('MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_' . $this->getUniqueName()),
                'default' => '',
                'order'   => 40,
            ],
            'MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_' . $this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Maximum amount', self::CLASS_NAME),
                'value'   => Configuration::get('MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_' . $this->getUniqueName()),
                'default' => '',
                'order'   => 50,
            ],
            'MULTISAFEPAY_OFFICIAL_COUNTRIES_' . $this->getUniqueName()       => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select countries', self::CLASS_NAME),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_OFFICIAL_COUNTRIES_' . $this->getUniqueName())
                ),
                'options'    => $this->mapArrayForSettings(
                    Country::getCountries(Context::getContext()->language->id, true),
                    'id_country'
                ),
                'helperText' => $this->module->l('Leave blank to support all countries', self::CLASS_NAME),
                'default'    => '',
                'order'      => 60,
            ],
            'MULTISAFEPAY_OFFICIAL_CURRENCIES_' . $this->getUniqueName()      => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select currencies', self::CLASS_NAME),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_OFFICIAL_CURRENCIES_' . $this->getUniqueName())
                ),
                'options'    => Currency::getCurrencies(false, true, true),
                'helperText' => $this->module->l('Leave blank to support all currencies', self::CLASS_NAME),
                'default'    => '',
                'order'      => 70,
            ],
            'MULTISAFEPAY_OFFICIAL_CUSTOMER_GROUPS_' . $this->getUniqueName() => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select customer groups', self::CLASS_NAME),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_OFFICIAL_CUSTOMER_GROUPS_' . $this->getUniqueName())
                ),
                'options'    => $this->mapArrayForSettings(
                    Group::getGroups(Context::getContext()->language->id),
                    'id_group'
                ),
                'helperText' => $this->module->l('Leave blank to support all customer groups', self::CLASS_NAME),
                'default'    => '',
                'order'      => 80,
            ],
            'MULTISAFEPAY_OFFICIAL_CARRIERS_' . $this->getUniqueName()        => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select carriers', self::CLASS_NAME),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_OFFICIAL_CARRIERS_' . $this->getUniqueName())
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
            'MULTISAFEPAY_OFFICIAL_SORT_ORDER_' . $this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Sort order', self::CLASS_NAME),
                'value'   => Configuration::get('MULTISAFEPAY_OFFICIAL_SORT_ORDER_' . $this->getUniqueName()),
                'default' => '',
                'order'   => 90,
                'class'   => 'sort-order',
            ]
        ]);

        if ($this->hasConfigurableDirect) {
            $settings['MULTISAFEPAY_OFFICIAL_DIRECT_' . $this->getUniqueName()] = [
                'type'       => 'switch',
                'name'       => $this->module->l('Enable direct', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_DIRECT_' . $this->getUniqueName()),
                'helperText' => $this->module->l(
                    'If enabled, additional information can be entered during checkout. If disabled, additional information will be requested on the MultiSafepay payment page.',
                    self::CLASS_NAME
                ),
                'default'    => '1',
                'order'      => 11,
            ];
        }

        if ($this->hasConfigurableTokenization) {
            $settings['MULTISAFEPAY_OFFICIAL_TOKENIZATION_' . $this->getUniqueName()] = [
                'type'       => 'switch',
                'name'       => $this->module->l('Enable tokenization', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_TOKENIZATION_' . $this->getUniqueName()) ?: '0',
                'helperText' => $this->module->l(
                    'If enabled, payment details entered during checkout can be saved by the customer for future purchases.',
                    self::CLASS_NAME
                ),
                'default'    => '0',
                'order'      => 14,
            ];
        }

        if ($this->hasConfigurablePaymentComponent) {
            $settings['MULTISAFEPAY_OFFICIAL_COMPONENT_' . $this->getUniqueName()] = [
                'type'       => 'switch',
                'name'       => $this->module->l('Enable payment component', self::CLASS_NAME),
                'value'      => Configuration::get('MULTISAFEPAY_OFFICIAL_COMPONENT_' . $this->getUniqueName()) ?: '0',
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
    protected function settingToArray(string $setting): array
    {
        if (!empty($setting)) {
            return (array) (json_decode($setting, false) ?? []);
        }

        return [];
    }

    /**
     * @param array $list
     * @param string $idKey
     *
     * @return array
     */
    protected function mapArrayForSettings(array $list, string $idKey): array
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
        return Configuration::get('MULTISAFEPAY_OFFICIAL_GATEWAY_' . $this->getUniqueName()) === '1';
    }

    /**
     * @param Cart $cart
     * @param array $data
     *
     * @return GatewayInfoInterface|null
     */
    public function getGatewayInfo(Cart $cart, array $data = []): ?GatewayInfoInterface
    {
        return null;
    }

    /**
     * @return bool
     */
    public function allowTokenization(): bool
    {
        $customer = Context::getContext()->customer;
        if ($this->hasConfigurableTokenization && ($customer !== null) && empty($customer->is_guest)) {
            return (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_TOKENIZATION_' . $this->getUniqueName());
        }

        return false;
    }

    /**
     * @return bool
     */
    public function allowPaymentComponent(): bool
    {
        if ($this->hasConfigurablePaymentComponent) {
            return (bool)Configuration::get('MULTISAFEPAY_OFFICIAL_COMPONENT_' . $this->getUniqueName());
        }

        return false;
    }

    /**
     * @param Context $context
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function registerJavascript(Context $context): void
    {
        if ($this->allowPaymentComponent()) {
            $context->controller->registerJavascript(
                'module-multisafepay-payment-component-javascript',
                self::MULTISAFEPAY_COMPONENT_JS_URL,
                [
                    'server' => 'remote'
                ]
            );

            $orderService = new OrderService($this->module, new SdkService());

            Media::addJsDef(
                [
                    'multisafepayPaymentComponentConfig' . $this->getGatewayCode(
                    ) => $orderService->createPaymentComponentOrder(
                        $this->getGatewayCode(),
                        $this->allowTokenization() ? (string) Context::getContext()->customer->id : null,
                        $this->allowTokenization() ? 'cardOnFile' : null
                    )
                ]
            );

            $context->controller->registerJavascript(
                'module-multisafepay-initialize-payment-component-javascript',
                PathHelper::getAssetPath('multisafepayofficial.js')
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
     * @return array
     */
    public function getPaymentOptionSettingsFields(): array
    {
        return [];
    }

    /**
     * Return the country code from the context object
     *
     * @param Context $context
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getCountryCode(Context $context): string
    {
        $idCountry = (new Address((int)$context->cart->id_address_invoice))->id_country;
        $country = (new Country($idCountry));

        if (!empty($country->iso_code)) {
            return $country->iso_code;
        }

        return '';
    }

    /**
     * Generate multi-language title configuration settings including base title
     *
     * @return array
     */
    private function getMultiLanguageTitleSettings(): array
    {
        $settings = [];
        $languages = Language::getLanguages();

        // Filter out any non-array elements
        $languages = array_filter($languages, function ($language) {
            return is_array($language) &&
                isset($language['iso_code']) &&
                isset($language['name']) &&
                isset($language['id_lang']);
        });
        $languageCount = count($languages);

        $baseText = $this->module->l('The title will be shown to the customer at the checkout page. When using translations, please leave this field empty.', self::CLASS_NAME);
        $additionalText = '';
        // Determine helper text based on available languages
        if ($languageCount > 1) {
            $additionalText = sprintf(
                $this->module->l(' You can also customize titles for the %d configured languages.', self::CLASS_NAME),
                $languageCount
            );
        }
        $titleHelperText = $baseText . $additionalText;
        $baseFieldName = 'MULTISAFEPAY_OFFICIAL_TITLE_' . $this->getUniqueName();

        // Add base title first
        $settings[$baseFieldName] = [
            'type'       => 'text',
            'name'       => $this->module->l('Title', self::CLASS_NAME),
            'value'      => Configuration::get($baseFieldName),
            'helperText' => $titleHelperText,
            'default'    => '',
            'order'      => 20,
            // Metadata for template logic
            'isTitleField' => true,
            'isBaseTitle' => true,
            'isLanguageSpecificTitle' => false,
            'baseFieldName' => $baseFieldName,
            'languageCode' => '',
        ];

        foreach ($languages as $language) {
            $langCode = strtoupper(trim($language['iso_code']));

            // Validate language code format (2-letter ISO code) - extra safety
            if (empty($langCode) || strlen($langCode) !== 2 || !ctype_alpha($langCode)) {
                continue; // Skip invalid language codes
            }

            $configKey = $baseFieldName . '_' . $langCode;

            // Use language name as it comes from PrestaShop, but clean parentheses content
            $languageName = trim($language['name']);

            // Remove everything between parentheses, including them
            $openParen = strpos($languageName, '(');
            if ($openParen !== false) {
                $languageName = trim(substr($languageName, 0, $openParen));
            }

            // Fallback to language code if name is empty
            if (empty($languageName)) {
                $languageName = $langCode;
            }

            // Add language titles later
            $setting = [
                'type'       => 'text',
                'name'       => $this->module->l('Title', self::CLASS_NAME) . ' (' . $languageName . ')',
                'value'      => Configuration::get($configKey),
                'default'    => '',
                'order'      => 20 + (int)$language['id_lang'],
                // Metadata for template logic
                'isTitleField' => true,
                'isBaseTitle' => false,
                'isLanguageSpecificTitle' => true,
                'baseFieldName' => $baseFieldName,
                'languageCode' => $langCode,
            ];
            $settings[$configKey] = $setting;
        }

        return $settings;
    }
}
