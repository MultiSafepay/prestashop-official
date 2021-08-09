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

use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal;
use PHPUnit\Framework\TestCase;
use Configuration;

class IdealTest extends TestCase
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
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = (new Ideal())->name;
        $this->assertEquals('iDEAL', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = (new Ideal())->description;
        $this->assertEquals('', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = (new Ideal())->gatewayCode;
        $this->assertEquals('IDEAL', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = (new Ideal())->type;
        $this->assertEquals('redirect', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = (new Ideal())->icon;
        $this->assertEquals('ideal.png', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal::getInputFields
     */
    public function testGetInputFields()
    {
        $output = (new Ideal())->inputs;
        $this->assertIsArray($output);
        $this->assertArrayHasKey('hidden', $output);
        $this->assertArrayHasKey('select', $output);
    }

    public function tearDown(): void
    {
        Configuration::updateValue('MULTISAFEPAY_TEST_API_KEY', $this->currentApiKey);
        Configuration::updateValue('MULTISAFEPAY_TEST_MODE', $this->currentTestMode);
        parent::tearDown();
    }
}
