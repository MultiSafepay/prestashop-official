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

namespace MultiSafepay\Tests\PaymentOption;

use Configuration;
use Exception;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class BasePaymentOptionTest extends BaseMultiSafepayTest
{
    /**
     * @var BasePaymentOption
     */
    public BasePaymentOption $basePaymentOption;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $multisafepayModule = $this->container->get('multisafepay');
        $paymentMethod = new PaymentMethod($this->visaPaymentMethodData());
        $this->basePaymentOption = new BasePaymentOption($paymentMethod, $multisafepayModule);
    }

    public function testBasePaymentOption(): void
    {
        $this->assertInstanceOf(BasePaymentOption::class, $this->basePaymentOption);
    }

    public function testGetGatewayCode(): void
    {
        $this->assertEquals('VISA', $this->basePaymentOption->getGatewayCode());
    }

    public function testGetName(): void
    {
        $this->assertEquals('Visa', $this->basePaymentOption->getName());
    }

    public function testGetUniqueName(): void
    {
        $this->assertEquals('VISA', $this->basePaymentOption->getUniqueName());
    }

    public function testGetPaymentComponentId(): void
    {
        $this->assertEquals('VISA', $this->basePaymentOption->getPaymentComponentId());
    }

    public function testGetAllowedCountries(): void
    {
        $this->assertEquals([], $this->basePaymentOption->getAllowedCountries());
    }

    public function testCanProcessRefunds(): void
    {
        $this->assertTrue($this->basePaymentOption->canProcessRefunds());
    }

    public function testGetDescriptionReturnsConfiguredValue()
    {
        Configuration::set('MULTISAFEPAY_OFFICIAL_DESCRIPTION_' . $this->basePaymentOption->getUniqueName(), 'Visa Custom Description');
        $this->assertEquals('Visa Custom Description', $this->basePaymentOption->getDescription());
    }

    public function testGetPaymentOptionSettingsFields()
    {
        $this->assertEquals([], $this->basePaymentOption->getPaymentOptionSettingsFields());
    }

    private function visaPaymentMethodData(): array
    {
        return [
            'additional_data' => [],
            'allowed_amount' => [
                'max' => null,
                'min' => 0
            ],
            'allowed_countries' => [],
            'allowed_currencies' => [
                'AED',
                'AUD',
                'BGN',
                'BRL',
                'CAD',
                'CHF',
                'CLP',
                'CNY',
                'CZK',
                'DKK',
                'EUR',
                'GBP',
                'HKD',
                'HRK',
                'HUF',
                'ILS',
                'INR',
                'ISK',
                'JPY',
                'MXN',
                'MYR',
                'NOK',
                'NZD',
                'PEN',
                'PHP',
                'PLN',
                'RUB',
                'SEK',
                'SGD',
                'THB',
                'TRY',
                'TWD',
                'USD',
                'VEF',
                'ZAR'
            ],
            'apps' => [
                'fastcheckout' => [
                    'is_enabled' => true,
                    'qr' => [
                        'supported' => false
                    ]
                ],
                'payment_components' => [
                    'has_fields' => true,
                    'is_enabled' => true,
                    'qr' => [
                        'supported' => false
                    ]
                ]
            ],
            'brands' => [
                [
                    'allowed_countries' => [
                        'DK'
                    ],
                    'icon_urls' => [
                        'large' => 'https://testmedia.multisafepay.com/img/methods/3x/dankort.png',
                        'medium' => 'https://testmedia.multisafepay.com/img/methods/2x/dankort.png',
                        'vector' => 'https://testmedia.multisafepay.com/img/methods/svg/dankort.svg'
                    ],
                    'id' => 'DANKORT',
                    'name' => 'Dankort'
                ]
            ],
            'description' => null,
            'icon_urls' => [
                'large' => 'https://testmedia.multisafepay.com/img/methods/3x/visa.png',
                'medium' => 'https://testmedia.multisafepay.com/img/methods/2x/visa.png',
                'vector' => 'https://testmedia.multisafepay.com/img/methods/svg/visa.svg'
            ],
            'id' => 'VISA',
            'label' => null,
            'name' => 'Visa',
            'preferred_countries' => [],
            'required_customer_data' => [],
            'shopping_cart_required' => false,
            'tokenization' => [
                'is_enabled' => true,
                'models' => [
                    'cardonfile' => true,
                    'subscription' => true,
                    'unscheduled' => true
                ]
            ],
            'type' => 'payment-method'
        ];
    }
}
