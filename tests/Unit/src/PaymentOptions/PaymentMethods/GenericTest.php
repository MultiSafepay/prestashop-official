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

use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic;
use PHPUnit\Framework\TestCase;

class GenericTest extends TestCase
{

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = (new Generic())->name;
        $this->assertEquals('Generic Gateway', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = (new Generic())->description;
        $this->assertEquals('', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = (new Generic())->gatewayCode;
        $this->assertEquals('', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = (new Generic())->type;
        $this->assertEquals('redirect', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = (new Generic())->icon;
        $this->assertEquals('', $output);
        $this->assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getInputFields
     */
    public function testGetInputFields()
    {
        $output = (new Generic())->inputs;
        $this->assertIsArray($output);
        $this->assertArrayHasKey('hidden', $output);
    }
}
