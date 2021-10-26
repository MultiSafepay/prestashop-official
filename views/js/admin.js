$(document).ready(function () {

    initDragula();

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

        $('#' + paymentOptionIdPanel + '  .panel-heading .panel-title a span.drag-and-drop-control').click(function (event) {
            event.preventDefault();
            event.stopPropagation();
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

function initDragula()
{
    var default_drake = dragula([document.querySelector('#dragula-container #accordion'), document.querySelector('#dragula-container #accordion')], {
        direction: 'vertical',
        moves: function (el, container, handle) {
            return handle.classList.contains('drag-and-drop-control');
        },
    });
    default_drake.on("drag", function (el) {
        $(el).find('.panel-heading').parent('.multisafepay-panel-payment-option').addClass('drag-active gu-transit');
    });
    default_drake.on("drop", function (el) {
        $(el).find('.panel-heading').parent('.multisafepay-panel-payment-option').removeClass('drag-active gu-transit');
    });
    default_drake.on("cancel", function (el) {
        $(el).find('.panel-heading').parent('.multisafepay-panel-payment-option').removeClass('drag-active gu-transit');
    });
    default_drake.on("dragend", function (el) {
        $('#dragula-container #accordion .multisafepay-panel-payment-option').each(function (i, obj) {
            $(obj).find(".sort-order").attr("value", i+1);
        });
    });
};
