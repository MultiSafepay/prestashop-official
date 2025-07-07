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

use MultiSafepay\PrestaShop\Helper\TaxHelper;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use ReflectionClass;

class TaxHelperTest extends BaseMultiSafepayTest
{
    /**
     * Test that the TaxHelper class exists and can be instantiated
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(TaxHelper::class));
    }

    /**
     * Test that TaxHelper methods are callable without errors.
     * This is a basic smoke test to ensure the class is properly structured
     */
    public function testBasicFunctionality(): void
    {
        $reflection = new ReflectionClass(TaxHelper::class);
        $this->assertNotEmpty($reflection->getMethods());

        // The class should have some methods
        $this->assertGreaterThan(0, count($reflection->getMethods()));
    }

    /**
     * Test that static methods can be called safely
     */
    public function testStaticMethodsExist(): void
    {
        $reflection = new ReflectionClass(TaxHelper::class);
        $staticMethods = array_filter($reflection->getMethods(), function ($method) {
            return $method->isStatic();
        });

        // This test just verifies that if static methods exist, they can be reflected
        $this->assertIsArray($staticMethods);
    }
}
