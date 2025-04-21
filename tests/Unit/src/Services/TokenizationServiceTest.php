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

use Exception;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Services\TokenizationService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;
use Psr\Http\Client\ClientExceptionInterface;
use ReflectionClass;
use ReflectionException;

class TokenizationServiceTest extends BaseMultiSafepayTest
{
    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::createTokenizationSavePaymentDetailsCheckbox
     * @throws Exception
     */
    public function testSaveTokenField(): void
    {
        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();
        $mockMultisafepay->method('l')->willReturn(
            ''
        );

        $mockTokenizationService = $this->getMockBuilder(TokenizationService::class)->setConstructorArgs(
            [$mockMultisafepay, $this->container->get('multisafepay.sdk_service')]
        )->onlyMethods([])->getMock();

        $output = $mockTokenizationService->createTokenizationSavePaymentDetailsCheckbox();

        self::assertCount(1, $output);
        self::assertCount(3, $output[0]);
        self::assertEquals('checkbox', $output[0]['type']);
        self::assertEquals('saveToken', $output[0]['name']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::formatExpiryDate
     * @throws Exception
     * @throws ReflectionException
     */
    public function testFormatExpiryDate(): void
    {
        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();
        $mockMultisafepay->method('l')->willReturn('');

        $tokenizationService = new TokenizationService(
            $mockMultisafepay,
            $this->container->get('multisafepay.sdk_service')
        );

        // Using reflection to access the private method
        $reflection = new ReflectionClass(get_class($tokenizationService));
        $method = $reflection->getMethod('formatExpiryDate');
        $method->setAccessible(true);

        // Verify different formats
        self::assertEquals('12/25', $method->invokeArgs($tokenizationService, ['2512']));
        self::assertEquals('--', $method->invokeArgs($tokenizationService, [null]));
        self::assertEquals('--', $method->invokeArgs($tokenizationService, ['123']));
        self::assertEquals('--', $method->invokeArgs($tokenizationService, ['12345']));
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::deleteToken
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function testDeleteTokenSuccess(): void
    {
        $mockSdkService = $this->getMockBuilder('MultiSafepay\PrestaShop\Services\SdkService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockTokenManager = $this->getMockBuilder('MultiSafepay\Api\TokenManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockSdk = $this->getMockBuilder('MultiSafepay\Sdk')
            ->disableOriginalConstructor()
            ->getMock();

        // Configure expected behavior
        $mockSdkService->expects($this->once())
            ->method('getSdk')
            ->willReturn($mockSdk);

        $mockSdk->expects($this->once())
            ->method('getTokenManager')
            ->willReturn($mockTokenManager);

        $mockTokenManager->expects($this->once())
            ->method('delete')
            ->with('token123', 'customer456')
            ->willReturn(true);

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();

        $tokenizationService = new TokenizationService($mockMultisafepay, $mockSdkService);
        $result = $tokenizationService->deleteToken('customer456', 'token123');

        self::assertTrue($result);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::deleteToken
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function testDeleteTokenFail(): void
    {
        $mockSdkService = $this->getMockBuilder('MultiSafepay\PrestaShop\Services\SdkService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockTokenManager = $this->getMockBuilder('MultiSafepay\Api\TokenManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockSdk = $this->getMockBuilder('MultiSafepay\Sdk')
            ->disableOriginalConstructor()
            ->getMock();

        // Configure expected behavior
        $mockSdkService->expects($this->once())
            ->method('getSdk')
            ->willReturn($mockSdk);

        $mockSdk->expects($this->once())
            ->method('getTokenManager')
            ->willReturn($mockTokenManager);

        // Simulate failure with exception
        $mockTokenManager->expects($this->once())
            ->method('delete')
            ->with('token123', 'customer456')
            ->will($this->throwException(new ApiException('API Error')));

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();

        $tokenizationService = new TokenizationService($mockMultisafepay, $mockSdkService);
        $result = $tokenizationService->deleteToken('customer456', 'token123');

        self::assertFalse($result);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::getTokensByCustomerIdAndGatewayCode
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function testGetTokensByCustomerIdAndGatewayCodeSuccess(): void
    {
        $mockSdkService = $this->getMockBuilder('MultiSafepay\PrestaShop\Services\SdkService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockTokenManager = $this->getMockBuilder('MultiSafepay\Api\TokenManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockSdk = $this->getMockBuilder('MultiSafepay\Sdk')
            ->disableOriginalConstructor()
            ->getMock();

        $mockToken = $this->getMockBuilder('MultiSafepay\Api\Tokens\Token')
            ->disableOriginalConstructor()
            ->getMock();

        // Configure expected behavior
        $mockSdkService->expects($this->once())
            ->method('getSdk')
            ->willReturn($mockSdk);

        $mockSdk->expects($this->once())
            ->method('getTokenManager')
            ->willReturn($mockTokenManager);

        $mockTokenManager->expects($this->once())
            ->method('getListByGatewayCode')
            ->with('customer123', 'VISA')
            ->willReturn([$mockToken]);

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();

        $tokenizationService = new TokenizationService($mockMultisafepay, $mockSdkService);
        $result = $tokenizationService->getTokensByCustomerIdAndGatewayCode('customer123', 'VISA');

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertInstanceOf('MultiSafepay\Api\Tokens\Token', $result[0]);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::getTokensByCustomerIdAndGatewayCode
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function testGetTokensByCustomerIdAndGatewayCodeEmpty(): void
    {
        $mockSdkService = $this->getMockBuilder('MultiSafepay\PrestaShop\Services\SdkService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockTokenManager = $this->getMockBuilder('MultiSafepay\Api\TokenManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockSdk = $this->getMockBuilder('MultiSafepay\Sdk')
            ->disableOriginalConstructor()
            ->getMock();

        // Configure expected behavior
        $mockSdkService->expects($this->once())
            ->method('getSdk')
            ->willReturn($mockSdk);

        $mockSdk->expects($this->once())
            ->method('getTokenManager')
            ->willReturn($mockTokenManager);

        // Simulate no tokens found with an exception
        $mockTokenManager->expects($this->once())
            ->method('getListByGatewayCode')
            ->with('customer123', 'VISA')
            ->will($this->throwException(new ApiException('No tokens found')));

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();

        $tokenizationService = new TokenizationService($mockMultisafepay, $mockSdkService);
        $result = $tokenizationService->getTokensByCustomerIdAndGatewayCode('customer123', 'VISA');

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::getTokensByCustomerId
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function testGetTokensByCustomerIdSuccess(): void
    {
        $mockSdkService = $this->getMockBuilder('MultiSafepay\PrestaShop\Services\SdkService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockTokenManager = $this->getMockBuilder('MultiSafepay\Api\TokenManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockSdk = $this->getMockBuilder('MultiSafepay\Sdk')
            ->disableOriginalConstructor()
            ->getMock();

        $mockToken = $this->getMockBuilder('MultiSafepay\Api\Tokens\Token')
            ->disableOriginalConstructor()
            ->getMock();

        // Configure expected behavior
        $mockSdkService->expects($this->once())
            ->method('getSdk')
            ->willReturn($mockSdk);

        $mockSdk->expects($this->once())
            ->method('getTokenManager')
            ->willReturn($mockTokenManager);

        $mockTokenManager->expects($this->once())
            ->method('getList')
            ->with('customer123')
            ->willReturn([$mockToken]);

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();

        $tokenizationService = new TokenizationService($mockMultisafepay, $mockSdkService);
        $result = $tokenizationService->getTokensByCustomerId('customer123');

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertInstanceOf('MultiSafepay\Api\Tokens\Token', $result[0]);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::getTokensByCustomerId
     * @throws Exception
     * @throws ClientExceptionInterface
     */
    public function testGetTokensByCustomerIdEmpty(): void
    {
        $mockSdkService = $this->getMockBuilder('MultiSafepay\PrestaShop\Services\SdkService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockTokenManager = $this->getMockBuilder('MultiSafepay\Api\TokenManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockSdk = $this->getMockBuilder('MultiSafepay\Sdk')
            ->disableOriginalConstructor()
            ->getMock();

        // Configure expected behavior
        $mockSdkService->expects($this->once())
            ->method('getSdk')
            ->willReturn($mockSdk);

        $mockSdk->expects($this->once())
            ->method('getTokenManager')
            ->willReturn($mockTokenManager);

        // Simulate no tokens found with an exception
        $mockTokenManager->expects($this->once())
            ->method('getList')
            ->with('customer123')
            ->will($this->throwException(new ApiException('No tokens found')));

        $mockMultisafepay = $this->getMockBuilder(MultisafepayOfficial::class)->getMock();

        $tokenizationService = new TokenizationService($mockMultisafepay, $mockSdkService);
        $result = $tokenizationService->getTokensByCustomerId('customer123');

        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}
