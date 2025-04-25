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

namespace MultiSafepay\Tests\Util;

use Address as PrestaShopAddress;
use Exception;
use MultiSafepay\PrestaShop\Util\AddressUtil;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use ReflectionClass;

class AddressUtilTest extends BaseMultiSafepayTest
{
    /**
     * @var AddressUtil
     */
    protected $addressUtil;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->addressUtil = new AddressUtil();
    }

    /**
    * @covers \MultiSafepay\PrestaShop\Util\AddressUtil::getAddress
    */
    public function testGetAddress(): void
    {
        // Use a real address ID or create a test address in setUp
        $testAddressId = 1; // Use an existing ID or create a test address

        // Call the actual method directly without mocking
        $result = $this->addressUtil->getAddress($testAddressId);

        // Verify the result
        $this->assertInstanceOf(PrestaShopAddress::class, $result);
        $this->assertEquals($testAddressId, $result->id);
    }
}
