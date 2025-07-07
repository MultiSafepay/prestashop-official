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

namespace MultiSafepay\Tests;

use MultiSafepay\Tests\Helper\MockHelper;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\Country;
use PHPUnit\Framework\TestCase;

abstract class BaseMultiSafepayTest extends TestCase
{
    use MockHelper;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Creates sample customer data for testing
     */
    protected function createValidCustomerData(): array
    {
        return [
            'email' => 'test@multisafepay.com',
            'phone' => '0612345678',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'locale' => 'nl_NL',
            'company' => 'MultiSafepay'
        ];
    }

    /**
     * Creates a sample address for testing
     */
    protected function createValidAddress(): Address
    {
        return (new Address())
            ->addCity('Amsterdam')
            ->addCountry(new Country('NL'))
            ->addHouseNumber('39')
            ->addZipCode('1033 SC');
    }

    /**
     * Creates minimal customer data for edge case testing
     */
    protected function createMinimalCustomerData(): array
    {
        return [
            'email' => 'test@test.com',
            'phone' => '',
            'first_name' => 'Test',
            'last_name' => 'User',
            'ip_address' => null,
            'user_agent' => null,
            'locale' => 'nl_NL',
            'company' => null
        ];
    }
}
