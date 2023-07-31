<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
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

namespace MultiSafepay\Tests\Services;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\PrestaShop\Util\CustomerUtil;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;

class CustomerUtilTest extends BaseMultiSafepayTest
{
    /**
     * @var CustomerUtil
     */
    protected $customerUtil;

    public function setUp(): void
    {
        parent::setUp();

        $this->customerUtil = $this->container->get('multisafepay.customer_util');
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomer()
    {
        $output = $this->customerUtil->createCustomer(
            new Address(),
            'john.doe@multisafepay.com',
            '0612345678',
            'John',
            'Doe',
            null,
            null,
            'en_GB'
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
        self::assertInstanceOf(EmailAddress::class, $output->getEmailAddress());
        self::assertInstanceOf(PhoneNumber::class, $output->getPhoneNumber());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerAddress()
    {
        $customer = $this->customerUtil->createCustomer(
            (new Address())
                ->addCity('Amsterdam')
                ->addCountry(new Country('NL'))
                ->addHouseNumber('39')
                ->addZipCode('1033 SC'),
            'john.doe@multisafepay.com',
            '0612345678',
            'John',
            'Doe',
            null,
            null,
            'en_GB'
        );

        $output = $customer->getAddress();

        self::assertInstanceOf(Country::class, $output->getCountry());
        self::assertEquals('Amsterdam', $output->getCity());
        self::assertEquals('NL', $output->getCountry()->getCode());
        self::assertEquals('39', $output->getHouseNumber());
        self::assertEquals('1033 SC', $output->getZipCode());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerOptionalVariables()
    {
        $output = $this->customerUtil->createCustomer(
            (new Address())
                ->addCountry(new Country('NL')),
            'john.doe@multisafepay.com',
            '0612345678',
            'John',
            'Doe',
            '1.1.1.1',
            'Mozilla/5.0',
            'en_GB',
            'MultiSafepay'
        );

        self::assertEquals('1.1.1.1', $output->getIpAddress()->get());
        self::assertEquals('Mozilla/5.0', $output->getUserAgent());
        self::assertEquals('MultiSafepay', $output->getCompanyName());
    }
}
