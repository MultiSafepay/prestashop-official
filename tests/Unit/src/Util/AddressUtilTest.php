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

class AddressUtilTest extends BaseMultiSafepayTest
{
    /**
     * @var AddressUtil
     */
    protected $addressUtil;

    /**
     * @var PrestaShopAddress
     */
    protected $testAddress;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->addressUtil = new AddressUtil();

        // Create a real Address object with test data
        $this->testAddress = new PrestaShopAddress();
        $this->testAddress->id_customer = 1;
        $this->testAddress->id_country = 1;
        $this->testAddress->firstname = 'John';
        $this->testAddress->lastname = 'Doe';
        $this->testAddress->address1 = 'Kraanspoor 39';
        $this->testAddress->address2 = '';
        $this->testAddress->postcode = '1033 SC';
        $this->testAddress->city = 'Amsterdam';
        $this->testAddress->phone = '0612345678';
        $this->testAddress->company = 'MultiSafepay';
        $this->testAddress->alias = 'Test Address'; // Required field
        // Save to get a real ID
        $this->testAddress->add();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\AddressUtil::getAddress
     */
    public function testGetAddress(): void
    {
        // Use the test address ID we created
        $testAddressId = (int)$this->testAddress->id;

        // Call the actual method
        $result = $this->addressUtil->getAddress($testAddressId);

        // Verify the result
        $this->assertInstanceOf(PrestaShopAddress::class, $result);
        $this->assertEquals($testAddressId, (int)$result->id);
        $this->assertEquals('John', $result->firstname);
        $this->assertEquals('Doe', $result->lastname);
        $this->assertEquals('Amsterdam', $result->city);
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void
    {
        // Clean up the test address
        if (isset($this->testAddress) && $this->testAddress->id) {
            $this->testAddress->delete();
        }
        parent::tearDown();
    }
}
