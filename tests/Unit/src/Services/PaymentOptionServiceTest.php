<?php
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

namespace MultiSafepay\PrestaShop\Tests\Services;

use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\InvalidDataInitializationException;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;

class PaymentOptionServiceTest extends BaseMultiSafepayTest
{
    private $paymentOptionService;
    private $moduleMock;
    private $mockPaymentMethodsData;

    public function setUp(): void
    {
        $this->moduleMock = $this->createMock(MultisafepayOfficial::class);

        // Create a partial mock of PaymentOptionService
        $this->paymentOptionService = $this->getMockBuilder(PaymentOptionService::class)
            ->setConstructorArgs([$this->moduleMock])
            ->onlyMethods(['getMultiSafepayPaymentMethods'])
            ->getMock();

        // Load the JSON data
        $jsonData = $this->getTestPaymentMethodsJson();
        $this->mockPaymentMethodsData = $this->convertJsonToPaymentMethods($jsonData);

        // Set up the mock to return the test data
        $this->paymentOptionService->method('getMultiSafepayPaymentMethods')
            ->willReturn($this->mockPaymentMethodsData);
    }

    /**
     * Test getMultiSafepayPaymentOptions method
     */
    public function testGetMultiSafepayPaymentOptions()
    {
        $paymentOptions = $this->paymentOptionService->getMultiSafepayPaymentOptions();

        $this->assertNotEmpty($paymentOptions);

        // Check that all returned items are BasePaymentOption instances
        foreach ($paymentOptions as $option) {
            $this->assertInstanceOf(BasePaymentOption::class, $option);
        }
    }

    /**
     * Test getMultiSafepayPaymentOptions method
     */
    public function testGetMultiSafepayPaymentOption()
    {
        $paymentOption = $this->paymentOptionService->getMultiSafepayPaymentOption('IDEAL');
        $this->assertInstanceOf(BasePaymentOption::class, $paymentOption);
        $this->assertEquals('IDEAL', $paymentOption->getGatewayCode());
    }

    /**
     * Test getMultiSafepayPaymentOptions method
     */
    public function testGetActivePaymentOptionsWhenAllAreInactive()
    {
        $activePaymentOption = $this->paymentOptionService->getActivePaymentOptions();
        $this->assertEmpty($activePaymentOption);
    }

    /**
     * Test getMultiSafepayPaymentOptions method
     */
    public function testGetActivePaymentOptionsWhenOneIsActive()
    {
        // Create a mock payment option for IDEAL
        $mockPaymentOptionIdeal = $this->createMock(BasePaymentOption::class);
        $mockPaymentOptionIdeal->method('getGatewayCode')->willReturn('IDEAL');
        $mockPaymentOptionIdeal->method('isActive')->willReturn(true);

        // Create a mock of PaymentOptionService
        $paymentOptionService = $this->getMockBuilder(PaymentOptionService::class)
            ->setConstructorArgs([$this->moduleMock])
            ->onlyMethods(['getMultiSafepayPaymentOptions'])
            ->getMock();

        // Return our mocked payment option
        $paymentOptionService->method('getMultiSafepayPaymentOptions')
            ->willReturn([$mockPaymentOptionIdeal]);

        // Test getActivePaymentOptions
        $activePaymentOptions = $paymentOptionService->getActivePaymentOptions();

        // Should only return the active IDEAL option
        $this->assertCount(1, $activePaymentOptions);
    }

    public function excludePaymentOptionWhenStatusIsDisabled()
    {
        $mockPaymentOption = $this->createMock(BasePaymentOption::class);
        $mockPaymentOption->method('getGatewaySettings')->willReturn([
            'MULTISAFEPAY_OFFICIAL_GATEWAY_TEST' => ['value' => false],
        ]);
        $mockPaymentOption->method('getUniqueName')->willReturn('TEST');

        $mockCart = $this->createMock(Cart::class);

        $result = $this->paymentOptionService->excludePaymentOptionByPaymentOptionSettings($mockPaymentOption, $mockCart);

        $this->assertTrue($result);
    }

