<?php declare(strict_types=1);

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\Tests\Services;

use Cart;
use MultiSafepay\PrestaShop\Services\ShoppingCartService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;

class ShoppingCartServiceTest extends BaseMultiSafepayTest
{

    /**
     * @var ShoppingCartService
     */
    protected $shoppingCartService;

    public function setUp(): void
    {
        parent::setUp();

        $this->shoppingCartService = $this->container->get('multisafepay.shopping_cart_service');
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\ShoppingCartService::createShoppingCart
     */
    public function testSimpleCreateShoppingCart()
    {
        $shoppingCart = $this->shoppingCartService->createShoppingCart(
            $this->createMockCart(
                [$this->createProduct('Test product', 12345, 39.9, 10.0),],
                $this->createCartSummary()
            ),
            'EUR',
            Order::ROUND_LINE,
            'kg'
        );

        $shoppingCartData = $shoppingCart->getData();
        $cartItem         = $shoppingCartData['items'][0];

        self::assertEquals('12345', $cartItem['merchant_item_id']);
        self::assertEquals(36.2727272727, $cartItem['unit_price']);
        self::assertEquals('Test product', $cartItem['name']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\ShoppingCartService::createShoppingCart
     */
    public function testSimpleCreateShoppingCartWithDiscount()
    {
        $shoppingCart = $this->shoppingCartService->createShoppingCart(
            $this->createMockCart(
                [$this->createProduct('Test product', 12345, 39.9, 10.0),],
                $this->createCartSummary(0.0, 0.0, 5.0)
            ),
            'EUR',
            Order::ROUND_LINE,
            'kg'
        );

        $shoppingCartData = $shoppingCart->getData();
        $cartItems        = $shoppingCartData['items'];
        $discountItem     = [];

        foreach ($cartItems as $cartItem) {
            if ($cartItem['name'] === 'Discount') {
                $discountItem = $cartItem;
                break;
            }
        }

        // Should contain 1 product, 1 shipping line and 1 discount line
        self::assertCount(3, $cartItems);
        self::assertEquals('Discount', $discountItem['merchant_item_id']);
        self::assertEquals('-5.0000000000', $discountItem['unit_price']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\ShoppingCartService::createShoppingCart
     */
    public function testSimpleCreateShoppingCartWithShipping()
    {
        $shoppingCart = $this->shoppingCartService->createShoppingCart(
            $this->createMockCart(
                [$this->createProduct('Test product', 12345, 39.9, 10.0),],
                $this->createCartSummary(5.0, 5.0)
            ),
            'EUR',
            Order::ROUND_LINE,
            'kg'
        );

        $shoppingCartData = $shoppingCart->getData();
        $cartItems        = $shoppingCartData['items'];
        $shippingItem     = [];

        foreach ($cartItems as $cartItem) {
            if ('Shipping' === $cartItem['name']) {
                $shippingItem = $cartItem;
                break;
            }
        }

        // Should contain 1 product and 1 shipping line
        self::assertCount(2, $cartItems);
        self::assertEquals('0', $shippingItem['tax_table_selector']);
        self::assertEquals('msp-shipping', $shippingItem['merchant_item_id']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\ShoppingCartService::createShoppingCart
     */
    public function testSimpleCreateShoppingCartWithWrapping()
    {
        $shoppingCart = $this->shoppingCartService->createShoppingCart(
            $this->createMockCart(
                [$this->createProduct('Test product', 12345, 39.9, 10.0),],
                $this->createCartSummary(0.0, 0.0, 0.0, 10.0)
            ),
            'EUR',
            Order::ROUND_LINE,
            'kg'
        );

        $shoppingCartData = $shoppingCart->getData();
        $cartItems        = $shoppingCartData['items'];
        $wrappingItem     = [];

        foreach ($cartItems as $cartItem) {
            if ('Wrapping' === $cartItem['name']) {
                $wrappingItem = $cartItem;
                break;
            }
        }

        // Should contain 1 product, 1 shipping line and 1 wrapping line
        self::assertCount(3, $cartItems);
        self::assertEquals('Wrapping', $wrappingItem['merchant_item_id']);
        self::assertEquals('10.0000000000', $wrappingItem['unit_price']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\ShoppingCartService::createShoppingCart
     */
    public function testCreateShoppingCartRoundPerLine()
    {
        $shoppingCart = $this->shoppingCartService->createShoppingCart(
            $this->createMockCart(
                [$this->createProduct('Test product', 12345, 33.915, 22.0),],
                $this->createCartSummary()
            ),
            'EUR',
            Order::ROUND_LINE,
            'kg'
        );

        $shoppingCartData = $shoppingCart->getData();
        $cartItem         = $shoppingCartData['items'][0];

        self::assertEquals('12345', $cartItem['merchant_item_id']);
        self::assertEquals(27.7991803279, $cartItem['unit_price']);
        self::assertEquals('Test product', $cartItem['name']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\ShoppingCartService::createShoppingCart
     */
    public function testCreateShoppingCartRoundPerItem()
    {
        $shoppingCart = $this->shoppingCartService->createShoppingCart(
            $this->createMockCart(
                [$this->createProduct('Test product', 12345, 33.915, 22.0),],
                $this->createCartSummary()
            ),
            'EUR',
            Order::ROUND_ITEM,
            'kg'
        );

        $shoppingCartData = $shoppingCart->getData();
        $cartItem         = $shoppingCartData['items'][0];

        self::assertEquals('12345', $cartItem['merchant_item_id']);
        self::assertEquals(27.8032786885, $cartItem['unit_price']);
        self::assertEquals('Test product', $cartItem['name']);
    }

    /**
     * @param array $products
     * @param array $cartSummary
     *
     * @return Cart
     */
    private function createMockCart(array $products, array $cartSummary): Cart
    {
        $mockCart = $this->getMockBuilder(Cart::class)->onlyMethods(['getProducts', 'getSummaryDetails'])->getMock();
        $mockCart->expects(self::atLeastOnce())->method('getProducts')->willReturn($products);
        $mockCart->expects(self::atLeastOnce())->method('getSummaryDetails')->willReturn($cartSummary);

        return $mockCart;
    }

    /**
     * @param float $totalShipping
     * @param float $totalShippingTaxExc
     * @param float $totalDiscounts
     * @param float $totalWrapping
     * @param array $giftProducts
     *
     * @return array
     */
    private function createCartSummary(
        $totalShipping = 0.0,
        $totalShippingTaxExc = 0.0,
        $totalDiscounts = 0.0,
        $totalWrapping = 0.0,
        $giftProducts = []
    ): array {
        return [
            'gift_products'          => $giftProducts,
            'total_discounts'        => $totalDiscounts,
            'total_wrapping'         => $totalWrapping,
            'total_shipping'         => $totalShipping,
            'total_shipping_tax_exc' => $totalShippingTaxExc,
        ];
    }

    /**
     * @param string $name
     * @param int $idProduct
     * @param float $priceWithReduction
     * @param float $rate
     * @param int $quantity
     *
     * @return array
     */
    private function createProduct(
        $name = 'Test product',
        $idProduct = 12345,
        $priceWithReduction = 10.0,
        $rate = 0.0,
        $quantity = 1,
        $weight = 1
    ): array {
        return [
            'name'                 => $name,
            'id_product'           => $idProduct,
            'quantity'             => $quantity,
            'price_with_reduction' => $priceWithReduction,
            'rate'                 => $rate,
            'weight'               => $weight,
        ];
    }
}
