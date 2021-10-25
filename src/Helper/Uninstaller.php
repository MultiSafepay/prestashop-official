<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use OrderState;
use PrestaShopBundle\Entity\Repository\TabRepository;
use PrestaShopCollection;
use Tab;
use MultisafepayOfficial;

/**
 * Class Uninstaller
 */
class Uninstaller
{

    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * Uninstaller constructor.
     *
     * @param MultisafepayOfficial $module
     */
    public function __construct(MultisafepayOfficial $module)
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
        /** @var TabRepository $tabRepository */
        $tabRepository = $this->module->get('prestashop.core.admin.tab.repository');
        $tabId = $tabRepository->findOneIdByClassName('AdminMultisafepayOfficial');

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
