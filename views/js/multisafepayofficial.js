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
        new MultiSafepayPaymentComponent(multisafepayPaymentComponentConfig, gateway);
    });
});


