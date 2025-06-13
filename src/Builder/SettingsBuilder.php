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

namespace MultiSafepay\PrestaShop\Builder;

use Country;
use Currency;
use Exception;
use HelperForm;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\SystemStatusService;
use MultisafepayOfficial;
use Configuration;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use SmartyException;
use Tab;
use Tools;
use Context;
use OrderState;
use Group;

/**
 * Class SettingsBuilder
 * @package MultiSafepay\PrestaShop\Builder
 */
class SettingsBuilder
{
    public const SECONDS = 'seconds';
    public const HOURS = 'hours';
    public const DAYS = 'days';
    public const MULTISAFEPAY_RELEASES_GITHUB_URL = 'https://github.com/MultiSafepay/prestashop-official/releases';
    public const CLASS_NAME = 'SettingsBuilder';

    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * SettingsBuilder constructor.
     * @param MultisafepayOfficial $module
     */
    public function __construct(MultisafepayOfficial $module)
    {
        $this->module  = $module;
    }

    /**
     * Return an array of config fields names and default values
     *
     * @return array
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public static function getConfigFieldsAndDefaultValues(): array
    {
        return [
            'MULTISAFEPAY_OFFICIAL_TEST_MODE'                   => ['default' => '0'],
            'MULTISAFEPAY_OFFICIAL_API_KEY'                     => ['default' => ''],
            'MULTISAFEPAY_OFFICIAL_TEST_API_KEY'                => ['default' => ''],
            'MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE'           => ['default' => '30'],
            'MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT'            => ['default' => self::DAYS],
            'MULTISAFEPAY_OFFICIAL_TEMPLATE_ID_VALUE'           => ['default' => ''],
            'MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION'           => ['default' => 'Payment for order: {order_reference}'],
            'MULTISAFEPAY_OFFICIAL_OS_TRIGGER_SHIPPED'          => ['default' => Configuration::get('PS_OS_SHIPPING')],
            'MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS'          => ['default' => '["'.Configuration::get('PS_OS_REFUND').'"]', 'multiple' => true],
            'MULTISAFEPAY_OFFICIAL_DEBUG_MODE'                  => ['default' => '0'],
            'MULTISAFEPAY_OFFICIAL_SECOND_CHANCE'               => ['default' => '1'],
            'MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL'    => ['default' => '1'],
            'MULTISAFEPAY_OFFICIAL_OS_INITIALIZED'              => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED')],
            'MULTISAFEPAY_OFFICIAL_OS_COMPLETED'                => ['default' => Configuration::get('PS_OS_PAYMENT')],
            'MULTISAFEPAY_OFFICIAL_OS_UNCLEARED'                => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_UNCLEARED')],
            'MULTISAFEPAY_OFFICIAL_OS_RESERVED'                 => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED')],
            'MULTISAFEPAY_OFFICIAL_OS_CHARGEBACK'               => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_CHARGEBACK')],
            'MULTISAFEPAY_OFFICIAL_OS_REFUNDED'                 => ['default' => Configuration::get('PS_OS_REFUND')],
            'MULTISAFEPAY_OFFICIAL_OS_SHIPPED'                  => ['default' => Configuration::get('PS_OS_SHIPPING')],
            'MULTISAFEPAY_OFFICIAL_OS_PARTIAL_REFUNDED'         => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_REFUNDED')],
            'MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS'           => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS')],
            'MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT' => ['default' => '1'],
            'MULTISAFEPAY_OFFICIAL_DISABLE_SHOPPING_CART'       => ['default' => '0'],
        ];
    }

    /**
     * Return an array with the values of the settings form
     *
     * @param string $className
     *
     * @return int
     * @throws Exception
     */
    public function getAdminTab(string $className = ''): int
    {
        $adminTab = null;

        if (class_exists('PrestaShopBundle\Entity\Repository\TabRepository') &&
            !empty($this->module->get('prestashop.core.admin.tab.repository'))
        ) {
            $tabRepository = $this->module->get('prestashop.core.admin.tab.repository');
            if (method_exists($tabRepository, 'findOneIdByClassName')) {
                $adminTab = $tabRepository->findOneIdByClassName($className);
            }
        }

        // Fallback if the new method failed or is not available
        if (empty($adminTab)) {
            $adminTab = Tab::getIdFromClassName($className) ?: 0;
        }

        return (int)$adminTab;
    }

