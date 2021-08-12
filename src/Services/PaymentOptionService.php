<?php
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

namespace MultiSafepay\PrestaShop\Services;

use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Generic;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Ideal;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\MultiSafepay;
use Multisafepay as MultiSafepayModule;
use PaymentModule; // This line is here to prevent this PHPStan error: Internal error: Class 'PaymentModuleCore' not found

/**
 * This class holds all the MultiSafepay payment methods
 *
 * @since      4.0.0
 */
class PaymentOptionService
{
    public const MULTISAFEPAY_PAYMENT_OPTIONS = [
        Ideal::class,
        MultiSafepay::class,
        Generic::class,
    ];

    /**
     * @var MultiSafepayModule
     */
    private $module;

    /**
     * SdkService constructor.
     */
    public function __construct(MultiSafepayModule $module)
    {
        $this->module = $module;
    }

    /**
     * Get all MultiSafepay payment options
     *
     * @return array
     */
    public function getMultiSafepayPaymentOptions(): array
    {
        $paymentOptions = array();
        foreach (self::MULTISAFEPAY_PAYMENT_OPTIONS as $paymentOption) {
            $paymentOptions[] = new $paymentOption($this->module);
        }
        return $paymentOptions;
    }

    /**
     * @param string $gatewayCode
     *
     * @return BasePaymentOption
     */
    public function getMultiSafepayPaymentOption(string $gatewayCode): BasePaymentOption
    {
        foreach (self::MULTISAFEPAY_PAYMENT_OPTIONS as $paymentOptionClassname) {
            $paymentOption = new $paymentOptionClassname($this->module);
            if ($paymentOption->getPaymentOptionGatewayCode() == $gatewayCode) {
                return $paymentOption;
            }
        }
        return new MultiSafepay($this->module);
    }
}