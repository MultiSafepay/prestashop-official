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

namespace MultiSafepay\PrestaShop\Services;

use Configuration;
use Exception;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Util\Version;
use MultisafepayOfficial;
use PrestaShop\PrestaShop\Adapter\Module\Module;
use PrestaShop\PrestaShop\Adapter\Requirement\CheckMissingOrUpdatedFiles;
use PrestaShop\PrestaShop\Core\Addon\AddonsCollection;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleRepository;
use Tools;
use OrderState;
use Context;

/**
 * Class SystemStatus
 * @package MultiSafepay\PrestaShop\Services
 */
class SystemStatusService
{

    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * @var array
     */
    protected $systemStatusReport = null;

    /**
     * SystemStatus constructor.
     *
     * @param MultisafepayOfficial $module
     */
    public function __construct(MultisafepayOfficial $module)
    {
        $this->module = $module;
    }

    /**
     * Return a array with the system status report information.
     *
     * @return array
     */
    public function createSystemStatusReport(): array
    {
        if (!$this->systemStatusReport) {
            $this->systemStatusReport = [
                'environment'                          => $this->getPrestaShopSettings(),
                'server_environment'                   => $this->getServerEnvironment(),
                'multisafepay_module_settings'         => $this->getMultiSafepayModuleSettings(),
                'multisafepay_payment_method_settings' => $this->getMultiSafepayPaymentOptionsSettings(),
                'overwritten_files'                    => $this->getOverwrittenMissingOrUpdatedFiles(),
                'order_statuses_definitions'           => $this->getOrderStatusDefinitions(),
                'active_modules'                       => $this->getActiveModules(),
            ];
        }
        return $this->systemStatusReport;
    }

    /**
     * Return an array with the information of PrestaShop settings:
     * Example: Site URL, PrestaShop version, Log location, Is multistore?, Is debug mode?
     *
     * @return array
     * @phpcs:disable Generic.Files.LineLength.TooLong
     * @throws Exception
     */
    public function getPrestaShopSettings(): array
    {
        $generalSettings = [
            'title'    => 'General Settings PrestaShop',
            'settings' => [
                'site_url' => [
                    'label' => 'Site URL',
                    'value' => _PS_BASE_URL_,
                ],
                'prestashop_version' => [
                    'label' => 'PrestaShop version',
                    'value' => _PS_VERSION_,
                ],
                'log_location' => [
                    'label' => 'Log Location',
                    'value' => LoggerHelper::getDefaultLogPath(),
                ],
                'multistore' => [
                    'label' => 'Multistore',
                    'value' => $this->isMultiStoreActive() ? 'Enabled' : 'Disabled',
                ],
                'debug_mode' => [
                    'label' => 'Debug Mode',
                    'value' => _PS_MODE_DEV_ ? 'Enabled' : 'Disabled',
                ],
                'ssl' => [
                    'label' => 'SSL',
                    'value' => Configuration::get('PS_SSL_ENABLED') ? 'Enabled' : 'Disabled',
                ],
                'price_round_mode' => [
                    'label' => 'Round Mode',
                    'value' => $this->getPriceRoundModeValue((int)Configuration::get('PS_PRICE_ROUND_MODE')),
                ],
                'price_round_type' => [
                    'label' => 'Round Type',
                    'value' => $this->getPriceRoundTypeValue((int)Configuration::get('PS_ROUND_TYPE')),
                ],
                'maintenance_mode' => [
                    'label' => 'Maintenance Mode',
                    'value' => Configuration::get('PS_SHOP_ENABLE') ? 'Disabled' : 'Enabled',
                ],
                'allow_ordering_oos' => [
                    'label' => 'Allow ordering of out-of-stock products',
                    'value' => Configuration::get('PS_STOCK_MANAGEMENT') && Configuration::get('PS_ORDER_OUT_OF_STOCK') ? 'Enabled' : 'Disabled',
                ],
                'stock_management' => [
                    'label' => 'Stock Management',
                    'value' => Configuration::get('PS_STOCK_MANAGEMENT') ? 'Enabled' : 'Disabled',
                ],
                'enable_b2b_mode' => [
                    'label' => 'B2B Mode',
                    'value' => Configuration::get('PS_B2B_ENABLE') ? 'Enabled' : 'Disabled',
                ],
                'ask_for_birthday' => [
                    'label' => 'Customer information contains the birthday',
                    'value' => Configuration::get('PS_CUSTOMER_BIRTHDATE') ? 'Enabled' : 'Disabled',
                ],
                'enable_gift_wrapping' => [
                    'label' => 'Gift Wrapping',
                    'value' => Configuration::get('PS_GIFT_WRAPPING') ? 'Enabled' : 'Disabled',
                ],
            ],
        ];

        if ((bool)Configuration::get('PS_GIFT_WRAPPING')) {
            $generalSettings['settings']['gift_wrapping_price'] = [
                'label' => 'Gift Wrapping Price',
                'value' => (float) Configuration::get('PS_GIFT_WRAPPING_PRICE'),
            ];
            $generalSettings['settings']['gift_wrapping_tax_rules_group'] = [
                'label' => 'Gift Wrapping Tax Rule ID',
                'value' => (int) Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'),
            ];
        }

        return $generalSettings;
    }

