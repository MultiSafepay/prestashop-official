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
    checkIfDeviceSupportApplePay(true);
});

if (typeof prestashop !== 'undefined') {
    // One Page Checkout PS support. Version 4.1.X
    prestashop.on(
        'opc-payment-getPaymentList-complete',
        function (event) {
            checkIfDeviceSupportApplePay(true);
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
            if (event && event.reason === 'update') {
                checkIfDeviceSupportApplePay();
            }
        }
    );
}

function checkIfDeviceSupportApplePay(isOpc = false)
{
    try {
        if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
            removeApplePay(isOpc);
        }
    } catch (error) {
        console.error(error);
    }
}

function removeApplePay(isOpc)
{
    if (isOpc) {
        $('*[data-module-name="APPLEPAY"]').closest('.module_payment_container').remove();
        // Required when the payment method form field is missing but the logo is still displayed
        $('img[src*="applepay.png"], img[title="Apple Pay"]').closest('.module_payment_container').remove();
    } else {
        $('*[data-module-name="APPLEPAY"]').parent().closest('div').remove();
    }
}
