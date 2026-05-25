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
 *
 */

namespace MultiSafepay\PrestaShop\Helper;

use Cart;
use Context;
use MultiSafepay\PrestaShop\Services\CartDetailsService;
use Throwable;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CartDetailsResponseHelper
{
    /**
     * Build the current cart details JSON response for direct wallet controllers.
     *
     * @param Context $context
     * @return string
     */
    public static function buildResponse(Context $context): string
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            return json_encode(['error' => 'Method Not Allowed']);
        }

        $cart = $context->cart;

        // Direct wallet callbacks can outlive the checkout session; fall back cleanly instead of throwing.
        if (!$cart instanceof Cart || !Validate::isLoadedObject($cart) || (int)$cart->id_currency <= 0) {
            http_response_code(400);
            return json_encode(['error' => 'Cart details are unavailable for the current session']);
        }

        try {
            $cartDetailsService = new CartDetailsService();
            return json_encode($cartDetailsService->getCartDetails($cart));
        } catch (Throwable $exception) {
            LoggerHelper::logException(
                'error',
                $exception,
                'Error when trying to get cart details for direct wallets',
                null,
                $cart->id ?: null
            );
            http_response_code(500);
            return json_encode(['error' => 'Unable to retrieve cart details']);
        }
    }
}
