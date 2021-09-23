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
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class GenericTest extends BaseMultiSafepayTest
{
    protected $genericPaymentMethod;

    public function setUp(): void
    {
        parent::setUp();

        /** @var Multisafepay $multisafepay */
        $multisafepay = $this->container->get('multisafepay');

        $this->genericPaymentMethod = new Generic($multisafepay);
    }


    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getPaymentOptionName
     */
    public function testGetPaymentOptionName()
    {
        $output = $this->genericPaymentMethod->name;
        self::assertEquals('Generic Gateway', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getPaymentOptionDescription
     */
    public function testGetPaymentOptionDescription()
    {
        $output = $this->genericPaymentMethod->description;
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getPaymentOptionGatewayCode
     */
    public function testGetPaymentOptionGatewayCode()
    {
        $output = $this->genericPaymentMethod->gatewayCode;
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getTransactionType
     */
    public function testGetTransactionType()
    {
        $output = $this->genericPaymentMethod->type;
        self::assertEquals('redirect', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getPaymentOptionLogo
     */
    public function testGetPaymentOptionLogo()
    {
        $output = $this->genericPaymentMethod->icon;
        self::assertEquals('', $output);
        self::assertIsString($output);
    }

    /**
     * @covers \MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic::getInputFields
     */
    public function testGetInputFields()
    {
        $output = $this->genericPaymentMethod->inputs;
        self::assertIsArray($output);
        self::assertIsArray($output[0]);
        self::assertArrayHasKey('type', $output[0]);
        self::assertEquals('hidden', $output[0]['type']);
    }
}
