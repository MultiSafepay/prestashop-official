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
use Customer;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class CustomerSecureKeyHelper
 *
 * Helper class to safely resolve the customer's `secure_key` for a given cart
 * when handling MultiSafepay payment return flows.
 *
 * Some payment methods (e.g. Bancontact or iDEAL through a bank mobile app)
 * return the customer to the shop in a different browser without the
 * PrestaShop session cookie. In that case the session customer is empty and
 * the confirmation URL ends up without a `key`, causing PrestaShop's
 * OrderConfirmationController to redirect the customer to the home or login
 * page.
 *
 * This helper prefers the session customer (when it matches the cart owner)
 * and otherwise falls back to a `key` query parameter that must match the
 * cart customer's stored `secure_key` using a timing-safe comparison.
 *
 * @package MultiSafepay\PrestaShop\Helper
 */
class CustomerSecureKeyHelper
{
    /**
     * Resolve the customer's secure key for the given cart.
     *
     * Resolution order:
     *  1. Session customer, if it exists and matches the cart owner.
     *  2. `key` query parameter, only if it matches the cart customer's
     *     stored `secure_key` (validated with hash_equals).
     *  3. Empty string, as a safe default.
     *
     * @param Cart         $cart
     * @param Context|null $context Optional; defaults to Context::getContext().
     *
     * @return string The validated secure key, or an empty string when it
     *                cannot be safely resolved.
     */
    public static function resolveForCart(Cart $cart, ?Context $context = null): string
    {
        $context = $context ?? Context::getContext();

        if ($context !== null
            && isset($context->customer)
            && (int) $context->customer->id > 0
            && (int) $context->customer->id === (int) $cart->id_customer
            && !empty($context->customer->secure_key)
        ) {
            return (string) $context->customer->secure_key;
        }

        $providedKey = (string) Tools::getValue('key', '');
        if ($providedKey === '' || (int) $cart->id_customer <= 0) {
            return '';
        }

        $cartCustomer = new Customer((int) $cart->id_customer);
        if (!Validate::isLoadedObject($cartCustomer) || empty($cartCustomer->secure_key)) {
            return '';
        }

        if (hash_equals((string) $cartCustomer->secure_key, $providedKey)) {
            return (string) $cartCustomer->secure_key;
        }

        return '';
    }
}
