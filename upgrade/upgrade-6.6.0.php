<?php

use MultiSafepay\PrestaShop\Helper\Installer;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param MultisafepayOfficial $module
 * @return bool
 */
function upgrade_module_6_6_0($module): bool
{
    try {
        (new Installer($module))->installManualCaptureResources();
        return true;
    } catch (Throwable $exception) {
        LoggerHelper::logException('error', $exception, 'MultiSafepay upgrade 6.6.0: manual capture setup failed.');
        return false;
    }
}
