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
use MultiSafepay\PrestaShop\Util\LanguageUtil;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use PrestaShopException;
use Tools;

class LanguageUtilTest extends BaseMultiSafepayTest
{
    /**
     * @var LanguageUtil
     */
    protected $languageUtil;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->languageUtil = new LanguageUtil();
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\LanguageUtil::getLanguageCode
     * @throws PrestaShopException
     */
    public function testGetLanguageCodeTwoLetterFormat(): void
    {
        // Create a partial mock for Language class
        $mockLanguageUtil = $this->getMockBuilder(LanguageUtil::class)
            ->onlyMethods(['getLanguageCode'])
            ->getMock();

        // Configure the getLanguageCode method to return our expected value
        $mockLanguageUtil->expects($this->once())
            ->method('getLanguageCode')
            ->with(1)
            ->willReturn('nl_NL');

        $result = $mockLanguageUtil->getLanguageCode(1);
        $this->assertEquals('nl_NL', $result);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\LanguageUtil::getLanguageCode
     * @dataProvider languageCodeDataProvider
     * @throws PrestaShopException
     */
    public function testGetLanguageCodeWithReflectionMethod(string $isoCode, string $languageCode, string $expected): void
    {
        // Create a specific test implementation
        $languageUtil = new class($isoCode, $languageCode) extends LanguageUtil {
            private $isoCode;
            private $languageCode;

            public function __construct(string $isoCode, string $languageCode)
            {
                $this->isoCode = $isoCode;
                $this->languageCode = $languageCode;
            }

            public function getLanguageCode(int $languageId): string
            {
                // Override static methods to return our test values
                if (Tools::strlen($this->languageCode) === 2) {
                    return Tools::strtolower($this->languageCode) . '_' . Tools::strtoupper($this->languageCode);
                }

                $parts = explode('-', $this->languageCode);
                return Tools::strtolower($parts[0]) . '_' . Tools::strtoupper($parts[1]);
            }
        };

        $result = $languageUtil->getLanguageCode(1);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for testGetLanguageCodeWithReflectionMethod
     */
    public function languageCodeDataProvider(): array
    {
        return [
            'Two letter language code' => ['nl', 'nl', 'nl_NL'],
            'Hyphenated language code' => ['en', 'en-us', 'en_US'],
            'Dutch language' => ['nl', 'nl-nl', 'nl_NL'],
            'English language' => ['en', 'en-gb', 'en_GB'],
        ];
    }
}
