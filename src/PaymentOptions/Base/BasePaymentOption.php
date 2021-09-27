<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\PaymentOptions\Base;

use Carrier;
use Configuration;
use Context;
use Country;
use Currency;
use Group;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfoInterface;
use Multisafepay;
use Order;
use PaymentModule;

abstract class BasePaymentOption implements BasePaymentOptionInterface
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $gatewayCode;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $inputs;

    /**
     * @var string
     */
    public $callToActionText;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var boolean
     */
    public $paymentForm;

    /**
     * @var string
     */
    public $action;

    /**
     * @var Multisafepay
     */
    public $module;

    /**
     * @var int
     */
    public $sortOrderPosition;

    /**
     * @var bool
     */
    public $hasConfigurableDirect = false;

    public function __construct(Multisafepay $module)
    {
        $this->module           = $module;
        $this->name             = $this->getPaymentOptionName();
        $this->description      = $this->getPaymentOptionDescription();
        $this->gatewayCode      = $this->getPaymentOptionGatewayCode();
        $this->type             = $this->getTransactionType();
        $this->inputs           = $this->getInputFields();
        $this->callToActionText = $this->getFrontEndPaymentOptionName();
        $this->icon             = $this->getPaymentOptionLogo();
        $this->paymentForm      = $this->getPaymentOptionForm();
        $this->action           = Context::getContext()->link->getModuleLink('multisafepay', 'payment', [], true);
        $this->sortOrderPosition = (int) Configuration::get('MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName());
    }

    public function getFrontEndPaymentOptionName(): string
    {
        return (Configuration::get('MULTISAFEPAY_TITLE_'.$this->getUniqueName()) ?: $this->getPaymentOptionName());
    }

    public function getPaymentOptionLogo(): string
    {
        return '';
    }

    public function getTransactionType(): string
    {
        $transactionType = 'redirect';
        if ($this->isDirect()) {
            $transactionType = 'direct';
        }
        return $transactionType;
    }

    public function getPaymentOptionForm(): bool
    {
        return false;
    }

    public function getPaymentOptionDescription(): string
    {
        return (Configuration::get('MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName()) ?: '');
    }

    public function getInputFields(): array
    {
        $inputFields = [
            [
                'type' => 'hidden',
                'name'  => 'gateway',
                'value' => $this->getPaymentOptionGatewayCode(),
            ],
        ];

        if ($this->isDirect()) {
            $inputFields = array_merge($inputFields, $this->getDirectTransactionInputFields());
        }

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
        return $this->getPaymentOptionGatewayCode();
    }

    /**
     * @return array
     */
    public function getGatewaySettings(): array
    {
        $settings = [
            'MULTISAFEPAY_GATEWAY_'.$this->getUniqueName() => [
                'type' => 'switch',
                'name' => $this->name,
                'value' => Configuration::get('MULTISAFEPAY_GATEWAY_'.$this->getUniqueName()),
                'default' => '0',
                'order' => 10,
            ],
            'MULTISAFEPAY_TITLE_'.$this->getUniqueName() => [
                'type' => 'text',
                'name' => $this->module->l('Title'),
                'value' => Configuration::get('MULTISAFEPAY_TITLE_'.$this->getUniqueName()),
                'helperText' => $this->module->l('The title will be shown to the customer at the checkout page.'),
                'default' => '',
                'order' => 20,
            ],
            'MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName() => [
                'type' => 'text',
                'name' => $this->module->l('Description'),
                'value' => Configuration::get('MULTISAFEPAY_DESCRIPTION_'.$this->getUniqueName()),
                'helperText' => $this->module->l('The description will be shown to the customer at the checkout page.'),
                'default' => '',
                'order' => 30,
            ],
            'MULTISAFEPAY_MIN_AMOUNT_'.$this->getUniqueName() => [
                'type' => 'text',
                'name' => $this->module->l('Minimum amount'),
                'value' => Configuration::get('MULTISAFEPAY_MIN_AMOUNT_'.$this->getUniqueName()),
                'default' => '',
                'order' => 40,
            ],
            'MULTISAFEPAY_MAX_AMOUNT_'.$this->getUniqueName() => [
                'type' => 'text',
                'name' => $this->module->l('Maximum amount'),
                'value' => Configuration::get('MULTISAFEPAY_MAX_AMOUNT_'.$this->getUniqueName()),
                'default' => '',
                'order' => 50,
            ],
            'MULTISAFEPAY_COUNTRIES_'.$this->getUniqueName() => [
                'type' => 'multi-select',
                'name' => $this->module->l('Select countries'),
                'value' => $this->settingToArray(Configuration::get('MULTISAFEPAY_COUNTRIES_'.$this->getUniqueName())),
                'options' => $this->getCountriesForSettings(),
                'helperText' => $this->module->l('Leave blank to support all countries'),
                'default' => '',
                'order' => 60,
            ],
            'MULTISAFEPAY_CURRENCIES_'.$this->getUniqueName() => [
                'type' => 'multi-select',
                'name' => $this->module->l('Select currencies'),
                'value' => $this->settingToArray(Configuration::get('MULTISAFEPAY_CURRENCIES_'.$this->getUniqueName())),
                'options' => Currency::getCurrencies(false, true, true),
                'helperText' => $this->module->l('Leave blank to support all currencies'),
                'default' => '',
                'order' => 70,
            ],
            'MULTISAFEPAY_CUSTOMER_GROUPS_'.$this->getUniqueName() => [
                'type' => 'multi-select',
                'name' => $this->module->l('Select customer groups'),
                'value' => $this->settingToArray(Configuration::get('MULTISAFEPAY_CUSTOMER_GROUPS_'.$this->getUniqueName())),
                'options' => $this->getGroupsForSettings(),
                'helperText' => $this->module->l('Leave blank to support all customer groups'),
                'default' => '',
                'order' => 80,
            ],
            'MULTISAFEPAY_CARRIERS_'.$this->getUniqueName() => [
                'type' => 'multi-select',
                'name' => $this->module->l('Select carriers'),
                'value' => $this->settingToArray(Configuration::get('MULTISAFEPAY_CARRIERS_'.$this->getUniqueName())),
                'options' => $this->getCarrierForSettings(),
                'helperText' => $this->module->l('Leave blank to support all carriers'),
                'default' => '',
                'order' => 80,
            ],
            'MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName() => [
                'type' => 'text',
                'name' => $this->module->l('Sort order'),
                'value' => Configuration::get('MULTISAFEPAY_SORT_ORDER_'.$this->getUniqueName()),
                'default' => '',
                'order' => 90,
            ],
        ];

        if (true === $this->hasConfigurableDirect) {
            $settings['MULTISAFEPAY_DIRECT_'.$this->getUniqueName()] = [
                'type' => 'switch',
                'name' => $this->module->l('Enable direct'),
                'value' => Configuration::get('MULTISAFEPAY_DIRECT_'.$this->getUniqueName()),
                'helperText' => $this->module->l('If enabled, additional information can be entered during checkout. If disabled, additional information will be requested on the MultiSafepay payment page.'),
                'default' => '1',
                'order' => 11,
            ];
        }
        uasort($settings, function ($a, $b) {
            return $a['order'] - $b['order'];
        });
        return $settings;
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
     * @return array
     */
    protected function getCountriesForSettings(): array
    {
        $returnArray = [];
        $countries = Country::getCountries((int)Context::getContext()->language->id, true);
        if (empty($countries)) {
            return [];
        }

        foreach ($countries as $country) {
            $returnArray[] = [
                'id' => $country['id_country'],
                'name' => $country['name']
            ];
        }
        return $returnArray;
    }

    /**
     * @return array
     */
    protected function getCarrierForSettings(): array
    {
        $returnArray = [];
        $carriers = Carrier::getCarriers(Context::getContext()->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
        if (empty($carriers)) {
            return [];
        }

        foreach ($carriers as $carrier) {
            $returnArray[] = [
                'id'   => $carrier['id_carrier'],
                'name' => $carrier['name'],
            ];
        }
        return $returnArray;
    }

    /**
     * @return array
     */
    protected function getGroupsForSettings(): array
    {
        $returnArray = [];
        $groups = Group::getGroups((int)Context::getContext()->language->id);
        if (empty($groups)) {
            return [];
        }

        foreach ($groups as $group) {
            $returnArray[] = [
                'id' => $group['id_group'],
                'name' => $group['name']
            ];
        }
        return $returnArray;
    }

    public function isActive(): bool
    {
        return Configuration::get('MULTISAFEPAY_GATEWAY_'.$this->getUniqueName()) === '1';
    }

    /**
     * @param Order $order
     * @param array $data
     *
     * @return GatewayInfoInterface
     */
    public function getGatewayInfo(Order $order, array $data = []): GatewayInfoInterface
    {
        return new BaseGatewayInfo();
    }

    /**
     * @return bool
     */
    public function isDirect(): bool
    {
        $isDirect = false;
        if (true === $this->hasConfigurableDirect) {
            $isDirect = (bool)Configuration::get('MULTISAFEPAY_DIRECT_'.$this->getUniqueName());
        }

        return $isDirect;
    }

    /**
     * @return bool
     */
    public function canProcessRefunds(): bool
    {
        return true;
    }
}
