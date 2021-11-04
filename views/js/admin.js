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