    /**
     * Return an array with information about the server environment to be used in system report
     * @return array
     */
    public function getServerEnvironment(): array
    {
        return [
            'title'    => 'Server environment',
            'settings' => [
                'server_info' => [
                    'label' => 'Server Information',
                    'value' => $_SERVER['SERVER_SOFTWARE'],
                ],
                'php_version' => [
                    'label' => 'PHP Version',
                    'value' => phpversion(),
                ],
            ],
        ];
    }

    /**
     * Return an array of overwritten files to be used in system report
     * @return array
     */
    public function getOverwrittenMissingOrUpdatedFiles(): array
    {
        return [
            'title'    => 'File system integrity',
            'settings' => [
                'overwritten_files' => [
                    'label' => 'Overwritten Files',
                    'value' => implode(PHP_EOL, $this->getListOfOverwrittenFiles()),
                ],
                'missing_files' => [
                    'label' => 'Missing Files',
                    'value' => implode(PHP_EOL, $this->getListOfMissingFiles()),
                ],
                'updated_files' => [
                    'label' => 'Updated Files',
                    'value' => implode(PHP_EOL, $this->getListOfUpdatedFiles()),
                ],
            ],
        ];
    }

    /**
     * Return an array with the information of installed modules to be used in system report
     *
     * @return array
     */
    public function getActiveModules(): array
    {
        $installedModules = $this->module::getModulesInstalled();

        $activeModules = [
            'title' => 'Active plugins',
        ];

        foreach ($installedModules as $installedModule) {
            $module = $this->module::getInstanceByName($installedModule['name']);
            if ($module) {
                $active = ((bool)$module->active ? "Enabled" : "Disabled");
                $activeModules['settings'][$module->id]['label'] = $module->displayName;
                $activeModules['settings'][$module->id]['value'] = "Version: $module->version. Status: $active. Author: $module->author.";
            }
        }

        return $activeModules;
    }

    /**
     * Return an array with the multisafepay module settings to be used in system report
     *
     * @return array
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getMultiSafepayModuleSettings(): array
    {
        $moduleSettingsValues = (new SettingsBuilder($this->module->get('multisafepay')))->getConfigFormValues(false);

        $moduleSettings =  [
            'title'    => 'MultiSafepay Module Settings',
            'settings' => [
                'sdk_version' => [
                    'label' => 'PHP-SDK version',
                    'value' => Version::SDK_VERSION,
                ],
                'environment' => [
                    'label' => 'Environment',
                    'value' => $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_TEST_MODE'] ? 'Test' : 'Live',
                ],
                'debug_mode' => [
                    'label' => 'Debug mode',
                    'value' => $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_DEBUG_MODE'] ? 'Enabled' : 'Disabled',
                ],
                'payment_link_lifetime' => [
                    'label' => 'Payment link lifetime',
                    'value' => $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE'] . ' ' . $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT'],
                ],
                'template_id' => [
                    'label' => 'Payment Component Template ID',
                    'value' => empty($moduleSettingsValues['MULTISAFEPAY_OFFICIAL_TEMPLATE_ID_VALUE']) ? 'Default' : $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_TEMPLATE_ID_VALUE'],
                ],
                'order_description' => [
                    'label' => 'Order description',
                    'value' => $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION'],
                ],
                'shipped_trigger' => [
                    'label' => 'Shipped trigger',
                    'value' => $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_OS_TRIGGER_SHIPPED'],
                ],
                'final_order_status' => [
                    'label' => 'Final order status IDs',
                    'value' => implode(', ', (array)$moduleSettingsValues['MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS[]']),
                ],
                'second_chance' => [
                    'label' => 'Second chance',
                    'value' => $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_SECOND_CHANCE'] ? 'Enabled' : 'Disabled',
                ],
                'conf_order_email' => [
                    'label' => 'Confirmation Order Email',
                    'value' => $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL'] ? 'Enabled' : 'Disabled',
                ],
                'order_flow' => [
                    'label' => 'Order Flow',
                    'value' => $moduleSettingsValues['MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT'] ? 'Create order before payment' : 'Create order after payment',
                ],
            ],
        ];

        $moduleSettings['settings'] = array_merge($moduleSettings['settings'], $this->extractModuleOrderStatusSettings());

        return $moduleSettings;
    }

    /**
     * Return an array of each setting for each MultiSafepay payment method to be used in system report
     *
     * @return array
     * @throws Exception
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function getMultiSafepayPaymentOptionsSettings(): array
    {
        $paymentOptionsSettings = [
            'title'    => 'Payment Method Settings',
        ];

        /** @var PaymentOptionService $paymentOptionService */
        $paymentOptionService = $this->module->get('multisafepay.payment_option_service');

