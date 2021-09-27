<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Helper;

use MultiSafepay\PrestaShop\Helper\Installer;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class InstallerTest extends BaseMultiSafepayTest
{

    public $installer;

    public function setUp(): void
    {
        parent::setUp();
        $multisafepay = $this->container->get('multisafepay');
        $this->installer = new Installer($multisafepay);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetMultiSafepayOrderStatuses()
    {
        $output = $this->installer->getMultiSafepayOrderStatuses();
        self::assertIsArray($output);
        self::assertArrayHasKey('initialized', $output);
        self::assertArrayHasKey('uncleared', $output);
        self::assertArrayHasKey('partial_refunded', $output);
        self::assertArrayHasKey('chargeback', $output);
        foreach ($output as $value) {
            self::assertArrayHasKey('name', $value);
            self::assertIsString($value['name']);
            self::assertArrayHasKey('send_mail', $value);
            self::assertIsBool($value['send_mail']);
            self::assertArrayHasKey('color', $value);
            self::assertIsString($value['color']);
            self::assertArrayHasKey('invoice', $value);
            self::assertIsBool($value['invoice']);
            self::assertArrayHasKey('template', $value);
            self::assertIsString($value['template']);
            self::assertArrayHasKey('paid', $value);
            self::assertIsBool($value['paid']);
            self::assertArrayHasKey('logable', $value);
            self::assertIsBool($value['logable']);
        }
    }
}
