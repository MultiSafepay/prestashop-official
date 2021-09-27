$(document).ready(function () {

    $("[id^='multisafepay-form-']").each(function () {
        $(this).parent().closest("div").prev("div").find("label img").css('height', '30px');
    });


    $("[id^='multisafepay-form-']").keypress(
        function (event) {
            if (event.which === 13) {
                event.preventDefault();
            }
        }
    );

});
