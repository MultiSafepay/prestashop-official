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

namespace MultiSafepay\PrestaShop\Services;

use Multisafepay;
use MultiSafepay\Api\Tokens\Token;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use PaymentModule;

/**
 * Class TokenizationService
 * @package MultiSafepay\PrestaShop\Services
 */
class TokenizationService
{
    /**
     * @var Multisafepay
     */
    protected $module;

    /**
     * @var SdkService
     */
    protected $sdkService;

    public function __construct(Multisafepay $module, SdkService $sdkService)
    {
        $this->module     = $module;
        $this->sdkService = $sdkService;
    }

    /**
     * @param string $customerId
     * @param string $gatewayCode
     *
     * @return Token[]
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getTokensByCustomerIdAndGatewayCode(string $customerId, string $gatewayCode): array
    {
        $tokenManager = $this->sdkService->getSdk()->getTokenManager();

        // The API will raise an error if there are no tokens for a customer, therefore we catch the error and return an empty array
        try {
            return $tokenManager->getListByGatewayCode($customerId, $gatewayCode);
        } catch (ApiException $exception) {
            return [];
        }
    }

    /**
     * @param string $customerId
     * @param BasePaymentOption $paymentOption
     *
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function createTokenizationCheckoutFields(string $customerId, BasePaymentOption $paymentOption): array
    {
        $inputFields = [];

        $tokens = $this->getTokensByCustomerIdAndGatewayCode($customerId, $paymentOption->getPaymentOptionGatewayCode());

        if (!empty($tokens)) {
            $options = [];
            foreach ($tokens as $token) {
                $options[] = [
                    'name'  => $token->getDisplay(),
                    'value' => $token->getToken(),
                ];
            }
            $inputFields[] = [
                'type'        => 'select',
                'name'        => 'selectedToken',
                'options'     => $options,
                'placeholder' => $this->module->l('Payment details'),
                'label' => $this->module->l('Select previous payment details or leave blank to enter new.'),
            ];
        }

        $inputFields[] = [
            'type'  => 'checkbox',
            'name'  => 'saveToken',
            'label' => $this->module->l('Save payment details for future purchases.'),
        ];

        return $inputFields;
    }
}
