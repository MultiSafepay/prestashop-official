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

if (typeof prestashop !== 'undefined') {
    prestashop.on(
        'changedCheckoutStep',
        function () {
            triggerCommonMethods();
        }
    );
    prestashop.on(
        'thecheckout_updatePaymentBlock',
        function () {
            triggerCommonMethods();
        }
    );
}

function triggerCommonMethods()
{
    preventSubmitOnKeyPress();
    toggleTokenizationPaymentMethodsFields();
    adjustPaymentLogoimages();
}

function adjustPaymentLogoimages()
{
    $("[id^='multisafepay-form-']").each(function () {
        $(this).parent().closest("div").prev("div").find("label img").css("height", "30px");
    });
}

function toggleTokenizationPaymentMethodsFields()
{
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

function preventSubmitOnKeyPress()
{
    $("[id^='multisafepay-form-']").keypress(
        function (event) {
            if (event.which === 13) {
                event.preventDefault();
            }
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
