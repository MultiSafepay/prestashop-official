<?php

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use Tab;
use Multisafepay;
use PaymentModule;

/**
 * Class Uninstaller
 */
class Uninstaller
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
        $this->module          = $module;
    }

    /**
     * Call this function when uninstalling the MultiSafepay module
     * @return void
     */
    public function uninstall()
    {
        $this->uninstallMultiSafepayTab();
        $this->deleteConfigValues();
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @return void
     */
    protected function uninstallMultiSafepayTab()
    {
        $tabId = Tab::getIdFromClassName('AdminMultiSafepay');
        $tab = new Tab($tabId);
        $tab->delete();
    }

    /**
     * Delete all saved config values
     * @return void
     */
    protected function deleteConfigValues()
    {
        $configValues = (new SettingsBuilder($this->module))->getConfigFormValues();
        foreach (array_keys($configValues) as $configValue) {
            Configuration::deleteByName((string)$configValue);
        }
        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Module config values has been removed');
        }
    }
}
