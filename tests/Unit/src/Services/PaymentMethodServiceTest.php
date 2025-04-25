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

use MultiSafepay\Api\PaymentMethodManager;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Sdk;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use PHPUnit\Framework\MockObject\MockObject;

class PaymentMethodServiceTest extends BaseMultiSafepayTest
{
    private $paymentMethodManagerMock;

    public function setUp(): void
    {
        parent::setUp();
        $sdkServiceMock = $this->createMock(SdkService::class);
        $sdkMock = $this->createMock(Sdk::class);
        $this->paymentMethodManagerMock = $this->createMock(PaymentMethodManager::class);

        $sdkMock->method('getPaymentMethodManager')
            ->willReturn($this->paymentMethodManagerMock);

        $sdkServiceMock->method('getSdk')
            ->willReturn($sdkMock);
    }

    /**
     * @covers \MultiSafepay\Api\PaymentMethodManager::getPaymentMethods
     */
    public function testGetPaymentMethods(): void
    {
        $paymentMethodsMock = [
            $this->createPaymentMethodMock('IDEAL', 'iDEAL'),
            $this->createPaymentMethodMock('CREDITCARD', 'Credit Card'),
            $this->createPaymentMethodMock('PAYPAL', 'PayPal'),
        ];

        $this->paymentMethodManagerMock->method('getPaymentMethods')
            ->willReturn($paymentMethodsMock);

        $result = $paymentMethodsMock;

        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertEquals('IDEAL', $result[0]->getId());
        self::assertEquals('Credit Card', $result[1]->getName());
    }

    private function createPaymentMethodMock(
        string $id,
        string $name
    ): MockObject {
        $mock = $this->createMock(PaymentMethod::class);
        $mock->method('getId')->willReturn($id);
        $mock->method('getName')->willReturn($name);
        return $mock;
    }

    /**
     * @covers \MultiSafepay\Api\PaymentMethodManager::getPaymentMethods
     * @dataProvider paymentMethodProvider
     */
    public function testGetPaymentMethodById(string $methodId, bool $shouldExist): void
    {
        $paymentMethodsMock = [
            $this->createPaymentMethodMock('IDEAL', 'iDEAL'),
            $this->createPaymentMethodMock('CREDITCARD', 'Credit Card'),
        ];

        $this->paymentMethodManagerMock->method('getPaymentMethods')
            ->willReturn($paymentMethodsMock);

        $result = null;
        foreach ($paymentMethodsMock as $method) {
            if ($method->getId() === $methodId) {
                $result = $method;
                break;
            }
        }

        if ($shouldExist) {
            self::assertInstanceOf(PaymentMethod::class, $result);
            self::assertEquals($methodId, $result->getId());
        } else {
            self::assertNull($result);
        }
    }

    public function paymentMethodProvider(): array
    {
        return [
            'existing method' => ['IDEAL', true],
            'non-existing method' => ['NONEXISTENT', false],
        ];
    }
}
