<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\Base;

use Carrier;
use Configuration;
use Context;
use Country;
use Currency;
use Group;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use MultiSafepay\PrestaShop\Services\TokenizationService;
use Multisafepay;
use Order;

abstract class BasePaymentOption implements BasePaymentOptionInterface
{
    public const REDIRECT_TYPE = 'redirect';
    public const DIRECT_TYPE = 'direct';

    /**
     * @var string
     */
    protected $name = '';

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
     * @var Multisafepay
     */
    public $module;

    public function __construct(Multisafepay $module)
    {
        $this->module = $module;
    }

    public function getName(): string
    {
        return $this->name;
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
        return Configuration::get('MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName()) ?: $this->description;
    }

    public function getAction(): string
    {
        return Context::getContext()->link->getModuleLink('multisafepay', 'payment', [], true);
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
        return Configuration::get('MULTISAFEPAY_TITLE_'.$this->getUniqueName()) ?: $this->getName();
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function getInputFields(): array
    {
        $inputFields = [
            [
                'type'  => 'hidden',
                'name'  => 'gateway',
                'value' => $this->getGatewayCode(),
                'order' => 100,
            ],
        ];

        if ($this->isDirect()) {
            $inputFields = array_merge($inputFields, $this->getDirectTransactionInputFields());
        }

        if ($this->allowTokenization()) {
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

        return $this->sortInputFields($inputFields);
    }

    public function sortInputFields(array $inputFields): array
    {
        uasort($inputFields, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $inputFields;
    }

    /**
     * Override this function if you need input fields for a direct payment method, also set $hasConfigurableDirect to true
     *
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

    public function getSortOrderPosition(): int
    {
        if (!isset($this->sortOrderPosition)) {
            $this->sortOrderPosition = (int)Configuration::get('MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName());
        }

        return $this->sortOrderPosition;
    }

    /**
     * @return array
     */
    public function getGatewaySettings(): array
    {
        $settings = [
            'MULTISAFEPAY_GATEWAY_'.$this->getUniqueName()         => [
                'type'    => 'switch',
                'name'    => $this->name,
                'value'   => Configuration::get('MULTISAFEPAY_GATEWAY_'.$this->getUniqueName()),
                'default' => '0',
                'order'   => 10,
            ],
            'MULTISAFEPAY_TITLE_'.$this->getUniqueName()           => [
                'type'       => 'text',
                'name'       => $this->module->l('Title'),
                'value'      => Configuration::get('MULTISAFEPAY_TITLE_'.$this->getUniqueName()),
                'helperText' => $this->module->l('The title will be shown to the customer at the checkout page.'),
                'default'    => '',
                'order'      => 20,
            ],
            'MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName()     => [
                'type'       => 'text',
                'name'       => $this->module->l('Description'),
                'value'      => Configuration::get('MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName()),
                'helperText' => $this->module->l('The description will be shown to the customer at the checkout page.'),
                'default'    => '',
                'order'      => 30,
            ],
            'MULTISAFEPAY_MIN_AMOUNT_'.$this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Minimum amount'),
                'value'   => Configuration::get('MULTISAFEPAY_MIN_AMOUNT_'.$this->getUniqueName()),
                'default' => '',
                'order'   => 40,
            ],
            'MULTISAFEPAY_MAX_AMOUNT_'.$this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Maximum amount'),
                'value'   => Configuration::get('MULTISAFEPAY_MAX_AMOUNT_'.$this->getUniqueName()),
                'default' => '',
                'order'   => 50,
            ],
            'MULTISAFEPAY_COUNTRIES_'.$this->getUniqueName()       => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select countries'),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_COUNTRIES_'.$this->getUniqueName())
                ),
                'options'    => $this->mapArrayForSettings(
                    Country::getCountries((int)Context::getContext()->language->id, true),
                    'id_country'
                ),
                'helperText' => $this->module->l('Leave blank to support all countries'),
                'default'    => '',
                'order'      => 60,
            ],
            'MULTISAFEPAY_CURRENCIES_'.$this->getUniqueName()      => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select currencies'),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_CURRENCIES_'.$this->getUniqueName())
                ),
                'options'    => Currency::getCurrencies(false, true, true),
                'helperText' => $this->module->l('Leave blank to support all currencies'),
                'default'    => '',
                'order'      => 70,
            ],
            'MULTISAFEPAY_CUSTOMER_GROUPS_'.$this->getUniqueName() => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select customer groups'),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_CUSTOMER_GROUPS_'.$this->getUniqueName())
                ),
                'options'    => $this->mapArrayForSettings(
                    Group::getGroups((int)Context::getContext()->language->id),
                    'id_group'
                ),
                'helperText' => $this->module->l('Leave blank to support all customer groups'),
                'default'    => '',
                'order'      => 80,
            ],
            'MULTISAFEPAY_CARRIERS_'.$this->getUniqueName()        => [
                'type'       => 'multi-select',
                'name'       => $this->module->l('Select carriers'),
                'value'      => $this->settingToArray(
                    Configuration::get('MULTISAFEPAY_CARRIERS_'.$this->getUniqueName())
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
                'helperText' => $this->module->l('Leave blank to support all carriers'),
                'default'    => '',
                'order'      => 81,
            ],
            'MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName()      => [
                'type'    => 'text',
                'name'    => $this->module->l('Sort order'),
                'value'   => Configuration::get('MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName()),
                'default' => '',
                'order'   => 90,
            ],
        ];

        if ($this->hasConfigurableDirect) {
            $settings['MULTISAFEPAY_DIRECT_'.$this->getUniqueName()] = [
                'type'       => 'switch',
                'name'       => $this->module->l('Enable direct'),
                'value'      => Configuration::get('MULTISAFEPAY_DIRECT_'.$this->getUniqueName()),
                'helperText' => $this->module->l(
                    'If enabled, additional information can be entered during checkout. If disabled, additional information will be requested on the MultiSafepay payment page.'
                ),
                'default'    => '1',
                'order'      => 11,
            ];
        }

        if ($this->hasConfigurableTokenization) {
            $settings['MULTISAFEPAY_TOKENIZATION_'.$this->getUniqueName()] = [
                'type'       => 'switch',
                'name'       => $this->module->l('Enable tokenization'),
                'value'      => Configuration::get('MULTISAFEPAY_TOKENIZATION_'.$this->getUniqueName()) ?? 0,
                'helperText' => $this->module->l(
                    'If enabled, payment details entered during checkout can be saved by the customer for future purchases.'
                ),
                'default'    => '0',
                'order'      => 12,
            ];
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
            $resultItem['id']   = $item[$idKey];
            $resultItem['name'] = $item['name'];
            $result[]           = $resultItem;
        }

        return $result;
    }

    public function isActive(): bool
    {
        return Configuration::get('MULTISAFEPAY_GATEWAY_'.$this->getUniqueName()) === '1';
    }

    /**
     * @param Order $order
     * @param array $data
     *
     * @return GatewayInfoInterface|null
     */
    public function getGatewayInfo(Order $order, array $data = []): ?GatewayInfoInterface
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isDirect(): bool
    {
        if ($this->hasConfigurableDirect) {
            return (bool)Configuration::get('MULTISAFEPAY_DIRECT_'.$this->getUniqueName());
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
            return (bool)Configuration::get('MULTISAFEPAY_TOKENIZATION_'.$this->getUniqueName());
        }

        return false;
    }


    /**
     * @param Context $context
     *
     * @return void
     */
    public function registerJavascript(Context $context): void
    {
    }
}
