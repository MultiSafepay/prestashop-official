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

namespace MultiSafepay\PrestaShop\Services;

use Address;
use Cart;
use Country;
use Currency;
use Exception;
use PrestaShopDatabaseException;
use PrestaShopException;

class CartDetailsService
{
    /**
     * Direct wallet sheets need the current server-side cart after dynamic checkout updates.
     *
     * @param Cart $cart
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */
    public function getCartDetails(Cart $cart): array
    {
        return [
            'total_price' => (float)$cart->getOrderTotal(),
            'currency_code' => (new Currency((int)$cart->id_currency))->iso_code,
            'country_code' => $this->getCountryCode($cart),
        ];
    }

    /**
     * Return the country code from the cart invoice address.
     *
     * @param Cart $cart
     * @return string
     */
    private function getCountryCode(Cart $cart): string
    {
        $addressId = (int)$cart->id_address_invoice ?: (int)$cart->id_address_delivery;

        if ($addressId <= 0) {
            return '';
        }

        $address = new Address($addressId);

        if (empty($address->id_country)) {
            return '';
        }

        return Country::getIsoById((int)$address->id_country);
    }
}
