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

namespace MultiSafepay\Tests\Builder;

use Context;
use MultiSafepay\PrestaShop\Adapter\ContextAdapter;
use MultiSafepay\PrestaShop\Builder\SettingsBuilder;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionException;

class SettingsBuilderTest extends BaseMultiSafepayTest
{
    /**
     * @var MockObject
     */
    private $mockModule;

    public function setUp(): void
    {
        parent::setUp();

        /** @var MultisafepayOfficial $mockModule */
        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $mockModule->method('l')->willReturnArgument(0);
        $mockModule->method('getModuleContext')->willReturn(Context::getContext());
        $this->mockModule = $mockModule;
    }

    /**
     * Test that class constants are properly defined
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder
     */
    public function testConstants(): void
    {
        $this->assertEquals('seconds', SettingsBuilder::SECONDS);
        $this->assertEquals('hours', SettingsBuilder::HOURS);
        $this->assertEquals('days', SettingsBuilder::DAYS);
        $this->assertEquals('SettingsBuilder', SettingsBuilder::CLASS_NAME);
        $this->assertStringContainsString('github.com', SettingsBuilder::MULTISAFEPAY_RELEASES_GITHUB_URL);
    }

    /**
     * Test getConfigFieldsAndDefaultValues method
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testGetConfigFieldsAndDefaultValues(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        $this->assertIsArray($configFields);
        $this->assertNotEmpty($configFields);

        // Test some specific expected keys
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_TEST_MODE', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_API_KEY', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_TEST_API_KEY', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_DEBUG_MODE', $configFields);
        $this->assertArrayHasKey('MULTISAFEPAY_OFFICIAL_DEFAULT_PAYMENT_METHOD', $configFields);

        // Test default values for some fields
        $this->assertEquals('0', $configFields['MULTISAFEPAY_OFFICIAL_TEST_MODE']['default']);
        $this->assertEquals('', $configFields['MULTISAFEPAY_OFFICIAL_API_KEY']['default']);
        $this->assertEquals('30', $configFields['MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_VALUE']['default']);
        $this->assertEquals(SettingsBuilder::DAYS, $configFields['MULTISAFEPAY_OFFICIAL_TIME_ACTIVE_UNIT']['default']);
        $this->assertEquals('0', $configFields['MULTISAFEPAY_OFFICIAL_DEBUG_MODE']['default']);
        $this->assertEquals('1', $configFields['MULTISAFEPAY_OFFICIAL_SECOND_CHANCE']['default']);
        $this->assertEquals('', $configFields['MULTISAFEPAY_OFFICIAL_DEFAULT_PAYMENT_METHOD']['default']);
    }

    /**
     * Test that each config field has the required structure
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testConfigFieldsStructure(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        foreach ($configFields as $fieldName => $fieldConfig) {
            $this->assertIsArray($fieldConfig, "Field '$fieldName' should be an array");
            $this->assertArrayHasKey('default', $fieldConfig, "Field '$fieldName' should have a 'default' key");
            $this->assertIsString($fieldName, "Field name should be a string");

            // Check that field names start with the expected prefix
            $this->assertStringStartsWith('MULTISAFEPAY_OFFICIAL_', $fieldName);
        }
    }

    /**
     * Test that the constructor works correctly
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::__construct
     */
    public function testConstructor(): void
    {
        $settingsBuilder = new SettingsBuilder($this->mockModule);
        $this->assertInstanceOf(SettingsBuilder::class, $settingsBuilder);
    }

