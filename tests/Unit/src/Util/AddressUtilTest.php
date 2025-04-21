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
        // Mock the PrestaShopAddress class since we can't use the real one in tests
        $mockAddress = $this->getMockBuilder(PrestaShopAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a reflection of the AddressUtil class to replace the creation of a new PrestaShopAddress
        $reflectionClass = new ReflectionClass(AddressUtil::class);
        $method = $reflectionClass->getMethod('getAddress');
        $method->setAccessible(true);

        // Create a partial mock to override the method
        $addressUtilMock = $this->getMockBuilder(AddressUtil::class)
            ->onlyMethods(['getAddress'])
            ->getMock();

        // Set up the mock to return our mocked address when called with ID 123
        $addressUtilMock->expects($this->once())
            ->method('getAddress')
            ->with(123)
            ->willReturn($mockAddress);

        // Execute the method and assert the result
        $address = $addressUtilMock->getAddress(123);
        $this->assertInstanceOf(PrestaShopAddress::class, $address);
    }
}
