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
use ReflectionMethod;
use RuntimeException;

/**
 * Unit tests for installer order-status and legacy icon behavior.
 */
class InstallerTest extends BaseMultiSafepayTest
{
    /** @var Installer */
    protected $installer;

    /** @var string[] */
    private $temporaryIconPaths = [];

    /** @var string[] */
    private $temporaryModuleIconPaths = [];

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
     * Remove temporary icon files created by icon-specific tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->temporaryIconPaths as $iconPath) {
            if (is_file($iconPath)) {
                $this->assertTrue(
                    unlink($iconPath),
                    sprintf('Failed to remove temporary icon file: %s', $iconPath)
                );
            }
        }

        foreach ($this->temporaryModuleIconPaths as $iconPath) {
            if (is_file($iconPath)) {
                $this->assertTrue(
                    unlink($iconPath),
                    sprintf('Failed to remove temporary module icon file: %s', $iconPath)
                );
            }
        }

        parent::tearDown();
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
        $this->assertEquals('partially_captured.gif', $orderStatuses['partial_captured']['icon']);
        $this->assertFalse($orderStatuses['partial_captured']['send_mail']);
        $this->assertEquals('#A700D3', $orderStatuses['partial_captured']['color']);
        $this->assertFalse($orderStatuses['partial_captured']['invoice']);
        $this->assertFalse($orderStatuses['partial_captured']['paid']);
        $this->assertFalse($orderStatuses['partial_captured']['logable']);
    }

    /**
     * Ensure the installer creates a missing legacy icon for a new order-state ID.
     *
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::cloneOrderStateIcon
     * @return void
     */
    public function testCloneOrderStateIconCreatesMissingFile(): void
    {
        $iconsDirectory = $this->getIconsDirectory();
        if (!is_dir($iconsDirectory) || !is_writable($iconsDirectory)) {
            $this->markTestSkipped('Order-state icon directory is not writable in this environment.');
        }

        $moduleIconsDirectory = $this->getModuleOrderStateIconsDirectory();
        if (!is_dir($moduleIconsDirectory) && !@mkdir($moduleIconsDirectory, 0755, true)) {
            $this->markTestSkipped('Module order-state icon directory is not writable in this environment.');
        }

        if (!is_writable($moduleIconsDirectory)) {
            $this->markTestSkipped('Module order-state icon directory is not writable in this environment.');
        }

        $sourceIconFileName = 'tmp_test_icon_' . str_replace('.', '', uniqid('', true)) . '.gif';
        $sourceIconPath = $moduleIconsDirectory . $sourceIconFileName;
        $sourceIconContent = 'installer-module-default-icon';
        $this->assertNotFalse(file_put_contents($sourceIconPath, $sourceIconContent));
        $this->temporaryModuleIconPaths[] = $sourceIconPath;

        $orderStateId = $this->allocateUnusedOrderStateId();
        $targetIconPath = $this->getIconPath($orderStateId);
        $this->temporaryIconPaths[] = $targetIconPath;

        $method = new ReflectionMethod(Installer::class, 'cloneOrderStateIcon');
        $method->setAccessible(true);
        $method->invoke($this->installer, $orderStateId, ['icon' => $sourceIconFileName]);

        $this->assertFileExists($targetIconPath);
        $this->assertSame($sourceIconContent, (string)file_get_contents($targetIconPath));
    }

    /**
     * Ensure the installer does not overwrite an existing legacy icon.
     *
     * @covers \MultiSafepay\PrestaShop\Helper\Installer::cloneOrderStateIcon
     * @return void
     */
    public function testCloneOrderStateIconDoesNotOverwriteExistingFile(): void
    {
        $iconsDirectory = $this->getIconsDirectory();
        if (!is_dir($iconsDirectory) || !is_writable($iconsDirectory)) {
            $this->markTestSkipped('Order-state icon directory is not writable in this environment.');
        }

        $orderStateId = $this->allocateUnusedOrderStateId();
        $targetIconPath = $this->getIconPath($orderStateId);
        $this->temporaryIconPaths[] = $targetIconPath;

        $existingContent = 'existing-icon-content';
        $this->assertNotFalse(file_put_contents($targetIconPath, $existingContent));

        $method = new ReflectionMethod(Installer::class, 'cloneOrderStateIcon');
        $method->setAccessible(true);
        $method->invoke($this->installer, $orderStateId, []);

        $this->assertSame($existingContent, (string)file_get_contents($targetIconPath));
    }

    /**
     * Resolve the legacy order-state icon directory.
     *
     * @return string
     */
    private function getIconsDirectory(): string
    {
        if (defined('_PS_ORDER_STATE_IMG_DIR_')) {
            return rtrim(_PS_ORDER_STATE_IMG_DIR_, '/\\')
                . DIRECTORY_SEPARATOR;
        }

        return rtrim(_PS_IMG_DIR_, '/\\')
            . DIRECTORY_SEPARATOR
            . 'os'
            . DIRECTORY_SEPARATOR;
    }

    /**
     * Resolve the icon path for a given order-state ID.
     *
     * @param int $orderStateId
     * @return string
     */
    private function getIconPath(int $orderStateId): string
    {
        return $this->getIconsDirectory() . $orderStateId . '.gif';
    }

    /**
     * Resolve the module order-state icon source directory.
     *
     * @return string
     */
    private function getModuleOrderStateIconsDirectory(): string
    {
        return rtrim(_PS_MODULE_DIR_, '/\\')
            . DIRECTORY_SEPARATOR
            . 'multisafepayofficial'
            . DIRECTORY_SEPARATOR
            . 'views'
            . DIRECTORY_SEPARATOR
            . 'img'
            . DIRECTORY_SEPARATOR
            . 'order_states'
            . DIRECTORY_SEPARATOR;
    }

    /**
     * Allocate an icon ID that does not exist yet in the icon directory.
     *
     * @return int
     * @throws RuntimeException
     */
    private function allocateUnusedOrderStateId(): int
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $candidateId = random_int(900000, 999999);
            if (!is_file($this->getIconPath($candidateId))) {
                return $candidateId;
            }
        }

        throw new RuntimeException('Could not allocate an unused order-state icon ID for Installer tests.');
    }
}