    /**
     * Test specific config field values
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testSpecificConfigValues(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        // Test order description default
        $this->assertEquals(
            'Payment for order: {order_reference}',
            $configFields['MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION']['default']
        );

        // Test confirmation email default
        $this->assertEquals(
            '1',
            $configFields['MULTISAFEPAY_OFFICIAL_CONFIRMATION_ORDER_EMAIL']['default']
        );

        // Test create order before payment default
        $this->assertEquals(
            '1',
            $configFields['MULTISAFEPAY_OFFICIAL_CREATE_ORDER_BEFORE_PAYMENT']['default']
        );

        // Test disable shopping cart default
        $this->assertEquals(
            '0',
            $configFields['MULTISAFEPAY_OFFICIAL_DISABLE_SHOPPING_CART']['default']
        );
    }

    /**
     * Test that fields marked as multiple have the correct structure
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testMultipleFieldsStructure(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        // Check fields that should have 'multiple' => true
        $multipleFields = array_filter($configFields, function ($field) {
            return isset($field['multiple']) && $field['multiple'] === true;
        });

        $this->assertNotEmpty($multipleFields);

        // Test that final order status is marked as multiple
        $this->assertTrue(
            isset($configFields['MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS']['multiple'])
        );
        $this->assertTrue(
            $configFields['MULTISAFEPAY_OFFICIAL_FINAL_ORDER_STATUS']['multiple']
        );
    }

    /**
     * Test that all required configuration fields are present
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::getConfigFieldsAndDefaultValues
     */
    public function testRequiredFieldsPresent(): void
    {
        $configFields = SettingsBuilder::getConfigFieldsAndDefaultValues();

        $requiredFields = [
            'MULTISAFEPAY_OFFICIAL_TEST_MODE',
            'MULTISAFEPAY_OFFICIAL_API_KEY',
            'MULTISAFEPAY_OFFICIAL_TEST_API_KEY',
            'MULTISAFEPAY_OFFICIAL_DEBUG_MODE',
            'MULTISAFEPAY_OFFICIAL_ORDER_DESCRIPTION'
        ];

        foreach ($requiredFields as $requiredField) {
            $this->assertArrayHasKey($requiredField, $configFields);
        }
    }

    /**
     * Test the default payment method helper text
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder
     * @throws ReflectionException
     */
    public function testDefaultPaymentMethodHelperText(): void
    {
        /** @var SettingsBuilder&MockObject $settingsBuilder */
        $settingsBuilder = $this->getMockBuilder(SettingsBuilder::class)
            ->setConstructorArgs([$this->mockModule])
            ->onlyMethods([
                'getPaymentMethodsHtmlContent',
                'getSystemStatusHtmlContent',
                'getSupportHtmlContent',
            ])
            ->getMock();

        $settingsBuilder->method('getPaymentMethodsHtmlContent')->willReturn('');
        $settingsBuilder->method('getSystemStatusHtmlContent')->willReturn('');
        $settingsBuilder->method('getSupportHtmlContent')->willReturn('');

        $reflection = new ReflectionClass($settingsBuilder);
        $method = $reflection->getMethod('getConfigForm');
        $method->setAccessible(true);

        $configForm = $method->invoke($settingsBuilder);
        $defaultPaymentMethodField = null;

        foreach ($configForm[0]['form']['input'] as $field) {
            if (($field['name'] ?? '') === 'MULTISAFEPAY_OFFICIAL_DEFAULT_PAYMENT_METHOD') {
                $defaultPaymentMethodField = $field;
                break;
            }
        }

        $this->assertNotNull($defaultPaymentMethodField);

        $this->assertSame(
            'Choose which "active" payment method is selected by default at checkout. If the customer has previously paid with another one, their last used method is selected instead.',
            $defaultPaymentMethodField['desc']
        );
    }

    /**
     * Test that the payment option service is reused within the same builder lifecycle
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder
     * @throws ReflectionException
     */
    public function testPaymentOptionServiceIsReusedWithinBuilderLifecycle(): void
    {
        $settingsBuilder = new SettingsBuilder($this->mockModule);
        $reflection = new ReflectionClass(SettingsBuilder::class);
        $method = $reflection->getMethod('getPaymentOptionService');
        $method->setAccessible(true);

        $firstPaymentOptionService = $method->invoke($settingsBuilder);
        $secondPaymentOptionService = $method->invoke($settingsBuilder);

        $this->assertInstanceOf(PaymentOptionService::class, $firstPaymentOptionService);
        $this->assertSame($firstPaymentOptionService, $secondPaymentOptionService);
    }

