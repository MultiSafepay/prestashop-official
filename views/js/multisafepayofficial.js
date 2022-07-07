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
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

var MultiSafepayPaymentComponent = function (config, gateway) {

    var paymentComponent = null;

    this.construct = function (config, gateway) {
        initializePaymentComponent();
        onSubmitCheckoutForm();
    };

    var getPaymentComponent = function () {
        if ( ! paymentComponent ) {
            paymentComponent = getNewPaymentComponent();
        }

        return paymentComponent;
    };

    var getNewPaymentComponent = function () {
        return new MultiSafepay(
            {
                env: config.env,
                apiToken: config.apiToken,
                order: config.orderData
            }
        );
    };

    var insertPayload = function (payload) {
        $("#multisafepay-form-" + gateway.toLowerCase() + " input[name='payload']").val(payload);
    };

    var removePayload = function () {
        $("#multisafepay-form-" + gateway.toLowerCase() + " input[name='payload']").val();
    };

    var initializePaymentComponent = function () {
        getPaymentComponent().init('payment', {
            container: '#multisafepay-payment-component-' + gateway.toLowerCase(),
            gateway: gateway,
            onLoad: state => {
                logger('onLoad');
            },
            onError: state => {
                logger('onError');
            }
        });
    };

    var onSubmitCheckoutForm = function () {
        $('#multisafepay-form-' + gateway.toLowerCase()).submit(function (event) {
            removePayload();
            if (getPaymentComponent().hasErrors()) {
                logger(getPaymentComponent().getErrors());
                $('#payment-confirmation button').removeClass('disabled');
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            var payload = getPaymentComponent().getPaymentData().payload;
            insertPayload(payload);
            $('#multisafepay-form-' + gateway.toLowerCase()).unbind('submit').submit();
        });
    };

    var logger = function (argument) {
        if (config.debug) {
            console.log(argument);
        }
    };

    this.construct(config, gateway);

};

function createMultiSafepayPaymentComponents()
{
    $("[id^='multisafepay-payment-component-']").each(function () {
        new MultiSafepayPaymentComponent(window['multisafepayPaymentComponentConfig' + $(this).data('gateway')], $(this).data('gateway'));
    });
}

// Default checkout
$(document).ready(function () {
    createMultiSafepayPaymentComponents();
});

// Support for "The Checkout module"
if (typeof prestashop !== 'undefined') {
    prestashop.on(
        'thecheckout_updatePaymentBlock',
        function (event) {
            if (event && event.reason === 'update') {
                createMultiSafepayPaymentComponents();
            }
        }
    );
}

// One Page Checkout PS support. Version 4.0.X
$(document).on('opc-load-payment:completed', function () {
    createMultiSafepayPaymentComponents();
});

// One Page Checkout PS support. Version 4.1.X
if (typeof prestashop !== 'undefined') {
    prestashop.on(
        'opc-payment-getPaymentList-complete',
        function (event) {
            createMultiSafepayPaymentComponents();
        }
    );
}




