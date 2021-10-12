if (typeof prestashop !== 'undefined') {
    prestashop.on(
        'changedCheckoutStep',
        function (event) {

            $("[id^='multisafepay-form-']").each(function () {
                $(this).parent().closest("div").prev("div").find("label img").css("height", "30px");
            });

            $("[id^='multisafepay-form-']").keypress(
                function (event) {
                    if (event.which === 13) {
                        event.preventDefault();
                    }
                }
            );

            $(".multisafepay-tokenization").each(function () {
                var paymentOptionFormId = $(this).attr("id");
                var tokenListInput = $(this).find(".form-group-token-list");
                if (tokenListInput.length > 0) {
                    // Initial check on load checkout page.
                    togglePaymentFields(paymentOptionFormId);
                    tokenListInput.change(function () {
                        // Check status on change.
                        togglePaymentFields(paymentOptionFormId);
                    });
                }
            });

        }
    );
}

function togglePaymentFields(paymentOptionFormId)
{
    var selected_value = $("#" + paymentOptionFormId + " .form-group-token-list input[name='selectedToken']:checked").val();
    // If selected value is add a new one, show the direct fields including checkbox to save a new payment method
    if ('new' != selected_value) {
        $("#" + paymentOptionFormId + " .form-group").not(".form-group-token-list").hide();
    } else {
        $("#" + paymentOptionFormId + " .form-group").not(".form-group-token-list").show();
    }
}
