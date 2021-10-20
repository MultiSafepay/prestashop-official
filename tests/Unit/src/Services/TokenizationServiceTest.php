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

use Multisafepay;
use MultiSafepay\Api\TokenManager;
use MultiSafepay\Api\Tokens\Token;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay as MultiSafepayPaymentMethod;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Visa;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\PrestaShop\Services\TokenizationService;
use MultiSafepay\Sdk;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class TokenizationServiceTest extends BaseMultiSafepayTest
{
    /**
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::createTokenizationCheckoutFields
     */
    public function testSaveTokenField()
    {
        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
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
     * @covers \MultiSafepay\PrestaShop\Services\TokenizationService::createTokenizationCheckoutFields
     */
    public function testTokenList()
    {
        $mockTokenizationServiceForGateway = $this->getMockBuilder(
            TokenizationService::class
        )->disableOriginalConstructor()->getMock();
        $mockTokenizationServiceForGateway->method('createTokenizationCheckoutFields')->willReturn(
            []
        );

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockMultisafepay->method('get')->willReturn(
            $mockTokenizationServiceForGateway
        );
        $mockMultisafepay->method('l')->willReturn(
            ''
        );

        $mockTokenizationService = $this->getMockBuilder(TokenizationService::class)->setConstructorArgs(
            [$mockMultisafepay, $this->container->get('multisafepay.sdk_service')]
        )->onlyMethods(['getTokensByCustomerIdAndGatewayCode'])->getMock();
        $mockTokenizationService->method('getTokensByCustomerIdAndGatewayCode')->willReturn(
            [
                new Token(
                    [
                        'token'       => '12345VISA',
                        'code'        => 'VISA',
                        'display'     => '12345VISA',
                        'bin'         => '',
                        'name_holder' => '',
                        'expiry_date' => '',
                        'expired'     => '',
                        'last4'       => '',
                        'model'       => '',
                    ]
                ),
            ]
        );


        $output = $mockTokenizationService->createTokenizationCheckoutFields('1', new Visa($mockMultisafepay));

        self::assertCount(1, $output);
        self::assertEquals('radio', $output[0]['type']);
        self::assertCount(2, $output[0]['options'][0]);
        self::assertEquals('12345VISA', $output[0]['options'][0]['name']);
    }
}