    /**
     * @param array $result
     *
     * @return string
     * @throws SmartyException
     * @throws Exception
     */
    public function renderForm(array $result = []): string
    {
        $helper = new HelperForm();

        $helper->module                = $this->module;
        $context                       = Context::getContext();
        $helper->default_form_language = $context->language->id;

        $helper->submit_action = 'submitMultisafepayOfficialModule';
        $helper->currentIndex  = $context->link->getAdminLink('AdminModules', false)
            .'&configure=' . $this->module->name . '&tab_module=' . $this->module->tab . '&module_name=' . $this->module->name;
        $helper->token         = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages'    => $context->controller->getLanguages(),
            'id_language'  => $context->language->id
        ];

        $configForm = $this->getConfigForm();

        if (isset($result['success'])) {
            $configForm[0]['form'] = ['success' => $this->module->l('Settings updated', self::CLASS_NAME)] + $configForm[0]['form'];
        }

        if (isset($result['error'])) {
            $configForm[0]['form'] = ['error' => $result['error']] + $configForm[0]['form'];
        }

        if ($this->isThereAnUpdateAvailable()) {
            $configForm[0]['form'] = [
                    'description' => $this->module->l(
                        'There is a new version for MultiSafepay payment module. ',
                        self::CLASS_NAME
                    ) . '<a href="' . self::MULTISAFEPAY_RELEASES_GITHUB_URL . '" target="_blank">Click here to read more information</a>'
                ] + $configForm[0]['form'];
        }

