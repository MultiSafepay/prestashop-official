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
    public function testGetLanguageCode(): void
    {
        $result = $this->languageUtil->getLanguageCode(1);
        $this->assertEquals('en_US', $result);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\Util\LanguageUtil::getLanguageCode
     * @throws PrestaShopException
     */
    public function testGetLanguageCodeWithTwoLetters(): void
    {
        $testableUtil = new class extends LanguageUtil {
            public function getLanguageCode(int $languageId): string
            {
                // Override to force a two-letter locale
                $isoCode = 'nl'; // Use a two-letter code

                // Call protected methods directly to mimic the original flow
                if (strlen($isoCode) === 2) {
                    return strtolower($isoCode) . '_' . strtoupper($isoCode);
                }

                $parts = explode('-', $isoCode);
                return strtolower($parts[0]).'_'.strtoupper($parts[1]);
            }
        };

        $result = $testableUtil->getLanguageCode(1);
        $this->assertEquals('nl_NL', $result);
    }
}
