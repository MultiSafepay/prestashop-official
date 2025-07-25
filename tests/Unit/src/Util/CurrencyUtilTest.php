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
        $result = $this->currencyUtil->getCurrencyIsoCodeById(1);
        $this->assertEquals('EUR', $result);
    }
}
