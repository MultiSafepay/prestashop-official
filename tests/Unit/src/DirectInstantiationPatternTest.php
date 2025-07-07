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

use PHPUnit\Framework\TestCase;

/**
 * This test ensures that the old Symfony dependency injection pattern is not re-introduced,
 * and that all services are instantiated directly via `new Service()`.
 */
class DirectInstantiationPatternTest extends TestCase
{
    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        // Go up 3 levels from tests/Unit/src to the module root
        $this->modulePath = dirname(__DIR__, 3);
        
        // Skip tests if required classes don't exist
        if (!class_exists(\MultisafepayOfficial::class)) {
            $this->markTestSkipped('MultisafepayOfficial class not available');
        }
    }

    public function testModuleUsesDirectInstantiation()
    {
        $reflection = new ReflectionClass(\MultisafepayOfficial::class);
        $source = file_get_contents($reflection->getFileName());

        self::assertStringContainsString('new SdkService()', $source, 'Module should use direct SdkService instantiation');
        self::assertStringContainsString('new PaymentOptionService(', $source, 'Module should use direct PaymentOptionService instantiation');
    }

    public function testControllersUseDirectInstantiation()
    {
        $controllersDir = $this->modulePath . '/controllers';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($controllersDir));
        $phpFiles = new \RegexIterator($iterator, '/\.php$/');

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file->getPathname());

            if (basename($file->getPathname()) === 'callback.php') {
                continue;
            }

            // We only test files that actually use our services
            if (strpos($content, 'MultiSafepay\\PrestaShop\\Services') === false) {
                continue;
            }

            $hasDirectInstantiation = (
                strpos($content, 'new SdkService(') !== false ||
                strpos($content, 'new OrderService(') !== false ||
                strpos($content, 'new PaymentOptionService(') !== false ||
                strpos($content, 'new TokenizationService(') !== false
            );

            self::assertTrue(
                $hasDirectInstantiation,
                basename($file->getPathname()) . ' should use direct service instantiation'
            );
        }
    }

    public function testSymfonyPatternRemoval()
    {
        $moduleFile = $this->modulePath . '/multisafepayofficial.php';
        $content = file_get_contents($moduleFile);

        // Verify ServiceNotFoundException is NOT used anymore
        self::assertStringNotContainsString('ServiceNotFoundException', $content, 'Module should not use Symfony ServiceNotFoundException');

        // Verify get_class calls on $this are not used (old DI pattern)
        self::assertStringNotContainsString('get_class($this)', $content, 'Module should not use get_class($this) for services');
    }
}
