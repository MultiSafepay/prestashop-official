<?php

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use Language;
use Multisafepay;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use Tab;
use PaymentModule;

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
        $this->installMultiSafepayTab();
        $this->setDefaultValues();
    }

    /**
     * Install the MultiSafepay tab
     * @return void
     */
    protected function installMultiSafepayTab(): void
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
    public function setDefaultValues(): void
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
}
