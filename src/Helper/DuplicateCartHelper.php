<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Helper;

use Cart;
use Context;
use Customer;
use Currency;
use Language;
use Configuration;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;

class DuplicateCartHelper
{

    /**
     * Duplicates the cart object.
     * Commonly used after cancel an order.
     *
     * @param Cart $cart
     * @return void
     */
    public static function duplicateCart(Cart $cart): void
    {
        $duplicatedCart                 = $cart->duplicate();
        Context::getContext()->cart     = $duplicatedCart['cart'];
        Context::getContext()->customer = new Customer((int) $cart->id_customer);
        Context::getContext()->currency = new Currency((int) $cart->id_currency);
        Context::getContext()->language = new Language((int) $cart->id_lang);
        Context::getContext()->cookie->__set('id_cart', $duplicatedCart['cart']->id);

        if (Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE')) {
            LoggerHelper::logInfo('Cart ID: ' . $cart->id . ' has been duplicated');
        }
    }
}
