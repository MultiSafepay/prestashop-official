<?php declare(strict_types=1);

use MultiSafepay\PrestaShop\Helper\CancelOrderHelper;
use MultiSafepay\PrestaShop\Helper\DuplicateCartHelper;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;

class MultisafepayCancelModuleFrontController extends ModuleFrontController
{
    /**
     *
     * @return string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if ($this->module->active == false || !Tools::getValue('id_reference') || !Tools::getValue('id_cart')) {
            if (Configuration::get('MULTISAFEPAY_DEBUG_MODE')) {
                LoggerHelper::logWarning('Warning: It seems postProcess method of MultiSafepay cancel controller is being called without the required parameters.');
            }
            header('HTTP/1.0 400 Bad request');
            die();
        }

        // Cancel orders
        CancelOrderHelper::cancelOrder((Order::getByReference(Tools::getValue('id_reference'))));

        // Duplicate cart
        DuplicateCartHelper::duplicateCart((new Cart(Tools::getValue('id_cart'))));

        // Redirect to checkout page
        Tools::redirect($this->context->link->getPageLink('order', true, null, ['step' => '3']));
    }
}
