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
        if ( ! this.paymentComponent ) {
            this.paymentComponent = getNewPaymentComponent();
        }

        return this.paymentComponent;
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

    var isUsingToken = function () {
        if ($('#multisafepay-form-' + gateway.toLowerCase() + ' .form-group-token-list').length === 0) {
            return false;
        }
        if ($('#multisafepay-form-' + gateway.toLowerCase() + ' .form-group-token-list input[name=\'selectedToken\']:checked').val() === 'new') {
            return false;
        }
        return true;
    };

    var onSubmitCheckoutForm = function () {
        $('#multisafepay-form-' + gateway.toLowerCase()).submit(function (event) {
            if (!isUsingToken()) {
                if (getPaymentComponent().hasErrors()) {
                    logger(getPaymentComponent().getErrors());
                    $('#payment-confirmation button').removeClass('disabled');
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                var payload = getPaymentComponent().getPaymentData().payload;
                insertPayload(payload);
            }
            removePayload();
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

$(document).ready(function () {
    $.each(multisafepayPaymentComponentGateways, function (index, gateway) {
        if ($('#multisafepay-form-' + gateway.toLowerCase()).length > 0) {
            new MultiSafepayPaymentComponent(multisafepayPaymentComponentConfig, gateway);
        }
    });
});


