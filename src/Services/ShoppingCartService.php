<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Services;

use Cart;
use Configuration;
use Multisafepay;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\ShippingItem;
use MultiSafepay\PrestaShop\Helper\LoggerHelper;
use MultiSafepay\PrestaShop\Helper\MoneyHelper;
use MultiSafepay\ValueObject\CartItem;
use MultiSafepay\ValueObject\Weight;
use Order;
use Tools;
use PaymentModule;

/**
 * Class ShoppingCartService
 * @package MultiSafepay\PrestaShop\Services
 */
class ShoppingCartService
{
    public const PRESTASHOP_ROUNDING_PRECISION = 2;

    /**
     * @var Multisafepay
     */
    private $module;

    /**
     * ShoppingCartService constructor.
     *
     * @param Multisafepay $module
     */
    public function __construct(Multisafepay $module)
    {
        $this->module = $module;
    }

    /**
     * @param Cart $shoppingCart
     * @param string $currencyIsoCode
     * @param int $orderRoundType
     *
     * @return ShoppingCart
     */
    public function createShoppingCart(
        Cart $shoppingCart,
        string $currencyIsoCode,
        int $orderRoundType,
        string $weightUnit
    ): ShoppingCart {
        /** @var array $products */
        $products    = $shoppingCart->getProductsWithSeparatedGifts();
        $cartSummary = $shoppingCart->getSummaryDetails();

        $cartItems = [];
        foreach ($products as $product) {
            $cartItems[] = $this->createCartItemFromProduct($product, $currencyIsoCode, $orderRoundType, $weightUnit);
        }

        $totalDiscount = $cartSummary['total_discounts'] ?? 0;
        if ($totalDiscount > 0) {
            $cartItems[] = $this->createDiscountCartItem($totalDiscount, $currencyIsoCode);
        }

        $totalWrapping = $cartSummary['total_wrapping'] ?? 0;
        if ($totalWrapping > 0) {
            $cartItems[] = $this->createWrappingCartItem($totalWrapping, $currencyIsoCode);
        }

        $cartItems[] = $this->createShippingItem($cartSummary, $currencyIsoCode);

        return new ShoppingCart($cartItems);
    }

    /**
     * @param array $product
     * @param string $currencyIsoCode
     * @param int $orderRoundType
     *
     * @return CartItem
     */
    private function createCartItemFromProduct(
        array $product,
        string $currencyIsoCode,
        int $orderRoundType,
        string $weightUnit
    ): CartItem {
        $merchantItemId = (string)$product['id_product'];
        $productName    = $product['name'];
        if (!empty($product['attributes_small'])) {
            $productName    .= ' ( '.$product['attributes_small'].' )';
            $merchantItemId .= '-'.$product['id_product_attribute'];
        }

        /**
         * We add '-gift' to the merchantItemId to prevent issues when someone has
         * two or more of the same item, but one of them is a gift
         */
        if ($this->productIsGift($product)) {
            $merchantItemId .= '-gift';
        }

        return $this->createCartItem(
            $productName,
            (int)$product['quantity'],
            $merchantItemId,
            $this->calculatePriceForProduct($product, $orderRoundType),
            $currencyIsoCode,
            (float)$product['rate'],
            new Weight($weightUnit, (float)$product['weight'])
        );
    }

    /**
     * @param array $product
     * @param int $orderRoundType
     *
     * @return float
     */
    private function calculatePriceForProduct(array $product, int $orderRoundType): float
    {
        /**
         * If the product is a gift product, the price should be 0
         */
        if ($this->productIsGift($product)) {
            return 0;
        }

        $taxRate = (float)$product['rate'];
        $price   = $product['price_with_reduction'];

        /**
         * If rounding mode is set to round per item, we have to round the price of each item before
         * adding it to the shopping cart to prevent 1 cent differences
         */
        if (Order::ROUND_ITEM === $orderRoundType) {
            $price = Tools::ps_round($price, self::PRESTASHOP_ROUNDING_PRECISION);
        }

        return $price * 100 / (100 + $taxRate);
    }

    /**
     * @param float $totalDiscount
     * @param string $currencyIsoCode
     *
     * @return CartItem
     */
    private function createDiscountCartItem(float $totalDiscount, string $currencyIsoCode): CartItem
    {
        return $this->createCartItem(
            $this->module->l('Discount'),
            1,
            'Discount',
            -$totalDiscount,
            $currencyIsoCode,
            0
        );
    }

    /**
     * @param float $totalWrapping
     * @param string $currencyIsoCode
     *
     * @return CartItem
     */
    private function createWrappingCartItem(float $totalWrapping, string $currencyIsoCode): CartItem
    {
        return $this->createCartItem(
            $this->module->l('Wrapping'),
            1,
            'Wrapping',
            $totalWrapping,
            $currencyIsoCode,
            0
        );
    }

    /**
     * @param string $name
     * @param int $quantity
     * @param string $merchantItemId
     * @param float $price
     * @param string $currencyCode
     * @param float $taxrate
     * @param Weight|null $weight
     *
     * @return CartItem
     */
    private function createCartItem(
        string $name,
        int $quantity,
        string $merchantItemId,
        float $price,
        string $currencyCode,
        float $taxrate,
        Weight $weight = null
    ): CartItem {
        $cartItem = new CartItem();
        $cartItem
            ->addName($name)
            ->addQuantity($quantity)
            ->addMerchantItemId($merchantItemId)
            ->addUnitPrice(
                MoneyHelper::createMoney($price, $currencyCode)
            )
            ->addTaxRate($taxrate);

        if (isset($weight)) {
            $cartItem->addWeight($weight);
        }

        return $cartItem;
    }

    /**
     * @param array $cartSummary
     * @param string $currencyIsoCode
     *
     * @return CartItem
     */
    private function createShippingItem(array $cartSummary, string $currencyIsoCode): CartItem
    {
        $shippingItem = new ShippingItem();

        $totalShippingTax = $cartSummary['total_shipping'] - $cartSummary['total_shipping_tax_exc'];
        $shippingTaxRate  = $cartSummary['total_shipping'] > 0 ? ($totalShippingTax * 100) / ($cartSummary['total_shipping'] - $totalShippingTax) : 0;

        return $shippingItem
            ->addName(($cartSummary['carrier']->name ?? $this->module->l('Shipping')))
            ->addQuantity(1)
            ->addUnitPrice(
                MoneyHelper::createMoney((float)$cartSummary['total_shipping_tax_exc'], $currencyIsoCode)
            )
            ->addTaxRate($shippingTaxRate);
    }

    /**
     * @param array $product
     *
     * @return bool
     */
    private function productIsGift(array $product): bool
    {
        return isset($product['is_gift']) && true === $product['is_gift'];
    }
}