        return $helper->generateForm($configForm);
    }

    /**
     * Return an array with the structure of the settings page form.
     *
     * @return array
     * @throws SmartyException
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    protected function getConfigForm(): array
    {
        $form           = [
            'form' => [
                'tabs'   => [
                    'account_settings' => $this->module->l('Account settings', self::CLASS_NAME),
                    'general_settings' => $this->module->l('General settings', self::CLASS_NAME),
                    'payment_methods'  => $this->module->l('Payment methods', self::CLASS_NAME),
                    'order_status'     => $this->module->l('Order Statuses', self::CLASS_NAME),
                    'system_status'     => $this->module->l('System Status', self::CLASS_NAME),
                    'support'          => $this->module->l('Support', self::CLASS_NAME),
                ],
                'input'  => [
                    [
                        'type'    => 'switch',
                        'tab'     => 'account_settings',
                        'label'   => $this->module->l('Test mode', self::CLASS_NAME),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_TEST_MODE',
                        'is_bool' => true,
                        'desc'    => $this->module->l('Use this module in test mode', self::CLASS_NAME),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled', self::CLASS_NAME),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled', self::CLASS_NAME),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'        => 'text',
                        'tab'         => 'account_settings',
                        'desc'        => $this->module->l('Enter a valid live API key', self::CLASS_NAME),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_API_KEY',
                        'label'       => $this->module->l('Live API key', self::CLASS_NAME),
                        'placeholder' => $this->module->l('Live API key', self::CLASS_NAME),
                        'section'     => 'default'
                    ],
                    [
                        'type'        => 'text',
                        'tab'         => 'account_settings',
                        'desc'        => $this->module->l('Enter a valid test API key', self::CLASS_NAME),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_TEST_API_KEY',
                        'label'       => $this->module->l('Test API key', self::CLASS_NAME),
                        'placeholder' => $this->module->l('Test API key', self::CLASS_NAME),
                        'section'     => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Debug mode', self::CLASS_NAME),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_DEBUG_MODE',
                        'is_bool' => true,
                        'desc'    => $this->module->l('Use this module in debug mode', self::CLASS_NAME),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled', self::CLASS_NAME),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled', self::CLASS_NAME),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Second Chance', self::CLASS_NAME),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_SECOND_CHANCE',
                        'is_bool' => true,
                        'desc'    => $this->module->l('When a customer initiates but does not complete a payment, whatever the reason may be, MultiSafepay will send two Second Chance reminder emails. In the emails, MultiSafepay will include a link to allow the consumer to finalize the payment. The first Second Chance email is sent 1 hour after the transaction was initiated and the second after 24 hours. To receive second chance emails, this option must also be activated within your MultiSafepay account, otherwise it will not work.', self::CLASS_NAME),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled', self::CLASS_NAME),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled', self::CLASS_NAME),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Group debit and credit cards', self::CLASS_NAME),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_GROUP_CREDITCARDS',
                        'is_bool' => true,
                        'desc'    => $this->module->l('If turned on, payment methods classified as credit and debit cards (Amex, Maestro, Mastercard, and Visa) will shown grouped as a single payment method', self::CLASS_NAME),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled', self::CLASS_NAME),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled', self::CLASS_NAME),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Create order before payment', self::CLASS_NAME),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT',
                        'is_bool' => true,
                        'desc'    => $this->module->l('If turned on an order in the PrestaShop backend will be created once the customer has initiated payment, but not yet actually paid. If this is off the order will be created after the payment has been made', self::CLASS_NAME),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled', self::CLASS_NAME),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled', self::CLASS_NAME),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Send confirmation order email', self::CLASS_NAME),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL',
                        'is_bool' => true,
                        'desc'    => $this->module->l('If turned off, it will disable the confirmation order email. It can be desirable when the order in PrestaShop is being created before the payment is completed.', self::CLASS_NAME),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled', self::CLASS_NAME),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled', self::CLASS_NAME),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Disable Shopping Cart', self::CLASS_NAME),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_DISABLE_SHOPPING_CART',
                        'is_bool' => true,
                        'desc'    => $this->module->l('Enable this option to hide the cart items on the MultiSafepay payment page, leaving only the total order amount. Note: If is enabled, the payment methods which require shopping cart won\'t work: Afterpay, E-Invoicing, iDEAL+in3, Klarna and Pay After Delivery.', self::CLASS_NAME),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled', self::CLASS_NAME),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled', self::CLASS_NAME),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'tab'         => 'general_settings',
                        'type'        => 'text',
                        'desc'        => $this->module->l('A text which will be shown with the order in MultiSafepay Control. If the customer’s bank supports it this description will also be shown on the customer’s bank statement. You can include the order number using {order_reference}', self::CLASS_NAME),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION',
                        'label'       => $this->module->l('Order description', self::CLASS_NAME),
                        'placeholder' => $this->module->l('Payment for order: {order_reference}', self::CLASS_NAME),
                        'section'     => 'default'
                    ],
                    [
                        'tab'   => 'general_settings',
                        'type'  => 'select',
                        'desc'  => $this->module->l('When the order reaches this status, a notification will be sent to MultiSafepay to set the transaction as shipped', self::CLASS_NAME),
                        'name'  => 'MULTISAFEPAY_OFFICIAL_OS_TRIGGER_SHIPPED',
                        'label' => $this->module->l('Set transaction as shipped', self::CLASS_NAME),
                        'options' => $this->getPrestaShopOrderStatusesOptions(),
                        'section' => 'default'
                    ],
                    [
                        'tab'   => 'general_settings',
                        'type'  => 'select',
                        'multiple' => true,
                        'class' => 'chosen',
                        'desc'  => $this->module->l('When the order reaches one of these statuses, the notification callback will not alter the status of this order. Can also be left empty', self::CLASS_NAME),
                        'name'  => 'MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS',
                        'label' => $this->module->l('Final order status', self::CLASS_NAME),
                        'options' => $this->getPrestaShopOrderStatusesOptions(),
                        'section' => 'default'
                    ],
                    [
                        'tab'         => 'general_settings',
                        'type'        => 'text',
                        'desc'        => $this->module->l('Lifetime of payment link value', self::CLASS_NAME),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE',
                        'label'       => $this->module->l('Lifetime of payment link value', self::CLASS_NAME),
                        'placeholder' => $this->module->l('Lifetime of payment link', self::CLASS_NAME),
                        'section'     => 'default'
                    ],
                    [
                        'tab'     => 'general_settings',
                        'type'    => 'select',
                        'desc'    => $this->module->l('Lifetime of payment link unit', self::CLASS_NAME),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT',
                        'label'   => $this->module->l('Lifetime of payment link unit', self::CLASS_NAME),
                        'options' => [
                            'query' => [
                                [
                                    'id'   => self::SECONDS,
                                    'name' => $this->module->l('Seconds', self::CLASS_NAME),
                                ],
                                [
                                    'id'   => self::HOURS,
                                    'name' => $this->module->l('Hours', self::CLASS_NAME),
                                ],
                                [
                                    'id'   => self::DAYS,
                                    'name' => $this->module->l('Days', self::CLASS_NAME),
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'section' => 'default'
                    ],
                    [
                        'tab'         => 'general_settings',
                        'type'        => 'text',
                        'desc'        => $this->module->l('If empty, the default one will be used', self::CLASS_NAME),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_TEMPLATE_ID_VALUE',
                        'label'       => $this->module->l('Payment Component Template ID', self::CLASS_NAME),
                        'placeholder' => $this->module->l('Payment Component Template ID', self::CLASS_NAME),
                        'section'     => 'default'
                    ],
                    [
                        'col'              => '12',
                        'label'            => '',
                        'name'             => 'Payment methods',
                        'tab'              => 'payment_methods',
                        'type'             => 'html',
                        'html_content'     => $this->getPaymentMethodsHtmlContent(),
                        'section'          => 'multisafepay-payment-methods',
                        'form_group_class' => 'form-group-multisafepay-payment-methods',
                    ],
                    [
                        'col'                 => '12',
                        'label'               => '',
                        'name'                => 'System Status',
                        'tab'                 => 'system_status',
                        'type'                => 'html',
                        'html_content'        => $this->getSystemStatusHtmlContent(),
                        'section'             => 'multisafepay-system-status',
                        'form_group_class'    => 'form-group-multisafepay-system-status',
                    ],
                    [
                        'col'                 => '12',
                        'label'               => '',
                        'name'                => 'Support',
                        'tab'                 => 'support',
                        'type'                => 'html',
                        'html_content'        => $this->getSupportHtmlContent(),
                        'section'             => 'multisafepay-support',
                        'form_group_class'    => 'form-group-multisafepay-support',
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save', self::CLASS_NAME),
                ],
            ],
        ];

        $form = array_merge_recursive($form, $this->getOrderStatusesSettingFields());
        return [$form];
    }

    /**
     * Return the view of the PaymentMethods tab of the settings page
     *
     * @return string
     * @throws SmartyException
     * @throws Exception
     */
    public function getPaymentMethodsHtmlContent(): string
    {
        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->module->get('multisafepay.payment_option_service');
        $groups = Group::getGroups((int)Context::getContext()->language->id);
        Context::getContext()->smarty->assign(
            [
                'payment_options' => $paymentOptionService->getMultiSafepayPaymentOptions(),
                'no_payments'     => $this->module->l('Please enter your API key to view all supported payment methods.', self::CLASS_NAME),
                'languages'       => Context::getContext()->controller->getLanguages(),
                'id_language'     => Context::getContext()->language->id,
                'countries'       => Country::getCountries((int)Context::getContext()->language->id, true),
                'currencies'      => Currency::getCurrencies(false, true, true),
                'customer_groups' => $groups
            ]
        );

        return Context::getContext()->smarty->fetch(
            'module:multisafepayofficial/views/templates/admin/settings/payment-methods.tpl'
        );
    }

    /**
     * Return the view of the System Status tab of the settings page
     *
     * @return string
     * @throws SmartyException
     * @throws Exception
     */
    public function getSystemStatusHtmlContent(): string
    {
        /** @var systemStatusService $systemStatusService */
        $systemStatusService = $this->module->get('multisafepay.system_status_service');

        Context::getContext()->smarty->assign(
            [
                'status_report' => $systemStatusService->createSystemStatusReport(),
                'plain_status_report' => $systemStatusService->createPlainSystemStatusReport()
            ]
        );

        return Context::getContext()->smarty->fetch(
            'module:multisafepayofficial/views/templates/admin/settings/system-status.tpl'
        );
    }

    /**
     * Return the view of the Support tab of the settings page
     *
     * @return string
     * @throws SmartyException
     */
    public function getSupportHtmlContent(): string
    {
        return Context::getContext()->smarty->fetch(
            'module:multisafepayofficial/views/templates/admin/settings/support.tpl'
        );
    }

    /**
     * Save form data.
     *
     * @return array
     * @throws Exception
     */
    public function postProcess(): array
    {
        $result = ['success' => true];
        $formValues = $this->getConfigFormValues();
        foreach ($formValues as $key => $value) {
            // Because the PrestaShops form helper adds these brackets to a select field, with the multiple set as true.
            // We have to manually remove them to get the correct value
            $key = trim($key, '[]');
            if (is_array($value)) {
                if (Tools::getValue($key) === false) {
                    Configuration::updateValue($key, '');
                } else {
                    Configuration::updateValue($key, json_encode(Tools::getValue($key)));
                }
            } else {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }

        return $result;
    }

    /**
     * Process country settings ensuring mandatory countries are always preserved
     *
     * @param string $settingKey
     * @return void
     * @throws Exception
     */
    private function processMandatoryCountries(string $settingKey): void
    {
        $userSelectedCountries = Tools::getValue($settingKey);

        if ($userSelectedCountries === false) {
            $userSelectedCountries = [];
        } else {
            $userSelectedCountries = is_array($userSelectedCountries) ? $userSelectedCountries : [$userSelectedCountries];
        }

        // Get mandatory countries from a payment option
        $mandatoryCountries = $this->getMandatoryCountriesForSetting($settingKey);

        // Merge user selection with mandatory countries
        $finalCountries = array_unique(array_merge($mandatoryCountries, $userSelectedCountries));

        Configuration::updateValue($settingKey, json_encode($finalCountries));
    }

    /**
     * Get mandatory countries for a specific setting key
     *
     * @param string $settingKey
     * @return array
     * @throws Exception
     */
    private function getMandatoryCountriesForSetting(string $settingKey): array
    {
        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->module->get('multisafepay.payment_option_service');

        foreach ($paymentOptionService->getMultiSafepayPaymentOptions() as $paymentOption) {
            $specialDefaultValues = $this->getSpecialDefaultValues($paymentOption);
            if (isset($specialDefaultValues[$settingKey])) {
                return json_decode($specialDefaultValues[$settingKey], true) ?? [];
            }
        }

        return [];
    }

    /**
     * @return array
     */
    private function getPrestaShopOrderStatusesOptions(): array
    {
        $prestaShopOrderStatuses = OrderState::getOrderStates(Context::getContext()->language->id);
        $prestaShopOrderStatusesOptions = [];
        foreach ($prestaShopOrderStatuses as $prestaShopOrderStatus) {
            $prestaShopOrderStatusesOptions['query'][] = [
                'id'   => $prestaShopOrderStatus['id_order_state'],
                'name' => $prestaShopOrderStatus['name'],
                'label' => $prestaShopOrderStatus['name'],
            ];
        }
        $prestaShopOrderStatusesOptions['id'] = 'id';
        $prestaShopOrderStatusesOptions['name'] = 'name';
        return $prestaShopOrderStatusesOptions;
    }

    /**
     * Return an array of settings input for OrderStatuses
     * @return array
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    private function getOrderStatusesSettingFields(): array
    {
        $orderStatusesSettingsFields = [];
        $orderStatuses =  $this->getMultiSafepayTransactionStatus();
        foreach ($orderStatuses as $orderStatus) {
            $orderStatusesSettingsFields['form']['input'][] = [
                'tab'         => 'order_status',
                'type'        => 'select',
                'name'        => 'MULTISAFEPAY_OFFICIAL_OS_' . Tools::strtoupper($orderStatus),
                'desc'        => $this->module->l('Select the order status for which an order should change if MultiSafepay notification reports the order as', self::CLASS_NAME) . ' ' . $orderStatus,
                'label'       => $this->module->l(Tools::ucfirst(str_replace('_', ' ', $orderStatus)), self::CLASS_NAME),
                'options'     => $this->getPrestaShopOrderStatusesOptions(),
                'section'     => 'default'
            ];
        }
        return $orderStatusesSettingsFields;
    }

    /**
     * Return the MultiSafepay transaction status with default status
     *
     * @return array
     */
    private function getMultiSafepayTransactionStatus(): array
    {
        return [
            'initialized',
            'completed',
            'uncleared',
            'refunded',
            'partial_refunded',
            'chargeback',
            'shipped',
        ];
    }

    /**
     * Set values for the inputs.
     *
     * @param bool $includePaymentOptionSettings
     *
     * @return array
     * @throws Exception
     */
    public function getConfigFormValues(bool $includePaymentOptionSettings = true): array
    {
        $configFormValues = [];
        foreach (self::getConfigFieldsAndDefaultValues() as $configKey => $configSettings) {
            if (!isset($configSettings['multiple']) || !$configSettings['multiple']) {
                $configFormValues[$configKey] = Configuration::get($configKey);
                continue;
            }
            $configFormValues[$configKey . '[]'] = $this->settingToArray(Configuration::get($configKey));
        }

        if ($includePaymentOptionSettings) {
            /** @var PaymentOptionService $paymentOptionService */
            $paymentOptionService = $this->module->get('multisafepay.payment_option_service');
            /** @var BasePaymentOption $paymentOption */
            foreach ($paymentOptionService->getMultiSafepayPaymentOptions() as $paymentOption) {
                $specialDefaultValues = $this->getSpecialDefaultValues($paymentOption);
                foreach ($paymentOption->getGatewaySettings() as $settingKey => $settings) {
                    // Verify if special default values have been removed or are missing.
                    // These values must be automatically restored if removed, particularly
                    // in the case of countries, where they need to coexist with merchant's
                    // manual selections.
                    if (!empty($specialDefaultValues[$settingKey])) {
                        $currentValue = Configuration::get($settingKey);

                        // Special handling for country settings to ensure mandatory countries are preserved
                        if (strpos($settingKey, 'MULTISAFEPAY_OFFICIAL_COUNTRIES_') === 0) {
                            $this->processMandatoryCountries($settingKey);
                            continue;
                        }

                        // For non-country settings (like min/max amounts), only apply default values
                        // when the configuration is empty to preserve merchant's custom settings
                        if (empty($currentValue)) {
                            Configuration::updateGlobalValue($settingKey, $specialDefaultValues[$settingKey]);
                            $configFormValues[$settingKey] = $specialDefaultValues[$settingKey];
                            continue;
                        }
                    }
                    $configFormValues[$settingKey] = $settings['value'] ?? '';
                }
            }
        }

        return $configFormValues;
    }

    /**
     * Get special default values from the API
     *
     * @param BasePaymentOption $paymentOption
     *
     * @return array
     */
    private function getSpecialDefaultValues(BasePaymentOption $paymentOption): array
    {
        $specialDefaultValues = [];

        // Adding default values max amounts of the payment methods
        $maxAmount = $paymentOption->getMaxAmount();
        if ($maxAmount > 0.0) {
            $specialDefaultValues['MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_' . $paymentOption->getUniqueName()] = (string)$maxAmount;
        }

        // Adding default values max amounts of the payment methods
        $minAmount = $paymentOption->getMinAmount();
        if ($minAmount > 0.0) {
            $specialDefaultValues['MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_' . $paymentOption->getUniqueName()] = (string)$minAmount;
        }

        // Adding default values for countries of the branded payment methods
        $brandedCountries = $paymentOption->getAllowedCountries();
        if (!empty($brandedCountries)) {
            $isoBrandedCountries = [];
            foreach ($brandedCountries as $brandedCountry) {
                $isoBrandedCountries[] = (string)Country::getByIso($brandedCountry);
            }
            $specialDefaultValues['MULTISAFEPAY_OFFICIAL_COUNTRIES_' . $paymentOption->getUniqueName()] = json_encode($isoBrandedCountries);
        }

        return $specialDefaultValues;
    }

    /**
     * Compare the version with
     *
     * @return bool
     */
    private function isThereAnUpdateAvailable(): bool
    {
        $options = [
            'http'=> [
                'method'=>"GET",
                'header'=> "Accept-language: en\r\n" .
                    "User-Agent: PHP\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $content = Tools::file_get_contents('https://api.github.com/repos/multisafepay/prestashop-official/releases/latest', false, $context);
        if ($content) {
            $information = json_decode($content, false);
            if (version_compare($information->tag_name, $this->module->version, '>')) {
                return true;
            }
        }
        return false;
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
}
