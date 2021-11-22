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

namespace MultiSafepay\PrestaShop\Builder;

use Country;
use Currency;
use HelperForm;
use MultisafepayOfficial;
use Configuration;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOptionInterface;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use Tools;
use Context;
use OrderState;
use Group;
use Uploader;
use ImageManager;

/**
 * Class SettingsBuilder
 * @package MultiSafepay\Prestashop\Builder
 */
class SettingsBuilder
{
    public const SECONDS = 'seconds';
    public const HOURS = 'hours';
    public const DAYS = 'days';
    public const MULTISAFEPAY_RELEASES_GITHUB_URL = 'https://github.com/MultiSafepay/prestashop-official/releases';

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
            'MULTISAFEPAY_OFFICIAL_TEST_MODE'                => ['default' => '0'],
            'MULTISAFEPAY_OFFICIAL_API_KEY'                  => ['default' => ''],
            'MULTISAFEPAY_OFFICIAL_TEST_API_KEY'             => ['default' => ''],
            'MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE'        => ['default' => '30'],
            'MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT'         => ['default' => self::DAYS],
            'MULTISAFEPAY_OFFICIAL_GOOGLE_ANALYTICS_ID'      => ['default' => ''],
            'MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION'        => ['default' => 'Payment for order: {order_reference}'],
            'MULTISAFEPAY_OFFICIAL_OS_TRIGGER_SHIPPED'       => ['default' => Configuration::get('PS_OS_SHIPPING')],
            'MULTISAFEPAY_OFFICIAL_DEBUG_MODE'               => ['default' => '0'],
            'MULTISAFEPAY_OFFICIAL_SECOND_CHANCE'            => ['default' => '1'],
            'MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL' => ['default' => '1'],
            'MULTISAFEPAY_OFFICIAL_OS_INITIALIZED'           => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED')],
            'MULTISAFEPAY_OFFICIAL_OS_COMPLETED'             => ['default' => Configuration::get('PS_OS_PAYMENT')],
            'MULTISAFEPAY_OFFICIAL_OS_UNCLEARED'             => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_UNCLEARED')],
            'MULTISAFEPAY_OFFICIAL_OS_RESERVED'              => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_INITIALIZED')],
            'MULTISAFEPAY_OFFICIAL_OS_CHARGEBACK'            => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_CHARGEBACK')],
            'MULTISAFEPAY_OFFICIAL_OS_REFUNDED'              => ['default' => Configuration::get('PS_OS_REFUND')],
            'MULTISAFEPAY_OFFICIAL_OS_SHIPPED'               => ['default' => Configuration::get('PS_OS_SHIPPING')],
            'MULTISAFEPAY_OFFICIAL_OS_PARTIAL_REFUNDED'      => ['default' => Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_REFUNDED')],
        ];
    }

    /**
     * @param bool $success
     * @return string
     * @throws \PrestaShopException
     */
    public function renderForm(bool $success = false)
    {
        $helper = new HelperForm();

        $helper->module                = $this->module;
        $context                       = Context::getContext();
        $helper->default_form_language = $context->language->id;

        $helper->submit_action = 'submitMultisafepayOfficialModule';
        $helper->currentIndex  = $context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->module->name.'&tab_module='.$this->module->tab.'&module_name='.$this->module->name;
        $helper->token         = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages'    => $context->controller->getLanguages(),
            'id_language'  => $context->language->id
        ];

        $configForm = $this->getConfigForm();

        if ($success) {
            $configForm[0]['form'] = ['success' => $this->module->l('Settings updated')] + $configForm[0]['form'];
        }

        if ($this->isThereAnUpdateAvailable()) {
            $configForm[0]['form'] = [
                    'description' => $this->module->l(
                        'There is a new version for MultiSafepay payment module. '
                    ) . '<a href="' . self::MULTISAFEPAY_RELEASES_GITHUB_URL . '" target="_blank">Click here, to read more information</a>'
                ] + $configForm[0]['form'];
        }

        return $helper->generateForm($configForm);
    }

    /**
     * Return an array with the structure of the settings page form.
     *
     * @return array
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    protected function getConfigForm(): array
    {
        $form           = [
            'form' => [
                'tabs'   => [
                    'account_settings' => $this->module->l('Account settings'),
                    'general_settings' => $this->module->l('General settings'),
                    'payment_methods'  => $this->module->l('Payment methods'),
                    'order_status'     => $this->module->l('Order Statuses'),
                    'support'          => $this->module->l('Support'),
                ],
                'input'  => [
                    [
                        'type'    => 'switch',
                        'tab'     => 'account_settings',
                        'label'   => $this->module->l('Test mode'),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_TEST_MODE',
                        'is_bool' => true,
                        'desc'    => $this->module->l('Use this module in test mode'),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled'),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'        => 'text',
                        'tab'         => 'account_settings',
                        'desc'        => $this->module->l('Enter a valid live API key'),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_API_KEY',
                        'label'       => $this->module->l('Live API key'),
                        'placeholder' => $this->module->l('Live API key'),
                        'section'     => 'default'
                    ],
                    [
                        'type'        => 'text',
                        'tab'         => 'account_settings',
                        'desc'        => $this->module->l('Enter a valid test API key'),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_TEST_API_KEY',
                        'label'       => $this->module->l('Test API key'),
                        'placeholder' => $this->module->l('Test API key'),
                        'section'     => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Debug mode'),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_DEBUG_MODE',
                        'is_bool' => true,
                        'desc'    => $this->module->l('Use this module in debug mode'),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled'),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Second Chance'),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_SECOND_CHANCE',
                        'is_bool' => true,
                        'desc'    => $this->module->l('When a customer initiates but does not complete a payment, whatever the reason may be, MultiSafepay will send two Second Chance reminder emails. In the emails, MultiSafepay will include a link to allow the consumer to finalize the payment. The first Second Chance email is sent 1 hour after the transaction was initiated and the second after 24 hours. To receive second chance emails, this option must also be activated within your MultiSafepay account, otherwise it will not work.'),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled'),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'type'    => 'switch',
                        'tab'     => 'general_settings',
                        'label'   => $this->module->l('Send confirmation order email'),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL',
                        'is_bool' => true,
                        'desc'    => $this->module->l('Send an email to the customer with the order details when a customer initiates an order, but has not yet completed the payment.'),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled'),
                            ],
                        ],
                        'section' => 'default'
                    ],
                    [
                        'tab'         => 'general_settings',
                        'type'        => 'text',
                        'desc'        => $this->module->l('Enter a valid Google Analytics ID'),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_GOOGLE_ANALYTICS_ID',
                        'label'       => $this->module->l('Google Analytics ID'),
                        'placeholder' => $this->module->l('Google Analytics ID'),
                        'section'     => 'default'
                    ],
                    [
                        'tab'         => 'general_settings',
                        'type'        => 'text',
                        'desc'        => $this->module->l('A text which will be shown with the order in MultiSafepay Control. If the customer’s bank supports it this description will also be shown on the customer’s bank statement. You can include the order number using {order_reference}'),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION',
                        'label'       => $this->module->l('Order description'),
                        'placeholder' => $this->module->l('Payment for order: {order_reference}'),
                        'section'     => 'default'
                    ],
                    [
                        'tab'   => 'general_settings',
                        'type'  => 'select',
                        'desc'  => $this->module->l('When the order reaches this status, a notification will be sent to MultiSafepay to set the transaction as shipped'),
                        'name'  => 'MULTISAFEPAY_OFFICIAL_OS_TRIGGER_SHIPPED',
                        'label' => $this->module->l('Set transaction as shipped'),
                        'options' => $this->getPrestaShopOrderStatusesOptions(),
                        'section' => 'default'
                    ],
                    [
                        'tab'         => 'general_settings',
                        'type'        => 'text',
                        'desc'        => $this->module->l('Lifetime of payment link value'),
                        'name'        => 'MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE',
                        'label'       => $this->module->l('Lifetime of payment link value'),
                        'placeholder' => $this->module->l('Lifetime of payment link'),
                        'section'     => 'default'
                    ],
                    [
                        'tab'     => 'general_settings',
                        'type'    => 'select',
                        'desc'    => $this->module->l('Lifetime of payment link unit'),
                        'name'    => 'MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT',
                        'label'   => $this->module->l('Lifetime of payment link unit'),
                        'options' => [
                            'query' => [
                                [
                                    'id'   => self::SECONDS,
                                    'name' => $this->module->l('Seconds'),
                                ],
                                [
                                    'id'   => self::HOURS,
                                    'name' => $this->module->l('Hours'),
                                ],
                                [
                                    'id'   => self::DAYS,
                                    'name' => $this->module->l('Days'),
                                ],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'section' => 'default'
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
                        'name'                => 'Support',
                        'tab'                 => 'support',
                        'type'                => 'html',
                        'html_content'        => $this->getSupportHtmlContent(),
                        'section'             => 'multisafepay-support',
                        'form_group_class'    => 'form-group-multisafepay-support',
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save'),
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
     * @throws \SmartyException
     */
    public function getPaymentMethodsHtmlContent(): string
    {
        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->module->get('multisafepay.payment_option_service');
        $groups = Group::getGroups((int)Context::getContext()->language->id);
        Context::getContext()->smarty->assign(
            [
                'payment_options' => $paymentOptionService->getMultiSafepayPaymentOptions(),
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
     * Return the view of the Support tab of the settings page
     *
     * @return string
     * @throws \SmartyException
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
     * @return void
     */
    public function postProcess(): void
    {
        $formValues = $this->filterToProcessConfigForm($this->getConfigFormValues());

        foreach ($formValues as $key => $value) {
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

        if (!empty($_FILES)) {
            $this->processFiles();
        }
    }

    /**
     * @param array $fields
     * @return array
     */
    private function filterToProcessConfigForm(array $fields): array
    {
        unset($fields['MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_GENERIC1']);
        unset($fields['MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_GENERIC2']);
        unset($fields['MULTISAFEPAY_OFFICIAL_GATEWAY_IMAGE_GENERIC3']);
        return $fields;
    }

    /**
     * Process $_FILES from generic gateway images
     */
    private function processFiles(): void
    {
        foreach ($_FILES as $key => $file) {
            // User are submitting a file. New one or updating
            if (!empty($file['tmp_name']) && !(bool)$file['error'] &&
                ImageManager::isCorrectImageFileExt($file['name']) &&
                is_uploaded_file($file['tmp_name']) &&
                ImageManager::isRealImage($file['tmp_name'], $file['type'])
            ) {
                $uploader = new Uploader($key);
                $result = $uploader->process();
                if (!(bool)$result[0]['error']) {
                    Configuration::updateValue($key, $result[0]['save_path']);
                }
            }

            // User are not submitting a file, but he want to remove the current one assigned.
            // In these cases $_POST variable contains remove as value and $_FILE is empty.
            if (empty($file['tmp_name']) && (bool)$file['error'] && Tools::getValue($key) === 'remove') {
                Configuration::updateValue($key, null);
            }
        }
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
                'name' => $prestaShopOrderStatus['name']
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
                'desc'        => 'Select the order status for which an order should change if MultiSafepay notification reports the order as ' . $orderStatus,
                'label'       => $this->module->l(Tools::ucfirst(str_replace('_', ' ', $orderStatus))),
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
     * @return array
     */
    public function getConfigFormValues(): array
    {
        $configFormValues = [];
        foreach (array_keys(self::getConfigFieldsAndDefaultValues()) as $configKey) {
            $configFormValues[$configKey] = Configuration::get($configKey);
        }

        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->module->get('multisafepay.payment_option_service');
        /** @var BasePaymentOptionInterface $paymentOption */
        foreach ($paymentOptionService->getMultiSafepayPaymentOptions() as $paymentOption) {
            foreach ($paymentOption->getGatewaySettings() as $settingKey => $settings) {
                $configFormValues[$settingKey] = $settings['value'] ?? '';
            }
        }

        return $configFormValues;
    }

    /**
     * Compare the version with
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
            $information = json_decode($content);
            if (version_compare($information->tag_name, $this->module->version, '>')) {
                return true;
            }
        }
        return false;
    }
}