    public function excludePaymentOptionWhenOrderTotalIsBelowMinAmount()
    {
        $mockPaymentOption = $this->createMock(BasePaymentOption::class);
        $mockPaymentOption->method('getGatewaySettings')->willReturn([
            'MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_TEST' => ['value' => 50],
        ]);
        $mockPaymentOption->method('getUniqueName')->willReturn('TEST');

        $mockCart = $this->createMock(Cart::class);
        $mockCart->method('getOrderTotal')->willReturn(30);

        $result = $this->paymentOptionService->excludePaymentOptionByPaymentOptionSettings($mockPaymentOption, $mockCart);

        $this->assertTrue($result);
    }

    public function excludePaymentOptionWhenOrderTotalExceedsMaxAmount()
    {
        $mockPaymentOption = $this->createMock(BasePaymentOption::class);
        $mockPaymentOption->method('getGatewaySettings')->willReturn([
            'MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_TEST' => ['value' => 100],
        ]);
        $mockPaymentOption->method('getUniqueName')->willReturn('TEST');

        $mockCart = $this->createMock(Cart::class);
        $mockCart->method('getOrderTotal')->willReturn(150);

        $result = $this->paymentOptionService->excludePaymentOptionByPaymentOptionSettings($mockPaymentOption, $mockCart);

        $this->assertTrue($result);
    }

    public function includePaymentOptionWhenAllConditionsAreMet()
    {
        $mockPaymentOption = $this->createMock(BasePaymentOption::class);
        $mockPaymentOption->method('getGatewaySettings')->willReturn([
            'MULTISAFEPAY_OFFICIAL_GATEWAY_TEST' => ['value' => true],
            'MULTISAFEPAY_OFFICIAL_MIN_AMOUNT_TEST' => ['value' => 50],
            'MULTISAFEPAY_OFFICIAL_MAX_AMOUNT_TEST' => ['value' => 100],
        ]);
        $mockPaymentOption->method('getUniqueName')->willReturn('TEST');

        $mockCart = $this->createMock(Cart::class);
        $mockCart->method('getOrderTotal')->willReturn(75);

        $result = $this->paymentOptionService->excludePaymentOptionByPaymentOptionSettings($mockPaymentOption, $mockCart);

        $this->assertFalse($result);
    }

    /**
     * Helper method to get the test payment methods data
     */
    private function getTestPaymentMethodsJson()
    {
        return '{"data":[{"additional_data":{},"allowed_amount":{"max":5000000,"min":0},"allowed_countries":[],"allowed_currencies":["EUR"],"apps":{"fastcheckout":{"is_enabled":true,"qr":{"supported":false}},"payment_components":{"has_fields":false,"is_enabled":true,"qr":{"supported":false}}},"brands":[],"description":null,"icon_urls":{"large":"https://testmedia.multisafepay.com/img/methods/3x/ideal.png","medium":"https://testmedia.multisafepay.com/img/methods/2x/ideal.png","vector":"https://testmedia.multisafepay.com/img/methods/svg/ideal.svg"},"id":"IDEAL","label":null,"name":"iDEAL","preferred_countries":[],"required_customer_data":[],"shopping_cart_required":false,"tokenization":{"is_enabled":true,"models":{"cardonfile":true,"subscription":true,"unscheduled":true}},"type":"payment-method"}],"success":true}';
    }

    /**
     * @param $jsonString
     * @return array
     * @throws InvalidDataInitializationException
     */
    private function convertJsonToPaymentMethods($jsonString)
    {
        $data = json_decode($jsonString, true);
        $paymentMethods = [];

        foreach ($data['data'] as $paymentMethodData) {
            $paymentMethod = new PaymentMethod($paymentMethodData);
            $paymentMethods[] = $paymentMethod;
        }

        return $paymentMethods;
    }

    /**
     * Mock a static method in a class
     *
     * @param $class
     * @param $method
     * @param $return
     * @return void
     */
    private function mockStaticMethod($class, $method, $return)
    {
        // Create a mock builder for the class
        $mockBuilder = $this->getMockBuilder($class);

        // Check if we're dealing with a callback or simple return value
        if (is_callable($return)) {
            $mock = $mockBuilder->disableOriginalConstructor()
                ->getMock();
            $mock->method($method)->willReturnCallback($return);
        } else {
            $mock = $mockBuilder->disableOriginalConstructor()
                ->getMock();
            $mock->method($method)->willReturn($return);
        }
    }
}
