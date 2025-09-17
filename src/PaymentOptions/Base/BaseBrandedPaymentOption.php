<?php
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MultiSafepay\PrestaShop\PaymentOptions\Base;

use Context;
use Exception;
use Media;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\PrestaShop\Helper\PathHelper;
use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultisafepayOfficial;
use PrestaShopDatabaseException;
use PrestaShopException;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BaseBrandedPaymentOption extends BasePaymentOption
{
    /**
     * @var MultisafepayOfficial
     */
    public $module;

    /**
     * @var array
     */
    public $brand;

    /**
     * @var string
     */
    public $gatewayCode;

    /**
     * @var string
     */
    public $gatewayName;

    /**
     * @var string
     */
    public $parentGateway;

    /**
     * @var string
     */
    public $parentName;

    /**
     * @var array
     */
    private $allowedCountries;

    public function __construct(PaymentMethod $paymentMethod, MultisafepayOfficial $module, array $brand = [])
    {
        parent::__construct($paymentMethod, $module);
        $this->brand = $brand;
        $this->gatewayCode = $this->brand['id'];
        $this->gatewayName = $this->getName();
        $this->parentGateway = $paymentMethod->getId();
        $this->parentName = $paymentMethod->getName();
        $this->allowedCountries = $this->brand['allowed_countries'];
    }

    /**
     * @param bool $fromCheckout
     *
     * @return string
     */
    public function getGatewayCode(bool $fromCheckout = false): string
    {
        // If this request is called from the checkout, it returns the branded
        // gateway code, forcing to continue the loop to find the correct gateway,
        // which is the parent of the branded gateway, certainly.
        if ($fromCheckout) {
            return $this->getUniqueName();
        }
        return $this->parentGateway;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->module->l($this->brand['name'] . ' - ' . $this->parentName);
    }

    /**
     * @return string
     */
    public function getBrandName(): string
    {
        return $this->module->l($this->brand['name']);
    }

    /**
     * @return string
     */
    public function getUniqueName(): string
    {
        return $this->gatewayCode . '_' . $this->parentGateway;
    }

    /**
     * @return string
     */
    public function getPaymentComponentId(): string
    {
        return $this->gatewayCode . '-' . $this->parentGateway;
    }

    /**
     * @return array
     */
    public function getAllowedCountries(): array
    {
        return $this->allowedCountries;
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return $this->brand['icon_urls']['medium'] ?: '';
    }

    /**
     * @param Context $context
     *
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     *
     * @phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function registerJavascript(Context $context): void
    {
        if ($this->allowPaymentComponent()) {
            $context->controller->registerJavascript(
                'module-multisafepay-payment-component-javascript',
                self::MULTISAFEPAY_COMPONENT_JS_URL,
                [
                    'server' => 'remote'
                ]
            );

            $orderService = new OrderService($this->module, new SdkService());

            Media::addJsDef(
                [
                    'multisafepayPaymentComponentConfig' . $this->getUniqueName(
                    ) => $orderService->createPaymentComponentOrder(
                        $this->getGatewayCode(),
                        $this->allowTokenization($context->customer->id) ? (string)$context->customer->id : null,
                        $this->allowTokenization($context->customer->id) ? 'cardOnFile' : null,
                        $context->cart
                    )
                ]
            );

            $context->controller->registerJavascript(
                'module-multisafepay-initialize-payment-component-javascript',
                PathHelper::getAssetPath('multisafepayofficial.js')
            );
        }
    }
}