        /** @var BasePaymentOption $paymentOption */
        foreach ($paymentOptionService->getMultiSafepayPaymentOptions() as $paymentOption) {
            $paymentOptionsSettings['settings'][Tools::strtolower($paymentOption->getGatewayCode())]['label'] = $paymentOption->getName();
            $paymentOptionsSettings['settings'][Tools::strtolower($paymentOption->getGatewayCode())]['value'] = $this->extractPaymentOptionSetting($paymentOption->getGatewaySettings(), $paymentOption->getGatewayCode());
        }

        return $paymentOptionsSettings;
    }

    /**
     * Return an array with the order statuses definitons to be used in system report
     * @return array
     */
    public function getOrderStatusDefinitions(): array
    {
        $orderStatusesDefinitions = [
            'title'    => 'Order Statuses Definitions',
        ];

        $orderStatuses = OrderState::getOrderStates(Context::getContext()->language->id);

        foreach ($orderStatuses as $orderState) {
            $output = '';
            $output .= 'Invoice: ' . ($orderState['invoice'] ? 'Yes. ' : 'No. ');
            $output .= 'Send Email: ' . ($orderState['send_email'] ? 'Yes. ' : 'No. ');
            $output .= 'Logable: ' . ($orderState['logable'] ? 'Yes. ' : 'No. ');
            $output .= 'Delivery: ' . ($orderState['delivery'] ? 'Yes. ' : 'No. ');
            $output .= 'Shipped: ' . ($orderState['shipped'] ? 'Yes. ' : 'No. ');
            $output .= 'Paid: ' . ($orderState['paid'] ? 'Yes. ' : 'No. ');
            $output .= 'Invoiced: ' . ($orderState['pdf_invoice'] ? 'Yes. ' : 'No. ');

            $orderStatusesDefinitions['settings'][$orderState['id_order_state']]['label'] = $orderState['name'] . ' (' . $orderState['id_order_state'] . ')';
            $orderStatusesDefinitions['settings'][$orderState['id_order_state']]['value'] = $output;
        }

        return $orderStatusesDefinitions;
    }

    /**
     * Return in plain text all the information to be displayed in a textarea read only section.
     *
     * @return string
     */
    public function createPlainSystemStatusReport()
    {
        $statusReport             = $this->createSystemStatusReport();
        $plainTextStatusReport  = '';
        $plainTextStatusReport .= '=================================' . PHP_EOL;
        $plainTextStatusReport .= PHP_EOL;
        foreach ($statusReport as $statusReportSection) {
            $plainTextStatusReport .= $statusReportSection['title'] . PHP_EOL;
            foreach ($statusReportSection['settings'] as $key => $value) {
                $plainTextStatusReport .= $value['label'] . ': ' . $value['value'] . PHP_EOL;
            }
            $plainTextStatusReport .= PHP_EOL;
            $plainTextStatusReport .= '=================================' . PHP_EOL;
            $plainTextStatusReport .= PHP_EOL;
        }
        return $plainTextStatusReport;
    }

    /**
     * Return the round type value for the given id
     * @param int $roundTypeId
     * @return string
     */
    private function getPriceRoundTypeValue(int $roundTypeId): string
    {
        switch ($roundTypeId) {
            case 1:
                return "Round on each item";
            case 2:
                return "Round on each line";
            case 3:
                return "Round on the total";
            default:
                return "Undefined";
        }
    }

    /**
     * Return the round mode value for the given id
     * @param int $roundModeId
     * @return string
     */
    private function getPriceRoundModeValue(int $roundModeId): string
    {
        switch ($roundModeId) {
            case 0:
                return "Round up to the nearest value";
            case 1:
                return "Round down to the nearest value";
            case 2:
                return "Round up away from zero, when it is half way there (recommended)";
            case 3:
                return "Round down towards zero, when it is half way there";
            case 4:
                return "Round towards the next even value";
            case 5:
                return "Round towards the next odd value";
            default:
                return "Undefined";
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function isMultiStoreActive(): bool
    {
        return (bool) Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE');
    }

    /**
     * Return an array with overwritten files
     *
     * @return array
     * @throws Exception
     */
    private function getListOfOverwrittenFiles(): array
    {
        return array_filter(Tools::scandir(_PS_OVERRIDE_DIR_, 'php', '', true), function ($file) {
            return basename($file) != 'index.php';
        });
    }

    /**
     * Return an array with the missing files
     *
     * @return array
     * @throws Exception
     */
    private function getListOfMissingFiles(): array
    {
        /** @var CheckMissingOrUpdatedFiles $checkMissingOrUpdatedFilesService */
        $checkMissingOrUpdatedFilesService = new CheckMissingOrUpdatedFiles();
        return $checkMissingOrUpdatedFilesService->getListOfUpdatedFiles()['missing'];
    }

    /**
     * Return an array with the updated files
     *
     * @return array
     * @throws Exception
     */
    private function getListOfUpdatedFiles(): array
    {
        /** @var CheckMissingOrUpdatedFiles $checkMissingOrUpdatedFilesService */
        $checkMissingOrUpdatedFilesService = new CheckMissingOrUpdatedFiles();
        return $checkMissingOrUpdatedFilesService->getListOfUpdatedFiles()['updated'];
    }

    /**
     * Extract from the payment option the important settings to include in the report
     *
     * @param array $settings
     * @param string $paymentOptionGatewayCode
     * @return string
     */
    private function extractPaymentOptionSetting(array $settings, string $paymentOptionGatewayCode): string
    {
        $output = '';

        if (! empty($settings['MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_' . $paymentOptionGatewayCode]['value'])) {
            $output .= 'Min Amount: ' . $settings['MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_' . $paymentOptionGatewayCode]['value'] . '. ';
        }
        if (! empty($settings['MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_' . $paymentOptionGatewayCode]['value'])) {
            $output .= 'Max Amount: ' . $settings['MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_' . $paymentOptionGatewayCode]['value'] . '. ';
        }
        if (! empty($settings['MULTISAFEPAY_OFFICIAL_CURRENCIES_' . $paymentOptionGatewayCode]['value'])) {
            $output .= 'Currencies: ' . implode(', ', $settings['MULTISAFEPAY_OFFICIAL_CURRENCIES_' . $paymentOptionGatewayCode]['value']) . '. ';
        }
        if (! empty($settings['MULTISAFEPAY_OFFICIAL_COUNTRIES_' . $paymentOptionGatewayCode]['value'])) {
            $output .= 'Countries: ' . implode(', ', $settings['MULTISAFEPAY_OFFICIAL_COUNTRIES_' . $paymentOptionGatewayCode]['value']) . '. ';
        }
        if (! empty($settings['MULTISAFEPAY_OFFICIAL_CARRIERS_' . $paymentOptionGatewayCode]['value'])) {
            $output .= 'Carriers: ' . implode(', ', $settings['MULTISAFEPAY_OFFICIAL_CARRIERS_' . $paymentOptionGatewayCode]['value']) . '. ';
        }
        if (! empty($settings['MULTISAFEPAY_OFFICIAL_GROUPS_' . $paymentOptionGatewayCode]['value'])) {
            $output .= 'Groups: ' . implode(', ', $settings['MULTISAFEPAY_OFFICIAL_GROUPS_' . $paymentOptionGatewayCode]['value']) . '. ';
        }

        return $output ? $output : 'Default settings';
    }

    /**
     * Extract the details of the order statuses assigned to each MultiSafepay transaction status.
     *
     * @return array
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function extractModuleOrderStatusSettings(): array
    {
        $output = [];

        /** @var ExistingOrderNotificationService $notificationService */
        $notificationService = $this->module->get('multisafepay.existing_order_notification_service');
        $transactionStatuses = ['cancelled', 'expired', 'void', 'declined', 'completed', 'uncleared', 'refunded', 'partial_refunded', 'chargedback', 'shipped', 'initialized'];
        foreach ($transactionStatuses as $transactionStatus) {
            $orderStatusId = $notificationService->getOrderStatusId($transactionStatus);
            /** @var OrderState $orderState */
            $orderState = new OrderState((int)$orderStatusId, Context::getContext()->language->id);
            $output[$transactionStatus]['label'] = Tools::ucfirst(Tools::str_replace_once('_', ' ', $transactionStatus));
            $output[$transactionStatus]['value'] = $orderState->name;
        }

        return $output;
    }
}
