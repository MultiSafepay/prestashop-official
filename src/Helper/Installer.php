<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use Language;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use Multisafepay;
use OrderState;
use Tab;
use Tools;

/**
 * Class Installer
 */
class Installer
{
    /**
     * @var Multisafepay
     */
    private $module;

    /**
     * Uninstaller constructor.
     *
     * @param Multisafepay $module
     */
    public function __construct(Multisafepay $module)
    {
        $this->module = $module;
    }

    /**
     * Call this function when installing the MultiSafepay module
     * @return void
     */
    public function install(): void
    {
        $this->registerMultiSafepayOrderStatuses();
        $this->installMultiSafepayTab();
        $this->setDefaultValues();
    }

    /**
     * Install the MultiSafepay tab
     * @return void
     */
    private function installMultiSafepayTab(): void
    {
        $idParent = Tab::getIdFromClassName('IMPROVE');

        $tab             = new Tab();
        $tab->class_name = 'AdminMultiSafepay';
        $tab->id_parent  = $idParent;
        $tab->module     = 'MultiSafepay';
        $tab->active     = true;
        $tab->icon       = 'multisafepay icon-multisafepay';
        $languages       = Language::getLanguages(true);
        foreach ($languages as $language) {
            $tab->name[$language['id_lang']] = 'MultiSafepay';
        }
        $tab->add();
    }

    /**
     * Set default values on install
     * @return void
     */
    private function setDefaultValues(): void
    {
        foreach (SettingsBuilder::getConfigFieldsAndDefaultValues() as $configField => $configData) {
            Configuration::updateGlobalValue($configField, $configData['default']);
        }

        $paymentOptionService = new PaymentOptionService($this->module);
        foreach ($paymentOptionService->getMultiSafepayPaymentOptions() as $paymentOption) {
            foreach ($paymentOption->getGatewaySettings() as $settingKey => $settings) {
                Configuration::updateGlobalValue($settingKey, $settings['default']);
            }
        }
    }

    /**
     * Register the MultiSafepay Order statuses
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function registerMultiSafepayOrderStatuses(): void
    {
        $multisafepayOrderStatuses = $this->getMultiSafepayOrderStatuses();
        foreach ($multisafepayOrderStatuses as $multisafepayOrderStatusKey => $multisafepayOrderStatusValues) {
            if (!Configuration::get('MULTISAFEPAY_OS_' . Tools::strtoupper($multisafepayOrderStatusKey))) {
                $orderState = $this->createOrderStatus($multisafepayOrderStatusValues);
                Configuration::updateGlobalValue('MULTISAFEPAY_OS_' . Tools::strtoupper($multisafepayOrderStatusKey), (int) $orderState->id);
            }
        }
    }

    /**
     * Creates the Order Statuses
     *
     * @param array $multisafepayOrderStatusValues
     * @return OrderState
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function createOrderStatus(array $multisafepayOrderStatusValues): OrderState
    {
        $orderState              = new OrderState();
        foreach (Language::getLanguages() as $language) {
            $orderState->name[$language['id_lang']] = 'MultiSafepay ' . $multisafepayOrderStatusValues['name'];
        }
        $orderState->send_email  = $multisafepayOrderStatusValues['send_mail'];
        $orderState->color       = $multisafepayOrderStatusValues['color'];
        $orderState->unremovable = false;
        $orderState->hidden      = false;
        $orderState->delivery    = false;
        $orderState->logable     = $multisafepayOrderStatusValues['logable'];
        $orderState->invoice     = $multisafepayOrderStatusValues['invoice'];
        $orderState->template    = $multisafepayOrderStatusValues['template'];
        $orderState->paid        = $multisafepayOrderStatusValues['paid'];
        $orderState->module_name = 'multisafepay';
        $orderState->add();
        return $orderState;
    }

    /**
     * Return an array with MultiSafepay order statuses
     *
     * @return array
     */
    public function getMultiSafepayOrderStatuses(): array
    {
        return [
            'initialized' => [
                'name'      => 'initialized',
                'send_mail' => false,
                'color'     => '#4169E1',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'uncleared' => [
                'name'      => 'uncleared',
                'send_mail' => false,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
            'partial_refunded' => [
                'name'      => 'partial refunded',
                'send_mail' => true,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => 'refund',
                'paid'      => false,
                'logable'   => false
            ],
            'chargeback' => [
                'name'      => 'chargeback',
                'send_mail' => true,
                'color'     => '#ec2e15',
                'invoice'   => false,
                'template'  => '',
                'paid'      => false,
                'logable'   => false
            ],
        ];
    }
}
