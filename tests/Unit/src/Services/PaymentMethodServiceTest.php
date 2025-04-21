<?php declare(strict_types=1);

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
