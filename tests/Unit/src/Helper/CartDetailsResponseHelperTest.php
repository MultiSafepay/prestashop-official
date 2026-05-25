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

namespace MultiSafepay\Tests\Helper;

use Cart;
use Configuration;
use Context;
use Currency;
use MultiSafepay\PrestaShop\Helper\CartDetailsResponseHelper;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use PrestaShopException;

class CartDetailsResponseHelperTest extends BaseMultiSafepayTest
{
    /** @var string|null */
    private $originalRequestMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalRequestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
    }

    protected function tearDown(): void
    {
        if (null === $this->originalRequestMethod) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->originalRequestMethod;
        }

        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \MultiSafepay\PrestaShop\Helper\CartDetailsResponseHelper::buildResponse
     */
    public function testBuildResponseReturnsMethodNotAllowedForNonPostRequests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $result = CartDetailsResponseHelper::buildResponse(new Context());

        self::assertSame(405, http_response_code());
        self::assertSame(['error' => 'Method Not Allowed'], json_decode($result, true));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \MultiSafepay\PrestaShop\Helper\CartDetailsResponseHelper::buildResponse
     */
    public function testBuildResponseReturnsBadRequestWhenCartIsUnavailable(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $context = new Context();
        $context->cart = null;

        $result = CartDetailsResponseHelper::buildResponse($context);

        self::assertSame(400, http_response_code());
        self::assertSame(
            ['error' => 'Cart details are unavailable for the current session'],
            json_decode($result, true)
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \MultiSafepay\PrestaShop\Helper\CartDetailsResponseHelper::buildResponse
     */
    public function testBuildResponseReturnsServerErrorWhenCartDetailsResolutionFails(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $context = new Context();
        $context->cart = $this->createLoadedCartMock(new PrestaShopException('Cart details failed'));

        $result = CartDetailsResponseHelper::buildResponse($context);

        self::assertSame(500, http_response_code());
        self::assertSame(['error' => 'Unable to retrieve cart details'], json_decode($result, true));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @covers \MultiSafepay\PrestaShop\Helper\CartDetailsResponseHelper::buildResponse
     */
    public function testBuildResponseReturnsCartDetailsForAvailableCart(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $currencyId = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $context = new Context();
        $context->cart = $this->createLoadedCartMock(null, $currencyId);

        $result = CartDetailsResponseHelper::buildResponse($context);

        self::assertSame(
            [
                'total_price' => 12.34,
                'currency_code' => (new Currency($currencyId))->iso_code,
                'country_code' => '',
            ],
            json_decode($result, true)
        );
    }

    private function createLoadedCartMock($exception = null, int $currencyId = null): Cart
    {
        $cart = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrderTotal'])
            ->getMock();

        $cart->id = 123;
        $cart->id_currency = $currencyId ?: (int) Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->id_address_invoice = 0;
        $cart->id_address_delivery = 0;

        if ($exception) {
            $cart->method('getOrderTotal')->willThrowException($exception);
            return $cart;
        }

        $cart->method('getOrderTotal')->willReturn(12.34);

        return $cart;
    }
}
