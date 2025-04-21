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

use Exception;
use MultiSafepay\PrestaShop\Helper\Installer;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;

class InstallerTest extends BaseMultiSafepayTest
{

    public $installer;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $multisafepay = $this->container->get('multisafepay');
        /** @var MultisafepayOfficial $multisafepay */
        $this->installer = $this->getMockBuilder(Installer::class)
            ->setConstructorArgs([$multisafepay])
            ->onlyMethods(['getMultiSafepayOrderStatuses'])
            ->getMock();

        // Mock of the method getMultiSafepayOrderStatuses()
        $this->installer->method('getMultiSafepayOrderStatuses')
            ->willReturn([
                'initialized' => [
                    'name' => 'Payment Initialized',
                    'send_mail' => false,
                    'color' => '#4169E1',
                    'invoice' => false,
                    'template' => '',
                    'paid' => false,
                    'logable' => true
                ],
                'uncleared' => [
                    'name' => 'Payment Uncleared',
                    'send_mail' => false,
                    'color' => '#FF8C00',
                    'invoice' => false,
                    'template' => '',
                    'paid' => false,
                    'logable' => true
                ]
            ]);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetMultiSafepayOrderStatuses(): void
    {
        $output = $this->installer->getMultiSafepayOrderStatuses();
        self::assertIsArray($output);
        self::assertArrayHasKey('initialized', $output);
        self::assertArrayHasKey('uncleared', $output);
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

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetOrderStatusId(): void
    {
        $output = $this->installer->getMultiSafepayOrderStatuses()['initialized'];
        self::assertIsArray($output);
        self::assertArrayHasKey('name', $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetOrderStatusName(): void
    {
        $output = $this->installer->getMultiSafepayOrderStatuses()['initialized'];
        self::assertIsArray($output);
        self::assertEquals('Payment Initialized', $output['name']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetOrderStatusColor(): void
    {
        $output = $this->installer->getMultiSafepayOrderStatuses()['initialized'];
        self::assertIsArray($output);
        self::assertEquals('#4169E1', $output['color']);
        self::assertStringStartsWith('#', $output['color']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetAllOrderStatuses(): void
    {
        $output = $this->installer->getMultiSafepayOrderStatuses();
        self::assertIsArray($output);

        // Verify all expected keys are present
        $expectedKeys = ['initialized', 'uncleared'];
        foreach ($expectedKeys as $key) {
            self::assertArrayHasKey($key, $output);
        }
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testUnclearedStatusProperties(): void
    {
        $output = $this->installer->getMultiSafepayOrderStatuses()['uncleared'];
        self::assertIsArray($output);
        self::assertEquals('Payment Uncleared', $output['name']);
        self::assertFalse($output['send_mail']);
        self::assertEquals('#FF8C00', $output['color']);
    }
}
