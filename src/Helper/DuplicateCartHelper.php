<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
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
use Currency;
use Customer;
use Exception;
use Language;

if (!defined('_PS_VERSION_')) {
    exit;
}

class DuplicateCartHelper
{

    /**
     * Duplicates the cart object.
     * Typically used after canceling an order to restore the cart for the customer.
     *
     * @param Cart $cart
     * @param Context $context The context to update with the duplicated cart
     * @return void
     * @throws Exception
     */
    public static function duplicateCart(Cart $cart, Context $context): void
    {
        $duplicatedCart = $cart->duplicate();

        // Update the context with the duplicated cart data
        if (isset($duplicatedCart['cart'])) {
            $context->cart = $duplicatedCart['cart'];
            $context->customer = new Customer((int) $cart->id_customer);
            $context->currency = new Currency((int) $cart->id_currency);
            $context->language = new Language((int) $cart->id_lang);
            $context->cookie->__set('id_cart', $duplicatedCart['cart']->id);
        }

        LoggerHelper::log(
            'info',
            'Cart has ' . (!isset($duplicatedCart['cart']) ? 'not ' : '') . 'been duplicated',
            true,
            null,
            $cart->id ?? null
        );
    }
}
