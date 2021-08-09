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
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\Tests\PaymentOptions;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use PHPUnit\Framework\TestCase;
use MultiSafepay\PrestaShop\PaymentOptions\Gateways;
use Configuration;

class GatewaysTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        $this->currentApiKey   = Configuration::get('MULTISAFEPAY_TEST_API_KEY');
        $this->currentTestMode = Configuration::get('MULTISAFEPAY_TEST_MODE');
        $apiKey = getenv('MULTISAFEPAY_API_KEY');
        Configuration::updateValue('MULTISAFEPAY_TEST_API_KEY', $apiKey);
        Configuration::updateValue('MULTISAFEPAY_TEST_MODE', 1);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\Gateways::getMultiSafepayPaymentOptions
     */
    public function testGetMultiSafepayPaymentOptionsReturnArray()
    {
        $output = Gateways::getMultiSafepayPaymentOptions();
        $this->assertIsArray($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\Gateways::getMultiSafepayPaymentOptions
     */
    public function testGetMultiSafepayPaymentOptionsReturnInstanceOfBasePaymentOption()
    {
        $paymentOptions = Gateways::getMultiSafepayPaymentOptions();
        foreach ($paymentOptions as $paymentOption) {
            $this->assertInstanceOf(BasePaymentOption::class, $paymentOption);
        }
    }

    public function tearDown(): void
    {
        Configuration::updateValue('MULTISAFEPAY_TEST_API_KEY', $this->currentApiKey);
        Configuration::updateValue('MULTISAFEPAY_TEST_MODE', $this->currentTestMode);
        parent::tearDown();
    }
}