    /**
     * Test that saving settings clears the cached payment option service before and after processing
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder::postProcess
     * @throws ReflectionException
     */
    public function testPostProcessClearsCachedPaymentOptionServiceBeforeAndAfterProcessing(): void
    {
        /** @var SettingsBuilder&MockObject $settingsBuilder */
        $settingsBuilder = $this->getMockBuilder(SettingsBuilder::class)
            ->setConstructorArgs([$this->mockModule])
            ->onlyMethods(['getConfigFormValues'])
            ->getMock();

        $cachedPaymentOptionService = $this->createMock(PaymentOptionService::class);
        $reflection = new ReflectionClass(SettingsBuilder::class);
        $property = $reflection->getProperty('paymentOptionService');
        $property->setAccessible(true);
        $property->setValue($settingsBuilder, $cachedPaymentOptionService);

        $settingsBuilder->method('getConfigFormValues')->willReturnCallback(function () use ($property, $settingsBuilder): array {
            $this->assertNull($property->getValue($settingsBuilder));

            return [];
        });

        $result = $settingsBuilder->postProcess();
        $method = $reflection->getMethod('getPaymentOptionService');
        $method->setAccessible(true);
        $recreatedPaymentOptionService = $method->invoke($settingsBuilder);

        $this->assertSame(['success' => true], $result);
        $this->assertNotSame($cachedPaymentOptionService, $recreatedPaymentOptionService);
    }

    /**
     * Test that default payment method options are built from active methods only
     *
     * @covers \MultiSafepay\PrestaShop\Builder\SettingsBuilder
     * @throws ReflectionException
     */
    public function testDefaultPaymentMethodOptionsUseActiveMethodsOnly(): void
    {
        $expectedLanguageId = ContextAdapter::getLanguageId($this->mockModule->getModuleContext()) ?: null;

        $firstActivePaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUniqueName', 'getFrontEndName'])
            ->getMock();
        $firstActivePaymentOption->method('getUniqueName')->willReturn('AMAZONBTN');
        $firstActivePaymentOption->method('getFrontEndName')->with($expectedLanguageId)->willReturn('Amazon Pay');

        $secondActivePaymentOption = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUniqueName', 'getFrontEndName'])
            ->getMock();
        $secondActivePaymentOption->method('getUniqueName')->willReturn('VISA');
        $secondActivePaymentOption->method('getFrontEndName')->with($expectedLanguageId)->willReturn('Visa');

        $paymentOptionService = $this->createMock(PaymentOptionService::class);
        $paymentOptionService->expects($this->once())
            ->method('getActivePaymentOptions')
            ->willReturn([$firstActivePaymentOption, $secondActivePaymentOption]);
        $paymentOptionService->expects($this->never())
            ->method('getMultiSafepayPaymentOptions');

        $settingsBuilder = new SettingsBuilder($this->mockModule);
        $reflection = new ReflectionClass(SettingsBuilder::class);
        $property = $reflection->getProperty('paymentOptionService');
        $property->setAccessible(true);
        $property->setValue($settingsBuilder, $paymentOptionService);

        $method = $reflection->getMethod('getDefaultPaymentMethodOptions');
        $method->setAccessible(true);
        $options = $method->invoke($settingsBuilder);

        $this->assertSame(
            [
                'query' => [
                    ['id' => '', 'name' => 'None'],
                    ['id' => 'AMAZONBTN', 'name' => 'Amazon Pay'],
                    ['id' => 'VISA', 'name' => 'Visa'],
                ],
                'id' => 'id',
                'name' => 'name',
            ],
            $options
        );
    }
}
