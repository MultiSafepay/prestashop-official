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

var MultiSafepayPaymentComponent = function (config, gateway, paymentComponentId) {

    var paymentComponent = null;

    this.construct = function (config, gateway, paymentComponentId) {
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
                order: config.orderData,
                recurring: config.recurring
            }
        );
    };

    var insertPayload = function (payload) {
        $("#multisafepay-form-" + paymentComponentId + " input[name='payload']").val(payload);
    };

    var insertTokenize = function (tokenize) {
        $("#multisafepay-form-" + paymentComponentId + " input[name='tokenize']").val(tokenize);
    };

    var removePayload = function () {
        $("#multisafepay-form-" + paymentComponentId + " input[name='payload']").val();
    };

    var initializePaymentComponent = function () {
        getPaymentComponent().init('payment', {
            container: '#multisafepay-payment-component-' + paymentComponentId,
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
        $('#multisafepay-form-' + paymentComponentId).submit(function (event) {
            removePayload();
            if (getPaymentComponent().hasErrors()) {
                logger(getPaymentComponent().getErrors());
                $('#payment-confirmation button').removeClass('disabled');
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            var payload = getPaymentComponent().getPaymentData().payload;
            var tokenize = getPaymentComponent().getPaymentData().tokenize ?? '0';
            insertPayload(payload);
            insertTokenize(tokenize);
            $('#multisafepay-form-' + paymentComponentId).unbind('submit').submit();
        });
    };

    var logger = function (argument) {
        if (config.debug) {
            console.log(argument);
        }
    };

    this.construct(config, gateway, paymentComponentId);

};

function createMultiSafepayPaymentComponents()
{
    $("[id^='multisafepay-payment-component-']").each(function () {
        new MultiSafepayPaymentComponent(window['multisafepayPaymentComponentConfig' + $(this).data('payment-id')], $(this).data('gateway'), $(this).data('payment-component-id'));
    });
}

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

(function ($) {
    $(function () {
        // Default checkout
        createMultiSafepayPaymentComponents();
    });
})(jQuery);
