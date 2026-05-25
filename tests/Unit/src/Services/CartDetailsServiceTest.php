<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\Tests\Services;

use Address;
use Cart;
use Configuration;
use Country;
use Currency;
use MultiSafepay\PrestaShop\Services\CartDetailsService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use PrestaShopDatabaseException;
use PrestaShopException;

class CartDetailsServiceTest extends BaseMultiSafepayTest
{
    /** @var CartDetailsService */
    private $cartDetailsService;

    /** @var Address[] */
    private $testAddresses = [];

    /** @var int */
    private $defaultCountryId;

    /** @var int */
    private $defaultCurrencyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartDetailsService = new CartDetailsService();
        $this->defaultCountryId = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        $this->defaultCurrencyId = (int) Configuration::get('PS_CURRENCY_DEFAULT');
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\CartDetailsService::getCartDetails
     */
    public function testGetCartDetailsReturnsTotalCurrencyAndInvoiceCountryCode(): void
    {
        $invoiceAddress = $this->createTestAddress($this->defaultCountryId);
        $cart = $this->createCartMock(123.45, $this->defaultCurrencyId, (int) $invoiceAddress->id, 0);

        $result = $this->cartDetailsService->getCartDetails($cart);

        self::assertSame(123.45, $result['total_price']);
        self::assertSame((new Currency($this->defaultCurrencyId))->iso_code, $result['currency_code']);
        self::assertSame(Country::getIsoById($this->defaultCountryId), $result['country_code']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\CartDetailsService::getCartDetails
     */
    public function testGetCartDetailsFallsBackToDeliveryCountryWhenInvoiceAddressIsMissing(): void
    {
        $deliveryAddress = $this->createTestAddress($this->defaultCountryId);
        $cart = $this->createCartMock(87.65, $this->defaultCurrencyId, 0, (int) $deliveryAddress->id);

        $result = $this->cartDetailsService->getCartDetails($cart);

        self::assertSame(Country::getIsoById($this->defaultCountryId), $result['country_code']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\CartDetailsService::getCartDetails
     */
    public function testGetCartDetailsReturnsEmptyCountryCodeWhenAddressesAreMissing(): void
    {
        $cart = $this->createCartMock(42.00, $this->defaultCurrencyId, 0, 0);

        $result = $this->cartDetailsService->getCartDetails($cart);

        self::assertSame('', $result['country_code']);
    }

    /**
     * @dataProvider cartDetailsExceptionProvider
     * @covers \MultiSafepay\PrestaShop\Services\CartDetailsService::getCartDetails
     */
    public function testGetCartDetailsPropagatesPrestashopExceptions($exception): void
    {
        $cart = $this->createCartMockThatThrows($exception, $this->defaultCurrencyId);

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $this->cartDetailsService->getCartDetails($cart);
    }

    public function cartDetailsExceptionProvider(): array
    {
        return [
            'prestashop exception' => [new PrestaShopException('Cart details failed')],
            'prestashop database exception' => [new PrestaShopDatabaseException('Cart details query failed')],
        ];
    }

    protected function tearDown(): void
    {
        foreach ($this->testAddresses as $address) {
            if ($address->id) {
                $address->delete();
            }
        }

        parent::tearDown();
    }

    private function createCartMock(float $orderTotal, int $currencyId, int $invoiceAddressId, int $deliveryAddressId): Cart
    {
        $cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrderTotal'])
            ->getMock();

        $cart->id_currency = $currencyId;
        $cart->id_address_invoice = $invoiceAddressId;
        $cart->id_address_delivery = $deliveryAddressId;
        $cart->method('getOrderTotal')->willReturn($orderTotal);

        return $cart;
    }

    private function createCartMockThatThrows($exception, int $currencyId): Cart
    {
        $cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrderTotal'])
            ->getMock();

        $cart->id_currency = $currencyId;
        $cart->id_address_invoice = 0;
        $cart->id_address_delivery = 0;
        $cart->method('getOrderTotal')->willThrowException($exception);

        return $cart;
    }

    private function createTestAddress(int $countryId): Address
    {
        $address = new Address();
        $address->id_customer = 1;
        $address->id_country = $countryId;
        $address->firstname = 'John';
        $address->lastname = 'Doe';
        $address->address1 = 'Kraanspoor 39';
        $address->postcode = '1033 SC';
        $address->city = 'Amsterdam';
        $address->alias = 'Cart Details Test';
        self::assertTrue($address->add(), 'Failed to create test address fixture.');

        $this->testAddresses[] = $address;

        return $address;
    }
}
