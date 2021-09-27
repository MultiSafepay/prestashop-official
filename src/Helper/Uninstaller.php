<?php

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use OrderState;
use PrestaShopCollection;
use Tab;
use Multisafepay;

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
        $this->module = $module;
    }

    /**
     * Call this function when uninstalling the MultiSafepay module
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function uninstall(): void
    {
        $this->uninstallMultiSafepayTab();
        $this->deleteConfigValues();
        $this->removeOrderStatuses();
    }

    /**
     * @return void
     * @throws \PrestaShopException
     * @throws \PrestaShopDatabaseException
     */
    protected function uninstallMultiSafepayTab(): void
    {
        $tabId = Tab::getIdFromClassName('AdminMultiSafepay');
        $tab   = new Tab($tabId);
        $tab->delete();
    }

    /**
     * Delete all saved config values
     * @return void
     */
    protected function deleteConfigValues(): void
    {
        $configValues = (new SettingsBuilder($this->module))->getConfigFormValues();
        foreach (array_keys($configValues) as $configValue) {
            Configuration::deleteByName((string)$configValue);
        }
        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Module config values has been removed');
        }
    }

    /**
     * @throws \PrestaShopException
     */
    protected function removeOrderStatuses(): void
    {
        /** @var OrderState[] $orderStatuses */
        $orderStatuses = (new PrestaShopCollection('OrderState'))->where(
            'module_name',
            '=',
            $this->module->name
        )->getResults();

        if (!empty($orderStatuses)) {
            foreach ($orderStatuses as $orderStatus) {
                $orderStatus->softDelete();
            }
        }
    }
}
