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

namespace MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder;

use Cart;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\ValueObject\CartItem;
use MultisafepayOfficial;

/**
 * Class DiscountItemBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest\ShoppingCartBuilder
 */
class WrappingItemBuilder implements ShoppingCartBuilderInterface
{
    public const CLASS_NAME = 'WrappingItemBuilder';

    /**
     * @var MultisafepayOfficial
     */
    private $module;

    /**
     * DiscountItemBuilder constructor.
     *
     * @param MultisafepayOfficial $module
     */
    public function __construct(MultisafepayOfficial $module)
    {
        $this->module = $module;
    }

    /**
     * @param Cart $cart
     * @param array $cartSummary
     * @param string $currencyIsoCode
     *
     * @return array|CartItem[]
     */
    public function build(Cart $cart, array $cartSummary, string $currencyIsoCode): array
    {
        $totalWrapping = $cartSummary['total_wrapping'] ?? 0;
        if ($totalWrapping <= 0) {
            return [];
        }

        $cartItem = new CartItem();
        $cartItem
            ->addName($this->module->l('Wrapping', self::CLASS_NAME))
            ->addQuantity(1)
            ->addMerchantItemId('Wrapping')
            ->addUnitPrice(
                MoneyHelper::createMoney($totalWrapping, $currencyIsoCode)
            )
            ->addTaxRate(0);

        return [$cartItem];
    }
}
