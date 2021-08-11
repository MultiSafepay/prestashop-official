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

use Multisafepay;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay as MultiSafepayPaymentMethod;

class MultiSafepayTest extends BaseMultiSafepayTest
{
    protected $multiSafepayPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->multiSafepayPaymentMethod = new MultiSafepayPaymentMethod($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->multiSafepayPaymentMethod->name;
        $this->assertEquals('MultiSafepay', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->multiSafepayPaymentMethod->description;
        $this->assertEquals('', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->multiSafepayPaymentMethod->gatewayCode;
        $this->assertEmpty($output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->multiSafepayPaymentMethod->type;
        $this->assertEquals('redirect', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->multiSafepayPaymentMethod->icon;
        $this->assertEquals('multisafepay.png', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay::getInputFields
     */
    public function testGetInputFields()
    {
        $output = $this->multiSafepayPaymentMethod->inputs;
        $this->assertIsArray($output);
        $this->assertArrayHasKey('hidden', $output);
    }
}
