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

        // Unset the image from uploader field
        $('.multisafepay-panel-payment-option .panel-body .form-group .col-lg-9 .form-group .col-lg-12 div a').click(function (event) {
            event.preventDefault();
            event.stopPropagation();
            $(this).closest('.form-group').next().find('input[type=file]').attr('value', '');
            $(this).closest('.form-group').next().find('input[type=text]').css('text-indent', '999px');
            $(this).closest('.form-group').next().find('input[type=text]').attr('value', 'remove');
            $(this).closest('.form-group').remove();
        });

        function togglePaymentMerchantFields(directOff, directOn, merchantInfo) {
            if ($(directOff).is(':checked')) {
                $(merchantInfo).closest('.form-group').hide();
            } else if ($(directOn).is(':checked')) {
                $(merchantInfo).closest('.form-group').show();
            }

            $(directOn).on('click', function () {
                $(merchantInfo).closest('.form-group').slideDown();
            });

            $(directOff).on('click', function () {
                $(merchantInfo).closest('.form-group').slideUp();
            });
        }

        togglePaymentMerchantFields(
            '#MULTISAFEPAY_OFFICIAL_DIRECT_GOOGLEPAY_off',
            '#MULTISAFEPAY_OFFICIAL_DIRECT_GOOGLEPAY_on',
            '.google-pay-direct-name, .google-pay-direct-id'
        );

        togglePaymentMerchantFields(
            '#MULTISAFEPAY_OFFICIAL_DIRECT_APPLEPAY_off',
            '#MULTISAFEPAY_OFFICIAL_DIRECT_APPLEPAY_on',
            '.apple-pay-direct-name'
        );

        // Initialize multi-language titles functionality
        initMultiLanguageTitles();

        // Ensure permanent help texts remain visible
        $('.multisafepay-permanent-help').show().css({
            'display': 'block',
            'visibility': 'visible'
        });

        // Handle form submission to ensure all fields are included
        $('form').on('submit', function() {
            $('.multisafepay-inline-language-field').each(function() {
                var $field = $(this);
                $field.find('input, select, textarea').each(function() {
                    $(this).prop('disabled', false);
                });
            });
        });
    });
})(jQuery);

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

/**
 * Initialize Multi-Language Title Fields functionality
 * Handles expand/contract behavior for title fields with multiple languages
 */
function initMultiLanguageTitles() {
    // Move additional language fields into their containers
    $('.multisafepay-additional-language-field').each(function() {
        const $field = $(this);
        const baseName = $field.data('base-name');
        const selector = '.multisafepay-additional-languages[data-base-name="' + baseName + '"]';
        const $container = $(selector);

        if ($container.length > 0) {
            // Clone the field and modify its structure for inline display
            const $clonedField = $field.clone();
            $clonedField.removeClass('multisafepay-additional-language-field');
            $clonedField.addClass('multisafepay-inline-language-field');
            $clonedField.css('display', 'block');
            $clonedField.css('margin-bottom', '8px');

            // Modify the structure to be more compact
            const $label = $clonedField.find('.control-label');
            const $inputContainer = $clonedField.find('.col-lg-9');
            const $input = $clonedField.find('input');

            $clonedField.removeClass('form-group');
            $clonedField.addClass('row');
            // Keep the same proportions as the main field: col-lg-3 and col-lg-9
            $label.removeClass('col-lg-3').addClass('col-lg-3');
            $inputContainer.removeClass('col-lg-9').addClass('col-lg-9');

            // Fix accessibility: Associate label with input
            const inputId = $input.attr('id');
            if (inputId && !$label.attr('for')) {
                $label.attr('for', inputId);
            }

            // Remove help text from cloned field (we only want it in the main field)
            $clonedField.find('.help-block').remove();

            // Extract language name from label and show only the language
            const labelText = $label.text().trim();
            const match = labelText.match(/\(([^)]+)\)$/);
            if (match && match[1]) {
                // If we found a language in parentheses, use only that
                $label.text(match[1]);
            }

            $container.append($clonedField);

            // Remove the original field
            $field.remove();
        }
    });

    // Get all language toggle buttons once to avoid duplicate selector
    const $languageToggleButtons = $('.multisafepay-language-toggle');

    // Initialize all buttons as collapsed (white background)
    $languageToggleButtons.addClass('collapsed');

    // Hide toggle buttons if there are no additional languages
    $languageToggleButtons.each(function() {
        const $button = $(this);
        const baseName = $button.data('base-name');
        const selector = '.multisafepay-additional-languages[data-base-name="' + baseName + '"]';
        const $container = $(selector);

        // Ensure $container is a valid jQuery object before calling methods
        if ($container.children().length === 0) {
            $button.hide();
        }
    });

    // Handle toggle button clicks
    $languageToggleButtons.on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const baseName = $button.data('base-name');
        const selector = '.multisafepay-additional-languages[data-base-name="' + baseName + '"]';
        const $container = $(selector);
        const $icon = $button.find('.toggle-icon');

        // Ensure $container is a valid jQuery object before calling methods
        if ($container.is(':visible')) {
            // Collapse
            $container.slideUp(300);
            $icon.html('&#xE313;'); // keyboard_arrow_down
            $button.attr('title', 'Expand Other Languages');
            $button.removeClass('expanded').addClass('collapsed');
        } else {
            // Expand
            $container.slideDown(300);
            $icon.html('&#xE316;'); // keyboard_arrow_up
            $button.attr('title', 'Contract Other Languages');
            $button.removeClass('collapsed').addClass('expanded');
        }
    });
}
