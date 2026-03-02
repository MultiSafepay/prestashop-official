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
    /** @var Installer */
    protected $installer;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        /** @var MultisafepayOfficial $mockModule */
        $mockModule = $this->createMock(MultisafepayOfficial::class);
        $this->installer = new Installer($mockModule);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetMultiSafepayOrderStatuses(): void
    {
        $orderStatuses = $this->installer->getMultiSafepayOrderStatuses();
        $this->assertIsArray($orderStatuses);
        $this->assertArrayHasKey('initialized', $orderStatuses);
        $this->assertArrayHasKey('uncleared', $orderStatuses);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetOrderStatusId(): void
    {
        $orderStatuses = $this->installer->getMultiSafepayOrderStatuses();
        $this->assertEquals('initialized', $orderStatuses['initialized']['name']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetOrderStatusName(): void
    {
        $orderStatuses = $this->installer->getMultiSafepayOrderStatuses();
        $this->assertEquals('initialized', $orderStatuses['initialized']['name']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetOrderStatusColor(): void
    {
        $orderStatuses = $this->installer->getMultiSafepayOrderStatuses();
        $this->assertEquals('#4169E1', $orderStatuses['initialized']['color']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testGetAllOrderStatuses(): void
    {
        $orderStatuses = $this->installer->getMultiSafepayOrderStatuses();
        $this->assertCount(6, $orderStatuses);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     */
    public function testUnclearedStatusProperties(): void
    {
        $orderStatuses = $this->installer->getMultiSafepayOrderStatuses();
        $this->assertEquals('uncleared', $orderStatuses['uncleared']['name']);
        $this->assertFalse($orderStatuses['uncleared']['send_mail']);
        $this->assertEquals('#EC2E15', $orderStatuses['uncleared']['color']);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::getMultiSafepayOrderStatuses
     *
     * @return void
     */
    public function testPartialCapturedStatusProperties(): void
    {
        $orderStatuses = $this->installer->getMultiSafepayOrderStatuses();
        $this->assertArrayHasKey('partial_captured', $orderStatuses);
        $this->assertEquals('partially captured', $orderStatuses['partial_captured']['name']);
        $this->assertFalse($orderStatuses['partial_captured']['send_mail']);
        $this->assertEquals('#A700D3', $orderStatuses['partial_captured']['color']);
        $this->assertFalse($orderStatuses['partial_captured']['invoice']);
        $this->assertFalse($orderStatuses['partial_captured']['paid']);
        $this->assertFalse($orderStatuses['partial_captured']['logable']);
    }
}
