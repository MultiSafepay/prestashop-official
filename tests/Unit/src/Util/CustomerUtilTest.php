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
    /** @var CustomerUtil */
    private $customerUtil;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->customerUtil = new CustomerUtil();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CustomerUtil::createCustomer
     */
    public function testCreateCustomer(): void
    {
        $customerData = $this->createValidCustomerData();
        
        $output = $this->customerUtil->createCustomer(
            new Address(),
            $customerData['email'],
            $customerData['phone'],
            $customerData['first_name'],
            $customerData['last_name'],
            $customerData['ip_address'],
            $customerData['user_agent'],
            $customerData['locale']
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
        $customerData = $this->createValidCustomerData();
        $address = $this->createValidAddress();
        
        $customer = $this->customerUtil->createCustomer(
            $address,
            $customerData['email'],
            $customerData['phone'],
            $customerData['first_name'],
            $customerData['last_name'],
            $customerData['ip_address'],
            $customerData['user_agent'],
            $customerData['locale']
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
     * @dataProvider customerDataProvider
     */
    public function testCreateCustomerWithVariousData(array $customerData, array $expectedChecks): void
    {
        $output = $this->customerUtil->createCustomer(
            $customerData['address'],
            $customerData['email'],
            $customerData['phone'],
            $customerData['first_name'],
            $customerData['last_name'],
            $customerData['ip_address'],
            $customerData['user_agent'],
            $customerData['locale'],
            $customerData['company'] ?? null
        );

        self::assertInstanceOf(CustomerDetails::class, $output);
        
        foreach ($expectedChecks as $property => $expectedValue) {
            switch ($property) {
                case 'email':
                    self::assertEquals($expectedValue, $output->getEmailAddress()->get());
                    break;
                case 'first_name':
                    self::assertEquals($expectedValue, $output->getFirstName());
                    break;
                case 'last_name':
                    self::assertEquals($expectedValue, $output->getLastName());
                    break;
                case 'ip_address':
                    if ($expectedValue !== null) {
                        self::assertEquals($expectedValue, $output->getIpAddress()->get());
                    }
                    break;
                case 'user_agent':
                    if ($expectedValue !== null) {
                        self::assertEquals($expectedValue, $output->getUserAgent());
                    }
                    break;
                case 'company':
                    if ($expectedValue !== null) {
                        self::assertEquals($expectedValue, $output->getCompanyName());
                    }
                    break;
            }
        }
    }

    /**
     * Data provider for customer creation tests
     */
    public function customerDataProvider(): array
    {
        return [
            'minimal_data' => [
                'customer_data' => [
                    'address' => new Address(),
                    'email' => 'test@test.com',
                    'phone' => '',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'ip_address' => null,
                    'user_agent' => null,
                    'locale' => 'nl_NL',
                ],
                'expected_checks' => [
                    'email' => 'test@test.com',
                    'first_name' => 'Test',
                    'last_name' => 'User',
                ]
            ],
            'full_data' => [
                'customer_data' => [
                    'address' => (new Address())
                        ->addCity('Amsterdam')
                        ->addCountry(new Country('NL'))
                        ->addHouseNumber('39')
                        ->addZipCode('1033 SC'),
                    'email' => 'john.doe@multisafepay.com',
                    'phone' => '0612345678',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'ip_address' => '1.1.1.1',
                    'user_agent' => 'Mozilla/5.0',
                    'locale' => 'nl_NL',
                    'company' => 'MultiSafepay'
                ],
                'expected_checks' => [
                    'email' => 'john.doe@multisafepay.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'ip_address' => '1.1.1.1',
                    'user_agent' => 'Mozilla/5.0',
                    'company' => 'MultiSafepay'
                ]
            ],
            'special_characters' => [
                'customer_data' => [
                    'address' => (new Address())
                        ->addCity('Tübingen')
                        ->addCountry(new Country('DE'))
                        ->addHouseNumber('123-A')
                        ->addZipCode('72070'),
                    'email' => 'test.special@example.com',
                    'phone' => '+49 30 123 4567',
                    'first_name' => 'Jörg',
                    'last_name' => 'van Güldenpfennig',
                    'ip_address' => null,
                    'user_agent' => null,
                    'locale' => 'de_DE',
                ],
                'expected_checks' => [
                    'email' => 'test.special@example.com',
                    'first_name' => 'Jörg',
                    'last_name' => 'van Güldenpfennig',
                ]
            ]
        ];
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
