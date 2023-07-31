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
