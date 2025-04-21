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

use Exception;
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

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->customerUtil = $this->container->get('multisafepay.customer_util');
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomer(): void
    {
        $output = $this->customerUtil->createCustomer(
            new Address(),
            'john.doe@multisafepay.com',
            '0612345678',
            'John',
            'Doe',
            null,
            null,
            'nl_NL'
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
        self::assertInstanceOf(EmailAddress::class, $output->getEmailAddress());
        self::assertInstanceOf(PhoneNumber::class, $output->getPhoneNumber());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerAddress(): void
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
            'nl_NL'
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
    public function testCreateCustomerOptionalVariables(): void
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
            'nl_NL',
            'MultiSafepay'
        );

        self::assertEquals('1.1.1.1', $output->getIpAddress()->get());
        self::assertEquals('Mozilla/5.0', $output->getUserAgent());
        self::assertEquals('MultiSafepay', $output->getCompanyName());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerWithMinimalData(): void
    {
        $output = $this->customerUtil->createCustomer(
            new Address(),
            'test@test.com',
            '',  // Empty phone
            'Test',
            'User',
            null,
            null,
            'nl_NL'
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
        self::assertEquals('test@test.com', $output->getEmailAddress()->get());
        self::assertEquals('Test', $output->getFirstName());
        self::assertEquals('User', $output->getLastName());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerWithSpecialCharacters(): void
    {
        $output = $this->customerUtil->createCustomer(
            (new Address())
                ->addCity('Tübingen')
                ->addCountry(new Country('NL'))
                ->addHouseNumber('123-A')
                ->addZipCode('3511 AA'),
            'test.special@example.com',
            '+31 30 123 4567',
            'Jörg',
            'van Güldenpfennig',
            null,
            null,
            'nl_NL'
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
        self::assertEquals('Tübingen', $output->getAddress()->getCity());
        self::assertEquals('NL', $output->getAddress()->getCountry()->getCode());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerWithLongValues(): void
    {
        $longString = str_repeat('a', 100);

        $output = $this->customerUtil->createCustomer(
            (new Address())
                ->addCity($longString)
                ->addCountry(new Country('NL'))
                ->addHouseNumber($longString)
                ->addZipCode($longString),
            'test@test.com',
            '1234567890',
            $longString,
            $longString,
            null,
            null,
            'nl_NL'
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerWithReference(): void
    {
        $output = $this->customerUtil->createCustomer(
            new Address(),
            'test@test.com',
            '1234567890',
            'Test',
            'User',
            '192.168.1.1',
            'Mozilla/5.0',
            'nl_NL',
            'Company Name',
            'customer_reference_123'
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
        self::assertEquals('test@test.com', $output->getEmailAddress()->get());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerWithBrowserData(): void
    {
        $browserData = [
            'javascript_enabled' => true,
            'screen_width' => 1920,
            'screen_height' => 1080,
            'color_depth' => 24,
            'timezone' => 'Europe/Amsterdam'
        ];

        $output = $this->customerUtil->createCustomer(
            (new Address())->addCountry(new Country('NL')),
            'test@test.com',
            '1234567890',
            'Test',
            'User',
            '192.168.1.1',
            'Mozilla/5.0',
            'nl_NL',
            null,
            null,
            $browserData
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
        self::assertEquals('test@test.com', $output->getEmailAddress()->get());
        self::assertEquals('Test', $output->getFirstName());
        self::assertEquals('User', $output->getLastName());
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomerWithEmptyOptionalFields(): void
    {
        $output = $this->customerUtil->createCustomer(
            (new Address())->addCountry(new Country('NL')),
            'test@test.com',
            '',
            'Test',
            'User',
            '',
            '',
            'nl_NL',
            '',
            '',
            []
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
        self::assertEquals('test@test.com', $output->getEmailAddress()->get());
        self::assertEquals('Test', $output->getFirstName());
        self::assertEquals('User', $output->getLastName());
        self::assertEquals('', $output->getCompanyName());
    }
}
