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
    public function testGetMultiSafepayOrderStatuses(): void
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
