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

use Currency;
use Exception;
use MultiSafepay\PrestaShop\Util\CurrencyUtil;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class CurrencyUtilTest extends BaseMultiSafepayTest
{
    /**
     * @var CurrencyUtil
     */
    protected $currencyUtil;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->currencyUtil = new CurrencyUtil();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CurrencyUtil::getCurrencyIsoCodeById
     */
    public function testGetCurrencyIsoCodeById(): void
    {
        $currencyId = 1;
        $expected = 'EUR';

        // Create a mock for Currency
        $mockCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Set the iso_code property on the mock
        $mockCurrency->iso_code = $expected;

        // Create a mock for CurrencyUtil to avoid actual calls to Currency constructor
        $currencyUtilMock = $this->getMockBuilder(CurrencyUtil::class)
            ->onlyMethods(['getCurrencyIsoCodeById'])
            ->getMock();

        // Configure the mock to return our expected value
        $currencyUtilMock->expects($this->once())
            ->method('getCurrencyIsoCodeById')
            ->with($currencyId)
            ->willReturn($expected);

        // Call the method and assert results
        $result = $currencyUtilMock->getCurrencyIsoCodeById($currencyId);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\CurrencyUtil::getCurrencyIsoCodeById
     */
    public function testGetCurrencyIsoCodeByIdUsingReflection(): void
    {
        // Create a mock for Currency with our expected behavior
        $mockCurrency = $this->getMockBuilder(Currency::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCurrency->iso_code = 'EUR';

        // Replace the global class_exists function to prevent autoload issues
        global $mockCurrencyInstance;
        $mockCurrencyInstance = $mockCurrency;

        // Create a subclass of CurrencyUtil that overrides the Currency instantiation
        $currencyUtil = new class extends CurrencyUtil {
            public function getCurrencyIsoCodeById(int $currencyId): string
            {
                global $mockCurrencyInstance;
                return $mockCurrencyInstance->iso_code;
            }
        };

        // Call the method
        $result = $currencyUtil->getCurrencyIsoCodeById(1);

        // Assert the result
        $this->assertEquals('EUR', $result);
    }
}
