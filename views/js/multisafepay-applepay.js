/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
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

(function ($) {
    $(function () {
        checkIfDeviceSupportApplePay();
    });
})(jQuery);

// One Page Checkout PS support. Version 4.0.X
$(document).on('opc-load-payment:completed', function () {
    checkIfDeviceSupportApplePay();
});

if (window.multisafepayCheckoutUtils.hasPrestashopEventBus()) {
    // One Page Checkout PS support. Version 4.1.X
    prestashop.on(
        'opc-payment-getPaymentList-complete',
        function (event) {
            checkIfDeviceSupportApplePay();
        }
    );

    // Each checkout step submission will fire this event.
    prestashop.on(
        'changedCheckoutStep',
        function () {
            checkIfDeviceSupportApplePay();
        }
    );

    // The Checkout module support
    prestashop.on(
        'thecheckout_updatePaymentBlock',
        function (event) {
            if (
                event &&
                event.reason === 'update' &&
                window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isTheCheckoutActive
            ) {
                checkIfDeviceSupportApplePay();
            }
        }
    );
}

function checkIfDeviceSupportApplePay()
{
    try {
        if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
            removeApplePay();
        }
    } catch (error) {
        console.error(error);
    }
}

function removeApplePay()
{
    const applePayOptionSelector = '*[data-module-name^="APPLEPAY"]';
    const applePayLogoSelector = '.payment-options img[src*="applepay.png"], .payment-options img[title="Apple Pay"]';
    const paymentOptionWrappers = '.module_payment_container, .payment-option, div[id$="-container"]';

    $(applePayOptionSelector).closest(paymentOptionWrappers).remove();

    // Some checkout renders may leave only the logo node behind after async updates.
    $(applePayLogoSelector).closest(paymentOptionWrappers).remove();
}
