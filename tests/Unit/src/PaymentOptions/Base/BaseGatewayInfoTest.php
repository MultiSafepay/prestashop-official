<?php declare(strict_types=1);

namespace MultiSafepay\Tests\PaymentOptions\Base;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BaseGatewayInfo;
use PHPUnit\Framework\TestCase;

class BaseGatewayInfoTest extends TestCase
{

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\Base\BaseGatewayInfo::getData
     */
    public function testGetData()
    {
        self::isEmpty((new BaseGatewayInfo())->getData());
        self::assertIsArray((new BaseGatewayInfo())->getData());
    }
}
