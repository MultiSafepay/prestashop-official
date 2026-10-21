<?php declare(strict_types=1);

namespace MultiSafepay\Tests\Helper;

use Cache;
use Configuration;
use Db;
use Hook;
use Module;
use MultiSafepay\PrestaShop\Helper\Installer;
use MultiSafepay\PrestaShop\Helper\ManualCaptureHelper;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;
use OrderState;
use PrestaShopException;
use ReflectionMethod;
use Tab;
use Validate;

class ManualCaptureResourcesTest extends BaseMultiSafepayTest
{
    private const AUTHORIZED = 'MULTISAFEPAY_OFFICIAL_OS_AUTHORIZED';
    private const PARTIAL = 'MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED';

    /** @var MultisafepayOfficial */
    private $module;

    /** @var int */
    private $lastOrderStateId;

    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 4) . '/upgrade/upgrade-6.6.0.php';
        $this->assertTrue(Db::getInstance()->execute('START TRANSACTION'));
        $this->lastOrderStateId = (int)Db::getInstance()->getValue(
            'SELECT MAX(id_order_state) FROM `' . _DB_PREFIX_ . 'order_state`',
            false
        );
        $this->module = new MultisafepayOfficial();
        $this->assertGreaterThan(0, (int)$this->module->id, 'The integration test requires an installed module.');
    }

    protected function tearDown(): void
    {
        try {
            $states = Db::getInstance()->executeS(
                'SELECT id_order_state FROM `' . _DB_PREFIX_ . 'order_state`'
                . ' WHERE id_order_state > ' . $this->lastOrderStateId
                . ' AND module_name = "multisafepayofficial"',
                true,
                false
            );
            foreach ($states as $state) {
                $directory = defined('_PS_ORDER_STATE_IMG_DIR_') ? _PS_ORDER_STATE_IMG_DIR_ : _PS_IMG_DIR_ . 'os/';
                $path = rtrim($directory, '/\\') . '/' . (int)$state['id_order_state'] . '.gif';
                if (is_file($path)) {
                    $this->assertTrue(unlink($path));
                }
            }
        } finally {
            $this->assertTrue(Db::getInstance()->execute('ROLLBACK'));
            Configuration::resetStaticCache();
            Tab::resetStaticCache();
            Module::resetStaticCache();
            OrderState::resetStaticCache();
            Cache::clean('*');
            parent::tearDown();
        }
    }

    /**
     * @dataProvider installedModuleProvider
     */
    public function testConstructorDoesNotRepairMissingResources(bool $installed): void
    {
        $this->clearCaptureConfiguration();
        $this->removeCaptureTab();
        $this->assertTrue(Db::getInstance()->delete(
            'hook_module',
            'id_module = ' . (int)$this->module->id
            . ' AND id_hook = ' . (int)Hook::getIdByName($this->getHookName())
        ));
        if (!$installed) {
            $this->assertTrue(Db::getInstance()->delete('module', 'id_module = ' . (int)$this->module->id));
        }
        Module::resetStaticCache();
        $before = $this->resourceCounts();

        $module = new MultisafepayOfficial();

        $this->assertSame($installed, (bool)$module->id);
        $this->assertSame($before, $this->resourceCounts());
        $this->assertFalse(Configuration::get(self::AUTHORIZED));
        $this->assertFalse(Configuration::get(self::PARTIAL));
        $this->assertFalse(Tab::getIdFromClassName('AdminMultisafepayOfficialCapture'));
    }

    public static function installedModuleProvider(): array
    {
        return ['installed' => [true], 'not installed' => [false]];
    }

    public function testUpgradeCreatesMissingResourcesAndIsIdempotent(): void
    {
        $this->clearCaptureConfiguration();
        $this->removeCaptureTab();
        $this->assertTrue(Db::getInstance()->delete(
            'hook_module',
            'id_module = ' . (int)$this->module->id
            . ' AND id_hook = ' . (int)Hook::getIdByName($this->getHookName())
        ));
        $this->assertTrue(Configuration::updateGlobalValue('MULTISAFEPAY_OFFICIAL_DEFAULT_PAYMENT_METHOD', 'test-value'));

        $this->assertTrue(upgrade_module_6_6_0($this->module));
        $authorizedId = ManualCaptureHelper::getRequiredOrderStateId(self::AUTHORIZED);
        $partialId = ManualCaptureHelper::getRequiredOrderStateId(self::PARTIAL);
        $tabId = (int)Tab::getIdFromClassName('AdminMultisafepayOfficialCapture');
        $this->assertGreaterThan(0, $tabId);
        $tab = new Tab($tabId);
        $this->assertSame('multisafepayofficial', $tab->module);
        $this->assertSame(-1, (int)$tab->id_parent);
        $this->assertTrue($this->module->isRegisteredInHook($this->getHookName()));
        $this->assertSame('#A700D3', (new OrderState($partialId))->color);
        $before = $this->resourceCounts();

        $this->assertTrue(upgrade_module_6_6_0($this->module));

        $this->assertSame($before, $this->resourceCounts());
        $this->assertSame($authorizedId, ManualCaptureHelper::getRequiredOrderStateId(self::AUTHORIZED));
        $this->assertSame($partialId, ManualCaptureHelper::getRequiredOrderStateId(self::PARTIAL));
        $this->assertSame($tabId, (int)Tab::getIdFromClassName('AdminMultisafepayOfficialCapture'));
        $this->assertSame('test-value', Configuration::getGlobalValue('MULTISAFEPAY_OFFICIAL_DEFAULT_PAYMENT_METHOD'));
    }

    public function testFreshInstallationCreatesValidStatesWithoutConstructorPreparation(): void
    {
        $this->clearCaptureConfiguration();
        $installer = new Installer($this->module);
        $method = new ReflectionMethod(Installer::class, 'registerMultiSafepayOrderStatuses');
        $method->setAccessible(true);
        $method->invoke($installer);

        $this->assertGreaterThan(0, ManualCaptureHelper::getRequiredOrderStateId(self::AUTHORIZED));
        $this->assertGreaterThan(0, ManualCaptureHelper::getRequiredOrderStateId(self::PARTIAL));
    }

    public function testUpgradeReplacesStaleStateEvenWhenReadyFlagIsSet(): void
    {
        $this->clearCaptureConfiguration();
        $this->assertTrue(Configuration::updateGlobalValue(self::AUTHORIZED, 2147483647));
        $this->assertTrue(Configuration::updateGlobalValue(self::AUTHORIZED . '_STATUS_CREATED', '1'));

        $this->assertTrue(upgrade_module_6_6_0($this->module));

        $id = ManualCaptureHelper::getRequiredOrderStateId(self::AUTHORIZED);
        $this->assertNotSame(2147483647, $id);
        $this->assertTrue(Validate::isLoadedObject(new OrderState($id)));
    }

    public function testUpgradeReplacesDeletedState(): void
    {
        $this->clearCaptureConfiguration();
        $this->assertTrue(upgrade_module_6_6_0($this->module));
        $oldId = ManualCaptureHelper::getRequiredOrderStateId(self::PARTIAL);
        $state = new OrderState($oldId);
        $state->deleted = true;
        $this->assertTrue((bool)$state->update());

        $this->assertTrue(upgrade_module_6_6_0($this->module));

        $this->assertNotSame($oldId, ManualCaptureHelper::getRequiredOrderStateId(self::PARTIAL));
    }

    public function testUpgradeFailsWhenHookRegistrationFails(): void
    {
        $this->clearCaptureConfiguration();
        $module = $this->createMock(MultisafepayOfficial::class);
        $module->id = $this->module->id;
        $module->name = $this->module->name;
        $module->expects($this->once())->method('registerHook')->with($this->getHookName())->willReturn(false);

        $this->assertFalse(upgrade_module_6_6_0($module));
        $beforeRetry = $this->resourceCounts();
        $this->assertTrue(upgrade_module_6_6_0($this->module));
        $this->assertSame($beforeRetry['order_state'], $this->resourceCounts()['order_state']);
    }

    public function testUpgradeDoesNotAcceptAnotherModulesCaptureTab(): void
    {
        $this->removeCaptureTab();
        $this->assertTrue(upgrade_module_6_6_0($this->module));
        $tab = new Tab((int)Tab::getIdFromClassName('AdminMultisafepayOfficialCapture'));
        $tab->module = 'anothermodule';
        $this->assertTrue((bool)$tab->update());
        Tab::resetStaticCache();

        $this->assertFalse(upgrade_module_6_6_0($this->module));
    }

    public function testRequiredStateRejectsDeletedStateWithoutRepair(): void
    {
        $this->clearCaptureConfiguration();
        $this->assertTrue(upgrade_module_6_6_0($this->module));
        $id = ManualCaptureHelper::getRequiredOrderStateId(self::AUTHORIZED);
        $state = new OrderState($id);
        $state->deleted = true;
        $this->assertTrue((bool)$state->update());

        $this->expectException(PrestaShopException::class);
        $this->expectExceptionMessage('Complete the module upgrade');
        ManualCaptureHelper::getRequiredOrderStateId(self::AUTHORIZED);
    }

    public function testUpgradeRejectsUninstalledModule(): void
    {
        $module = $this->createMock(MultisafepayOfficial::class);
        $module->id = null;
        $module->expects($this->never())->method('registerHook');
        $before = $this->resourceCounts();

        $this->assertFalse(upgrade_module_6_6_0($module));
        $this->assertSame($before, $this->resourceCounts());
    }

    /**
     * @dataProvider invalidStateProvider
     */
    public function testMissingStateRaisesActionableErrorWithoutRepair($value): void
    {
        $this->clearCaptureConfiguration();
        $this->assertTrue(Configuration::updateGlobalValue(self::AUTHORIZED, $value));
        $before = $this->resourceCounts();
        try {
            ManualCaptureHelper::getRequiredOrderStateId(self::AUTHORIZED);
            $this->fail('Missing or invalid states must not be accepted.');
        } catch (PrestaShopException $exception) {
            $this->assertStringContainsString(self::AUTHORIZED, $exception->getMessage());
            $this->assertStringContainsString('Complete the module upgrade', $exception->getMessage());
        }
        $this->assertSame($before, $this->resourceCounts());
        $this->assertSame((string)$value, (string)Configuration::getGlobalValue(self::AUTHORIZED));
    }

    public static function invalidStateProvider(): array
    {
        return ['zero' => [0], 'invalid' => ['invalid'], 'stale ID' => [2147483647]];
    }

    private function clearCaptureConfiguration(): void
    {
        foreach ([self::AUTHORIZED, self::PARTIAL] as $key) {
            $this->assertTrue(Configuration::deleteByName($key));
            $this->assertTrue(Configuration::deleteByName($key . '_STATUS_CREATED'));
        }
    }

    private function removeCaptureTab(): void
    {
        $this->assertTrue(Db::getInstance()->delete('tab', 'class_name = "AdminMultisafepayOfficialCapture"'));
        Tab::resetStaticCache();
    }

    private function getHookName(): string
    {
        return version_compare(_PS_VERSION_, '1.7.7.0', '>=')
            ? 'actionGetAdminOrderButtons'
            : 'displayBackOfficeOrderActions';
    }

    private function resourceCounts(): array
    {
        $counts = [];
        foreach (['order_state', 'configuration', 'tab', 'hook', 'hook_module'] as $table) {
            $counts[$table] = (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . $table . '`',
                false
            );
        }
        return $counts;
    }
}
