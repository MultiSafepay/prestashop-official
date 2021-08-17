<?php

namespace MultiSafepay\PrestaShop\Helper;

use Configuration;
use Language;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use Tab;

/**
 * Class Installer
 */
class Installer
{

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

        $tab = new Tab();
        $tab->class_name = 'AdminMultiSafepay';
        $tab->id_parent = $idParent;
        $tab->module = 'MultiSafepay';
        $tab->active = true;
        $tab->icon = '';
        $languages = Language::getLanguages(true);
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
        if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
            LoggerHelper::logInfo('Default values has been set in database');
        }
    }
}
