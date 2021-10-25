$(document).ready(function () {
    $("[id^='multisafepay-panel-payment-option-']").each(function () {
        var paymentOptionIdPanel = $(this).attr("id");

        $('#' + paymentOptionIdPanel + ' .panel-body .form-group:first .multisafepay-payment-methods-list-switch input:radio').change(function () {
            togglePaymentOptionIconStatus(paymentOptionIdPanel, $(this).val());
        });

        $('#' + paymentOptionIdPanel + '  .panel-heading .panel-title a span.status').click(function (event) {
            event.preventDefault();
            event.stopPropagation();
            togglePaymentOptionFieldStatus(paymentOptionIdPanel, $(this).hasClass('active'));
        });
    });
});

function togglePaymentOptionIconStatus(paymentOptionIdPanel, active)
{
    if (active == 1) {
        $('#' + paymentOptionIdPanel + ' .panel-heading .panel-title a span.status').addClass('active');
    } else {
        $('#' + paymentOptionIdPanel + ' .panel-heading .panel-title a span.status').removeClass('active');
    }
}

function togglePaymentOptionFieldStatus(paymentOptionIdPanel, disable)
{
    if (disable) {
        $('#' + paymentOptionIdPanel + ' .panel-body .form-group:first .multisafepay-payment-methods-list-switch input:radio:last').trigger('click');
    } else {
        $('#' + paymentOptionIdPanel + ' .panel-body .form-group:first .multisafepay-payment-methods-list-switch input:radio:first').trigger('click');
    }
}
