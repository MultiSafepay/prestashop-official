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

if (window.multisafepayCheckoutUtils.hasPrestashopEventBus()) {
    prestashop.on(
        'changedCheckoutStep',
        function () {
            triggerCommonMethods(true);
        }
    );
    prestashop.on(
        'thecheckout_updatePaymentBlock',
        function () {
            triggerCommonMethods(true);
        }
    );
    prestashop.on(
        'opc-payment-getPaymentList-complete',
        function () {
            triggerCommonMethods(true);
        }
    );
}

let multisafepayIsProgrammaticSelection = false;
let multisafepayProgrammaticSelectionResetTimeoutId = null;
let multisafepayAutoSelectionRetryTimeoutId = null;
let multisafepayAutoSelectionRetryCount = 0;
let multisafepayTheCheckoutConfirmButton = null;
let multisafepayTheCheckoutConfirmButtonHandler = null;
const multisafepayAutoSelectionMaxRetryCount = 10;
const multisafepayManualSelectionSource = 'manual';
const multisafepayNonMultiSafepaySelectionMarker = '__NON_MULTISAFEPAY__';
let multisafepayHasManualPaymentSelection = false;
let multisafepayLastPaymentOptionsSignature = '';
let multisafepayLastCheckedPaymentOptionRefreshSignature = '';
// Cache the last storage/default combination already normalized on this page to
// avoid repeating the same localStorage scan and writes on every checkout refresh.
let multisafepayLastSelectionSyncSignature = '';

$(document).ready(function () {
    triggerCommonMethods(true);
});

/**
 * Executes common checkout handlers required after payment block updates.
 *
 * @param {boolean} [isPaymentBlockRefresh=false]
 * @returns {void}
 */
function triggerCommonMethods(isPaymentBlockRefresh)
{
    resetAutoSelectionRetryWindowIfNeeded(isPaymentBlockRefresh === true);
    preventSubmitOnKeyPress();
    toggleTokenizationPaymentMethodsFields();
    adjustPaymentLogoimages();
    initializePaymentMethodSelectionPreference();
    initializeTheCheckoutConfirmationBridge();
    initializeTheCheckoutPaymentComponentLayoutFix();
    scheduleTheCheckoutSelectedPaymentComponentLayoutFix();
    autoSelectPreferredPaymentMethod();
}

/**
 * Bridges external checkout confirmation CTAs to the selected MultiSafepay form.
 *
 * Some external checkouts submit through their own button (`#confirm_order`), which bypasses
 * the MultiSafepay form submit hook used to populate payment-component payloads
 * and tokenization values. When such a form is selected, submit it directly.
 *
 * @returns {void}
 */
function initializeTheCheckoutConfirmationBridge()
{
    const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();
    const theCheckoutConfirmButton = checkoutCompatibilityState.hasExternalConfirmationButton ?
        document.querySelector('#confirm_order[data-link-action="x-confirm-order"]') :
        null;

    if (
        multisafepayTheCheckoutConfirmButton &&
        multisafepayTheCheckoutConfirmButton !== theCheckoutConfirmButton &&
        multisafepayTheCheckoutConfirmButtonHandler
    ) {
        multisafepayTheCheckoutConfirmButton.removeEventListener(
            'click',
            multisafepayTheCheckoutConfirmButtonHandler,
            true
        );
        multisafepayTheCheckoutConfirmButton = null;
        multisafepayTheCheckoutConfirmButtonHandler = null;
    }

    if (!theCheckoutConfirmButton || multisafepayTheCheckoutConfirmButton === theCheckoutConfirmButton) {
        return;
    }

    multisafepayTheCheckoutConfirmButton = theCheckoutConfirmButton;
    multisafepayTheCheckoutConfirmButtonHandler = function (event) {
        if (!shouldBridgeTheCheckoutConfirmation()) {
            return;
        }

        const selectedMultiSafepayForm = getSelectedMultiSafepayPaymentForm();
        if (!selectedMultiSafepayForm.length) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();

        if (checkoutCompatibilityState.isTheCheckoutActive) {
            debugCheckoutSelection(
                'Running The Checkout validation before submitting the selected MultiSafepay form.',
                'info'
            );

            window.multisafepayCheckoutUtils.validateTheCheckoutBeforePayment().then(function (isCheckoutValid) {
                if (!isCheckoutValid) {
                    debugCheckoutSelection(
                        'The Checkout validation blocked the selected MultiSafepay form submit.',
                        'info'
                    );
                    return;
                }

                debugCheckoutSelection(
                    'Bridging external checkout confirmation button to the selected MultiSafepay form submit.',
                    'info'
                );

                submitSelectedMultiSafepayPaymentForm(getSelectedMultiSafepayPaymentForm());
            });
            return;
        }

        debugCheckoutSelection(
            'Bridging external checkout confirmation button to the selected MultiSafepay form submit.',
            'info'
        );

        submitSelectedMultiSafepayPaymentForm(selectedMultiSafepayForm);
    };

    multisafepayTheCheckoutConfirmButton.addEventListener(
        'click',
        multisafepayTheCheckoutConfirmButtonHandler,
        true
    );
}

/**
 * Keeps The Checkout payment component containers out of interrupted jQuery animation states.
 *
 * The Checkout shows the selected `.js-payment-option-form` with jQuery `slideDown()` and hides
 * the rest with `stop().hide()`. When customers switch payment methods while a MultiSafepay
 * payment component is rendering or validating, that animation can be interrupted and leave
 * inline styles such as `height: 20px` and zero padding on the selected form container.
 * Binding here is scoped to The Checkout's payment option markup so native checkout,
 * One Page Checkout PS, and non-The Checkout integrations keep their original behavior.
 *
 * @returns {void}
 */
function initializeTheCheckoutPaymentComponentLayoutFix()
{
    const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();
    if (!checkoutCompatibilityState.isTheCheckoutActive) {
        return;
    }

    $(document)
        .off('change.theCheckoutPaymentComponentLayout', ".tc-main-title input[name='payment-option']")
        .on('change.theCheckoutPaymentComponentLayout', ".tc-main-title input[name='payment-option']", function () {
            // Let The Checkout run its own toggle first, then normalize only the selected MultiSafepay form.
            scheduleTheCheckoutSelectedPaymentComponentLayoutFix();
        });
}

/**
 * Schedules layout cleanup after The Checkout runs its payment option toggling.
 *
 * The first pass catches the immediate radio-change toggle. The delayed pass catches the
 * common race where The Checkout's `slideDown()` and the payment component render/validation
 * complete slightly later and leave animation dimensions behind.
 *
 * @returns {void}
 */
function scheduleTheCheckoutSelectedPaymentComponentLayoutFix()
{
    const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();
    if (!checkoutCompatibilityState.isTheCheckoutActive) {
        return;
    }

    setTimeout(normalizeTheCheckoutSelectedPaymentComponentLayout, 0);
    setTimeout(normalizeTheCheckoutSelectedPaymentComponentLayout, 250);
}

/**
 * Clears stale inline animation styles from the selected The Checkout payment component form.
 *
 * The cleanup is intentionally narrow: it only runs for The Checkout's selected
 * `multisafepayofficial` option and only when the selected form contains a MultiSafepay
 * payment component. Hosted methods, wallets without embedded fields, other modules,
 * and other checkout implementations are skipped.
 *
 * @returns {void}
 */
function normalizeTheCheckoutSelectedPaymentComponentLayout()
{
    const selectedPaymentOptionInput = $(".tc-main-title input[name='payment-option']:checked").first();
    if (!selectedPaymentOptionInput.length) {
        return;
    }

    const selectedPaymentOptionContainer = selectedPaymentOptionInput.closest('.tc-main-title');
    if (selectedPaymentOptionContainer.attr('data-payment-module') !== 'multisafepayofficial') {
        return;
    }

    const paymentOptionId = selectedPaymentOptionInput.attr('id');
    const paymentOptionFormContainer = selectedPaymentOptionContainer.find('#pay-with-' + paymentOptionId + '-form').first();
    if (!paymentOptionFormContainer.length || !paymentOptionFormContainer.find("[id^='multisafepay-payment-component-']").length) {
        return;
    }

    // Clear only the inline properties written by jQuery's height animation so CSS can
    // calculate the component's natural height again.
    paymentOptionFormContainer
        .stop(true, true)
        .css({
            height: '',
            paddingTop: '',
            paddingBottom: '',
            marginTop: '',
            marginBottom: '',
            overflow: ''
        });

    if (!isElementDisplayed(paymentOptionFormContainer.get(0))) {
        paymentOptionFormContainer.show();
    }
}

/**
 * Returns whether an external checkout confirmation CTA must submit the MultiSafepay form.
 *
 * Only bridge flows that require MultiSafepay's own 'submit' hook, such as payment
 * components or tokenization-enabled forms.
 *
 * @returns {boolean}
 */
function shouldBridgeTheCheckoutConfirmation()
{
    const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();

    if (
        !checkoutCompatibilityState.hasExternalConfirmationButton ||
        (
            !checkoutCompatibilityState.isTheCheckoutActive &&
            !checkoutCompatibilityState.isOnePageCheckoutPsActive
        )
    ) {
        return false;
    }

    const selectedMultiSafepayForm = getSelectedMultiSafepayPaymentForm();
    if (!selectedMultiSafepayForm.length) {
        return false;
    }

    return selectedMultiSafepayForm.find("input[name='payload']").length > 0 ||
        selectedMultiSafepayForm.find("[id^='multisafepay-payment-component-']").length > 0 ||
        selectedMultiSafepayForm.hasClass('multisafepay-tokenization');
}

/**
 * Returns the selected MultiSafepay payment form for the current checkout radio.
 *
 * @returns {jQuery}
 */
function getSelectedMultiSafepayPaymentForm()
{
    const selectedPaymentOptionInput = $("input[name='payment-option']:checked").first();
    if (!selectedPaymentOptionInput.length) {
        return $();
    }

    const selectedGatewayCode = getGatewayCodeFromPaymentOptionInput(selectedPaymentOptionInput);
    if (!selectedGatewayCode) {
        return $();
    }

    const classicPaymentForm = getPaymentOptionFormContainer(selectedPaymentOptionInput)
        .find("form[id^='multisafepay-form-']")
        .first();
    if (classicPaymentForm.length) {
        return classicPaymentForm;
    }

    const nearbyPaymentForm = selectedPaymentOptionInput
        .closest('.payment-option, .payment-option-item, .js-payment-option, li')
        .find("form[id^='multisafepay-form-']")
        .first();
    if (nearbyPaymentForm.length) {
        return nearbyPaymentForm;
    }

    return $("form[id^='multisafepay-form-']").filter(function () {
        const gatewayInput = $(this).find("input[name='gateway']").first();
        const paymentFormGatewayCode = normalizeGatewayCode(gatewayInput.val());

        return gatewayInput.length && (
            paymentFormGatewayCode === selectedGatewayCode ||
            paymentFormGatewayCode.indexOf(selectedGatewayCode + '_') === 0
        );
    }).first();
}

/**
 * Submits the selected MultiSafepay payment form through its own 'submit' cycle.
 *
 * @param {jQuery} paymentForm
 * @returns {void}
 */
function submitSelectedMultiSafepayPaymentForm(paymentForm)
{
    const paymentFormElement = paymentForm.get(0);
    if (!paymentFormElement) {
        return;
    }

    if (typeof paymentFormElement.requestSubmit === 'function') {
        paymentFormElement.requestSubmit();
        return;
    }

    paymentForm.trigger('submit');
}

/**
 * Keeps the programmatic-selection flag active until the current event burst settles.
 *
 * @returns {void}
 */
function scheduleProgrammaticSelectionReset()
{
    if (multisafepayProgrammaticSelectionResetTimeoutId) {
        clearTimeout(multisafepayProgrammaticSelectionResetTimeoutId);
    }

    multisafepayProgrammaticSelectionResetTimeoutId = setTimeout(function () {
        multisafepayIsProgrammaticSelection = false;
        multisafepayProgrammaticSelectionResetTimeoutId = null;
    }, 0);
}

/**
 * Returns a stable signature for the currently rendered payment option radios.
 *
 * @returns {string}
 */
function getRenderedPaymentOptionsSignature()
{
    return $("input[name='payment-option']")
        .map(function () {
            return [
                this.id || '',
                this.value || '',
                this.getAttribute('data-module-name') || ''
            ].join(':');
        })
        .get()
        .join('|');
}

/**
 * Resets the bounded retry window only for a real payment-block refresh
 * or when the rendered payment option set actually changes.
 *
 * @param {boolean} forceReset
 * @returns {void}
 */
function resetAutoSelectionRetryWindowIfNeeded(forceReset)
{
    const currentPaymentOptionsSignature = getRenderedPaymentOptionsSignature();
    const hasPaymentOptionsSignatureChanged = currentPaymentOptionsSignature !== multisafepayLastPaymentOptionsSignature;

    if (!forceReset && !hasPaymentOptionsSignatureChanged) {
        return;
    }

    if (multisafepayAutoSelectionRetryTimeoutId) {
        clearTimeout(multisafepayAutoSelectionRetryTimeoutId);
        multisafepayAutoSelectionRetryTimeoutId = null;
    }

    multisafepayAutoSelectionRetryCount = 0;
    multisafepayLastPaymentOptionsSignature = currentPaymentOptionsSignature;
    multisafepayLastCheckedPaymentOptionRefreshSignature = '';
}

/**
 * Retries preferred gateway auto-selection for async checkout renders.
 *
 * Some checkouts mount payment options after the first ready/event cycle.
 * Keep retries bounded so genuine missing gateways still surface as warnings.
 *
 * @returns {void}
 */
function scheduleAutoSelectionRetry()
{
    if (multisafepayAutoSelectionRetryTimeoutId ||
        multisafepayAutoSelectionRetryCount >= multisafepayAutoSelectionMaxRetryCount) {
        return;
    }

    multisafepayAutoSelectionRetryTimeoutId = setTimeout(function () {
        multisafepayAutoSelectionRetryTimeoutId = null;
        multisafepayAutoSelectionRetryCount++;
        triggerCommonMethods(false);
    }, 200);
}

/**
 * Logs checkout selection messages only when module debug mode is enabled.
 *
 * @param {string} message
 * @param {string} [loggingType='log']
 * @returns {void}
 */
function debugCheckoutSelection(message, loggingType)
{
    window.multisafepayCheckoutUtils.logDebugMessage(message, getCheckoutDebugStatus(), loggingType);
}

/**
 * Adjusts payment method logo size for MultiSafepay checkout options.
 *
 * @returns {void}
 */
function adjustPaymentLogoimages()
{
    $("[id^='multisafepay-form-']").each(function () {
        $(this).parent().closest("div").prev("div").find("label img").css("height", "30px");
    });
}

/**
 * Toggles tokenization-dependent fields for payment methods.
 *
 * Uses a dedicated change-event 'namespace' so repeated checkout refreshes can
 * safely rebind this listener without stacking duplicate handlers.
 *
 * jQuery supports event namespaces since version 1.7, and the minimum
 * supported PrestaShop version (1.7.6.x) already ships with jQuery 2.2.4.
 *
 * @returns {void}
 */
function toggleTokenizationPaymentMethodsFields()
{
    $(".multisafepay-tokenization").each(function () {
        const paymentOptionFormId = $(this).attr("id");
        const tokenListInput = $(this).find(".form-group-token-list");
        if (tokenListInput.length > 0) {
            // Initial check on load checkout page.
            togglePaymentFields(paymentOptionFormId);
            tokenListInput
                .off('change.multisafepayTokenization')
                .on('change.multisafepayTokenization', function () {
                    // Check status on change.
                    togglePaymentFields(paymentOptionFormId);
                });
        }
    });
}

/**
 * Prevents accidental form submission on the Enter key press.
 *
 * Uses a dedicated event namespace so this handler can be safely removed and
 * rebound when checkout refreshes re-run the common setup, avoiding duplicate
 * keypress listeners on the same form.
 *
 * @returns {void}
 */
function preventSubmitOnKeyPress()
{
    $("[id^='multisafepay-form-']")
        .off('keypress.multisafepayPreventSubmit')
        .on(
            'keypress.multisafepayPreventSubmit',
            function (event) {
                if (event.which === 13) {
                    event.preventDefault();
                }
            }
        );
}

/**
 * Shows or hides payment fields depending on the selected tokenization option.
 *
 * @param {string} paymentOptionFormId
 * @returns {void}
 */
function togglePaymentFields(paymentOptionFormId)
{
    const selected_value = $("#" + paymentOptionFormId + " .form-group-token-list input[name='selectedToken']:checked").val();
    // If selected value is add a new one, show the direct fields including checkbox to save a new payment method
    if ('new' != selected_value) {
        $("#" + paymentOptionFormId + " .form-group").not(".form-group-token-list").hide();
    } else {
        $("#" + paymentOptionFormId + " .form-group").not(".form-group-token-list").show();
    }
}

/**
 * Persists the currently selected checkout gateway as the customer's last used method.
 *
 * @returns {void}
 */
function initializePaymentMethodSelectionPreference()
{
    const paymentOptionInputs = $("input[name='payment-option']");

    $(document)
        .off('mousedown.multisafepayPreferenceIntent touchstart.multisafepayPreferenceIntent', "input[name='payment-option'], label[for^='payment-option-']")
        .on('mousedown.multisafepayPreferenceIntent touchstart.multisafepayPreferenceIntent', "input[name='payment-option'], label[for^='payment-option-']", function () {
            // Record intent before the radio change event to avoid race conditions with theme scripts.
            if (!multisafepayIsProgrammaticSelection) {
                multisafepayHasManualPaymentSelection = true;
            }
        });

    paymentOptionInputs
        .off('click.multisafepayPreferenceIntent')
        .on('click.multisafepayPreferenceIntent', function () {
            if (!multisafepayIsProgrammaticSelection) {
                multisafepayHasManualPaymentSelection = true;
            }
        });

    $(document)
        .off('click.multisafepayPreferenceContainer', ".payment-option, .payment-option-item, .js-payment-option, label[for^='payment-option-']")
        .on('click.multisafepayPreferenceContainer', ".payment-option, .payment-option-item, .js-payment-option, label[for^='payment-option-']", function (event) {
            if (multisafepayIsProgrammaticSelection || $(event.target).is("input[name='payment-option']")) {
                return;
            }

            const selectionElement = this;

            setTimeout(function () {
                if (multisafepayIsProgrammaticSelection) {
                    return;
                }

                const selectedPaymentOptionInput = getPaymentOptionInputFromSelectionElement($(selectionElement));
                storePaymentOptionPreference(selectedPaymentOptionInput);
            }, 0);
        });

    paymentOptionInputs
        .off('change.multisafepayPreference')
        .on('change.multisafepayPreference', function () {
            // Auto-selection triggers change as well; ignore that cycle, so it is never persisted as user intent.
            if (multisafepayIsProgrammaticSelection) {
                return;
            }

            storePaymentOptionPreference($(this));
        });
}

/**
 * Finds the selected payment radio related to a clicked checkout wrapper.
 *
 * @param {jQuery} selectionElement
 * @returns {jQuery}
 */
function getPaymentOptionInputFromSelectionElement(selectionElement)
{
    if (selectionElement.is("label[for^='payment-option-']")) {
        const labelledPaymentOptionInput = $(document.getElementById(selectionElement.attr('for')));
        if (labelledPaymentOptionInput.is("input[name='payment-option']:checked")) {
            return labelledPaymentOptionInput;
        }
    }

    return selectionElement.find("input[name='payment-option']:checked").first();
}

/**
 * Stores a manually selected payment option as the customer's checkout preference.
 *
 * @param {jQuery} paymentOptionInput
 * @returns {void}
 */
function storePaymentOptionPreference(paymentOptionInput)
{
    if (!paymentOptionInput || !paymentOptionInput.length || !paymentOptionInput.prop('checked')) {
        return;
    }

    multisafepayHasManualPaymentSelection = true;

    const gatewayCode = getGatewayCodeFromPaymentOptionInput(paymentOptionInput);

    if (!gatewayCode) {
        // Preserve the user's manual choice of a non-MultiSafepay method
        // across checkout refreshes so the configured default does not win again.
        setLastUsedGatewayInStorage(multisafepayNonMultiSafepaySelectionMarker, multisafepayManualSelectionSource);
        debugCheckoutSelection('Stored manual selection for a non-MultiSafepay payment option.', 'info');
        return;
    }

    setLastUsedGatewayInStorage(gatewayCode, multisafepayManualSelectionSource);
    debugCheckoutSelection('Stored last used gateway: ' + gatewayCode, 'info');
}

/**
 * Auto-selects the preferred gateway in checkout.
 *
 * Priority: last used gateway first, configured default gateway second.
 * If the last used gateway is not available in the current checkout, fallback to configured default.
 *
 * @returns {void}
 */
function autoSelectPreferredPaymentMethod()
{
    const hasCheckedPaymentOption = $("input[name='payment-option']:checked").length > 0;

    // Keep honoring a manual choice while checkout still exposes an active selection.
    // If a refresh rebuilds the payment block without any checked option, allow the
    // stored MultiSafepay preference to restore that lost checkout state.
    if (multisafepayIsProgrammaticSelection || (multisafepayHasManualPaymentSelection && hasCheckedPaymentOption)) {
        return;
    }

    // Read storage-derived selection data once for this auto-selection cycle so
    // fallback logic can reuse both the effective preference and its manual source.
    const preferredGatewaySelection = getPreferredGatewaySelection();
    if (preferredGatewaySelection.hasStoredNonMultiSafepaySelection) {
        debugCheckoutSelection('Skipping MultiSafepay auto-selection because the customer manually chose a non-MultiSafepay payment option.', 'info');
        return;
    }

    let preferredGateway = preferredGatewaySelection.preferredGateway;
    const lastUsedGateway = preferredGatewaySelection.lastUsedGateway;

    if (!preferredGateway) {
        return;
    }

    let preferredPaymentOptionInput = getPaymentOptionInputByGateway(preferredGateway);

    if (!preferredPaymentOptionInput.length && lastUsedGateway) {
        const configuredDefaultGateway = getConfiguredDefaultGateway();
        if (configuredDefaultGateway && configuredDefaultGateway !== lastUsedGateway) {
            if (multisafepayAutoSelectionRetryCount < multisafepayAutoSelectionMaxRetryCount) {
                if (multisafepayAutoSelectionRetryCount === 0) {
                    const renderedPaymentOptionCount = $("input[name='payment-option']").length;

                    if (renderedPaymentOptionCount === 0) {
                        debugCheckoutSelection('Checkout payment options are not rendered yet. Waiting before retrying stored last used gateway "' + lastUsedGateway + '".', 'info');
                    } else {
                        debugCheckoutSelection('Stored last used gateway "' + lastUsedGateway + '" is not present yet among the currently rendered checkout payment options. Retrying.', 'info');
                    }
                }

                scheduleAutoSelectionRetry();
                return;
            }

            if (multisafepayAutoSelectionRetryCount === multisafepayAutoSelectionMaxRetryCount) {
                debugCheckoutSelection('Stored last used gateway "' + lastUsedGateway + '" is not present among the current checkout payment options. Trying configured default gateway instead: ' + configuredDefaultGateway, 'info');
            }

            preferredGateway = configuredDefaultGateway;
            preferredPaymentOptionInput = getPaymentOptionInputByGateway(preferredGateway);
        }
    }

    if (!preferredPaymentOptionInput.length) {
        const checkedPaymentOptionInput = $("input[name='payment-option']:checked").first();

        if (checkedPaymentOptionInput.length) {
            debugCheckoutSelection('Configured default gateway was not applied because another payment method is already selected in checkout.', 'info');
            return;
        }

        if (multisafepayAutoSelectionRetryCount < multisafepayAutoSelectionMaxRetryCount) {
            if (multisafepayAutoSelectionRetryCount === 0) {
                debugCheckoutSelection('Preferred gateway is not ready yet in current checkout. Retrying: ' + preferredGateway, 'info');
            }
            scheduleAutoSelectionRetry();
            return;
        }

        debugCheckoutSelection('Preferred gateway is not available in current checkout: ' + preferredGateway, 'warn');
        return;
    }

    multisafepayAutoSelectionRetryCount = 0;

    if (!preferredPaymentOptionInput.prop('checked')) {
        debugCheckoutSelection('Auto-selecting preferred gateway: ' + preferredGateway, 'info');
        multisafepayIsProgrammaticSelection = true;
        scheduleProgrammaticSelectionReset();
        preferredPaymentOptionInput.prop('checked', true).attr('checked', 'checked');
        notifyCheckoutAboutPaymentOptionSelection(preferredPaymentOptionInput);

        // Some checkout implementations can restore their server-rendered radio state
        // shortly after this first programmatic selection. Re-check once on the next
        // bounded retry cycle so the configured default can still win when no manual
        // customer choice was made.
        scheduleAutoSelectionRetry();
        return;
    }

    // OPC can render a preferred option as checked before its own selection state
    // is initialized, so notify it once with native events and reveal the form.
    if (shouldRefreshCheckedPaymentOption(preferredPaymentOptionInput)) {
        debugCheckoutSelection('Refreshing already checked payment option state: ' + preferredGateway, 'info');
        multisafepayIsProgrammaticSelection = true;
        scheduleProgrammaticSelectionReset();
        multisafepayLastCheckedPaymentOptionRefreshSignature = getCheckedPaymentOptionRefreshSignature(preferredPaymentOptionInput);
        notifyCheckoutAboutPaymentOptionSelection(preferredPaymentOptionInput);

        setTimeout(function () {
            revealSelectedPaymentComponentForm(preferredPaymentOptionInput);
        }, 0);
    }
}

/**
 * Notifies checkout scripts about a selected payment option using native DOM events.
 *
 * @param {jQuery} paymentOptionInput
 * @returns {void}
 */
function notifyCheckoutAboutPaymentOptionSelection(paymentOptionInput)
{
    const paymentOptionElement = paymentOptionInput.get(0);
    if (!paymentOptionElement) {
        return;
    }

    paymentOptionInput.prop('checked', true).attr('checked', 'checked');

    if (typeof paymentOptionElement.click === 'function') {
        paymentOptionElement.click();
    } else {
        paymentOptionElement.dispatchEvent(createBubblingEvent('click'));
    }

    paymentOptionElement.dispatchEvent(createBubblingEvent('change'));
}

/**
 * Creates a bubbling DOM event with a fallback for older browsers.
 *
 * @param {string} eventName
 * @returns {Event}
 */
function createBubblingEvent(eventName)
{
    try {
        return new Event(eventName, {bubbles: true, cancelable: true});
    } catch (error) {
        const event = document.createEvent('Event');
        // noinspection JSDeprecatedSymbols: fallback for browsers without a constructable Event API.
        event.initEvent(eventName, true, true);

        return event;
    }
}

/**
 * Returns the checkout form container linked to a payment option radio.
 *
 * @param {jQuery} paymentOptionInput
 * @returns {jQuery}
 */
function getPaymentOptionFormContainer(paymentOptionInput)
{
    const paymentOptionId = paymentOptionInput.attr('id');
    if (!paymentOptionId) {
        return $();
    }

    return $('#pay-with-' + paymentOptionId + '-form');
}

/**
 * Returns whether a payment option form contains a payment component.
 *
 * @param {jQuery} paymentOptionFormContainer
 * @returns {boolean}
 */
function hasPaymentComponentForm(paymentOptionFormContainer)
{
    return paymentOptionFormContainer.find("[id^='multisafepay-payment-component-'], input[name='payload']").length > 0;
}

/**
 * Returns whether an element and its parents are displayed.
 *
 * @param {HTMLElement} element
 * @returns {boolean}
 */
function isElementDisplayed(element)
{
    let currentElement = element;

    while (currentElement && currentElement.nodeType === Node.ELEMENT_NODE) {
        const currentElementStyle = window.getComputedStyle(currentElement);

        if (currentElementStyle.display === 'none' || currentElementStyle.visibility === 'hidden') {
            return false;
        }

        currentElement = currentElement.parentElement;
    }

    return true;
}

/**
 * Returns a signature for the last checked payment option refresh.
 *
 * @param {jQuery} paymentOptionInput
 * @returns {string}
 */
function getCheckedPaymentOptionRefreshSignature(paymentOptionInput)
{
    return [
        getRenderedPaymentOptionsSignature(),
        paymentOptionInput.attr('id') || '',
        getGatewayCodeFromPaymentOptionInput(paymentOptionInput)
    ].join('|');
}

/**
 * Returns whether OPC needs a selection refresh for an already checked payment option.
 *
 * @param {jQuery} paymentOptionInput
 * @returns {boolean}
 */
function shouldRefreshCheckedPaymentOption(paymentOptionInput)
{
    const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();
    if (!checkoutCompatibilityState.isOnePageCheckoutPsActive || !paymentOptionInput.prop('checked')) {
        return false;
    }

    const refreshSignature = getCheckedPaymentOptionRefreshSignature(paymentOptionInput);
    if (multisafepayLastCheckedPaymentOptionRefreshSignature === refreshSignature) {
        return false;
    }

    if (isOnePageCheckoutPsOrderButtonWaitingForSelection()) {
        return true;
    }

    const paymentOptionFormContainer = getPaymentOptionFormContainer(paymentOptionInput);
    if (!paymentOptionFormContainer.length || !hasPaymentComponentForm(paymentOptionFormContainer)) {
        return false;
    }

    const paymentOptionFormContainerElement = paymentOptionFormContainer.get(0);
    if (!paymentOptionFormContainerElement || !isElementDisplayed(paymentOptionFormContainerElement)) {
        return true;
    }

    const paymentComponentElement = paymentOptionFormContainer.find("[id^='multisafepay-payment-component-']").get(0);
    return Boolean(paymentComponentElement && !isElementDisplayed(paymentComponentElement));
}

/**
 * Returns whether OPC still shows its payment-required state despite a checked radio.
 *
 * @returns {boolean}
 */
function isOnePageCheckoutPsOrderButtonWaitingForSelection()
{
    // One Page Checkout PS has used different order button IDs across versions.
    const orderButton = document.getElementById('btn-placer_order') || document.getElementById('btn_place_order');
    return Boolean(orderButton && orderButton.disabled);
}

/**
 * Shows the selected payment component form if OPC kept it hidden after selection events.
 *
 * @param {jQuery} paymentOptionInput
 * @returns {void}
 */
function revealSelectedPaymentComponentForm(paymentOptionInput)
{
    if (!paymentOptionInput.prop('checked')) {
        return;
    }

    const paymentOptionFormContainer = getPaymentOptionFormContainer(paymentOptionInput);
    if (!paymentOptionFormContainer.length || !hasPaymentComponentForm(paymentOptionFormContainer)) {
        return;
    }

    const paymentOptionFormContainerElement = paymentOptionFormContainer.get(0);
    if (paymentOptionFormContainerElement && !isElementDisplayed(paymentOptionFormContainerElement)) {
        paymentOptionFormContainer.show();
    }
}

/**
 * Finds the checkout payment radio input that matches a gateway code.
 *
 * @param {string} gatewayCode
 * @returns {jQuery}
 */
function getPaymentOptionInputByGateway(gatewayCode)
{
    const normalizedGatewayCode = normalizeGatewayCode(gatewayCode);
    if (!normalizedGatewayCode) {
        return $();
    }

    let paymentOptionInput = $();

    $("input[name='payment-option']").each(function () {
        const currentPaymentOptionInput = $(this);
        const currentGatewayCode = getGatewayCodeFromPaymentOptionInput(currentPaymentOptionInput);

        if (currentGatewayCode === normalizedGatewayCode) {
            paymentOptionInput = currentPaymentOptionInput;
            return false;
        }
    });

    return paymentOptionInput;
}

/**
 * Resolves the hidden gateway value from a checkout payment option input.
 *
 * @param {jQuery} paymentOptionInput
 * @returns {string}
 */
function getGatewayCodeFromPaymentOptionInput(paymentOptionInput)
{
    const fallbackGatewayCode = getGatewayCodeFromPaymentOptionMetadata(paymentOptionInput);
    const paymentOptionId = paymentOptionInput.attr('id');
    if (!paymentOptionId) {
        return fallbackGatewayCode;
    }

    const paymentOptionFormContainer = $("#pay-with-" + paymentOptionId + "-form");
    if (!paymentOptionFormContainer.length) {
        return fallbackGatewayCode;
    }

    const paymentForm = paymentOptionFormContainer.find('form').first();
    if (!paymentForm.length) {
        return fallbackGatewayCode;
    }

    const formAction = String(paymentForm.attr('action') || '');
    const formId = String(paymentForm.attr('id') || '');
    // Checkout radios include methods from other modules; identify MultiSafepay forms
    // using signals that survive both query-based and rewritten module URLs.
    const isMultiSafepayPaymentOption =
        formAction.indexOf('module=multisafepayofficial') !== -1 ||
        formAction.indexOf('/module/multisafepayofficial/') !== -1 ||
        formId.indexOf('multisafepay-form-') === 0;

    if (!isMultiSafepayPaymentOption) {
        return '';
    }

    const gatewayInput = paymentOptionFormContainer.find("input[name='gateway']").first();
    if (gatewayInput.length) {
        return normalizeGatewayCode(gatewayInput.val());
    }

    // Some themes expose module metadata but omit the hidden gateway field; keep this fallback.
    return fallbackGatewayCode;
}

/**
 * Extracts a MultiSafepay gateway code directly from payment radio metadata.
 *
 * The Checkout renders radios without the classic pay-with-<id>-form wrapper
 * but keeps the gateway in data-module-name using the pattern GATEWAY-hash.
 * Preserve the original case so lowercased native module names are ignored.
 *
 * @param {jQuery} paymentOptionInput
 * @returns {string}
 */
function getGatewayCodeFromPaymentOptionMetadata(paymentOptionInput)
{
    const moduleName = String(paymentOptionInput.attr('data-module-name') || '').trim();
    const gatewayCodeMatch = moduleName.match(/^([A-Z0-9_]+)(?:-[a-z0-9]{5})?$/);

    if (!gatewayCodeMatch || !gatewayCodeMatch[1]) {
        return '';
    }

    return normalizeGatewayCode(gatewayCodeMatch[1]);
}

/**
 * Returns the preferred gateway selection data for checkout.
 *
 * Priority: stored last used gateway first, configured default gateway second.
 * If no default gateway is configured in admin, auto-selection stays disabled.
 *
 * @returns {{preferredGateway: string, lastUsedGateway: string, hasStoredNonMultiSafepaySelection: boolean}}
 */
function getPreferredGatewaySelection()
{
    // Normalize storage first so selection priority runs on a clean, current-cart scope.
    synchronizeStoredSelectionWithCurrentDefault();

    const configuredDefaultGateway = getConfiguredDefaultGateway();

    if (!configuredDefaultGateway) {
        return {
            preferredGateway: '',
            lastUsedGateway: '',
            hasStoredNonMultiSafepaySelection: false
        };
    }

    const hasStoredNonMultiSafepayManualSelection = hasStoredNonMultiSafepaySelection();
    const lastUsedGateway = getLastUsedGatewayFromStorage();

    return {
        // Return both values so callers can use the selected priority result and
        // still know whether it originally came from a manual last-used gateway.
        preferredGateway: lastUsedGateway || configuredDefaultGateway,
        lastUsedGateway: lastUsedGateway,
        hasStoredNonMultiSafepaySelection: hasStoredNonMultiSafepayManualSelection
    };
}

/**
 * Returns the configured default gateway code from checkout JS configuration.
 *
 * @returns {string}
 */
function getConfiguredDefaultGateway()
{
    const checkoutSelectionConfig = getCheckoutSelectionConfig();
    return normalizeGatewayCode(checkoutSelectionConfig.defaultGateway || '');
}

/**
 * Returns checkout configuration injected from PHP.
 *
 * @returns {Object}
 */
function getCheckoutSelectionConfig()
{
    return window.multisafepayCheckoutSelectionConfig || {};
}

/**
 * Returns whether checkout debug logs are enabled.
 *
 * @returns {boolean}
 */
function getCheckoutDebugStatus()
{
    const checkoutSelectionConfig = getCheckoutSelectionConfig();
    const debugValue = checkoutSelectionConfig.debug;

    return debugValue === true || debugValue === 1 || debugValue === '1' || debugValue === 'true';
}

/**
 * Returns the storage key used to persist the last used gateway.
 *
 * @returns {string}
 */
function getGatewayStorageKey()
{
    const checkoutSelectionConfig = getCheckoutSelectionConfig();
    return checkoutSelectionConfig.storageKey || 'multisafepayofficial_last_gateway';
}

/**
 * Returns the storage key used to persist the source of the last used gateway.
 *
 * @returns {string}
 */
function getGatewayStorageSourceKey()
{
    return getGatewayStorageKey() + '_source';
}

/**
 * Returns the storage key used to persist the configured default gateway.
 *
 * @returns {string}
 */
function getDefaultGatewayStorageKey()
{
    return getGatewayStorageKey() + '_default';
}

/**
 * Returns the shop/customer storage key prefix derived from the current cart-scoped key.
 *
 * @returns {string}
 */
function getGatewayStorageScopePrefix()
{
    const gatewayStorageKey = getGatewayStorageKey();
    // Expected key shape: multisafepayofficial_last_gateway_<shopId>_<customerId>_<cartId>.
    // Capture shop/customer scope to prune only previous carts for this shopper.
    const keyMatch = gatewayStorageKey.match(/^(multisafepayofficial_last_gateway_\d+_\d+)_\d+$/);

    if (!keyMatch || !keyMatch[1]) {
        return '';
    }

    return keyMatch[1];
}

/**
 * Returns the source of the last used gateway from localStorage.
 *
 * @returns {string}
 */
function getLastUsedGatewaySourceFromStorage()
{
    try {
        return String(window.localStorage.getItem(getGatewayStorageSourceKey()) || '');
    } catch (error) {
        return '';
    }
}

/**
 * Returns the raw stored checkout selection value from localStorage.
 *
 * @returns {string}
 */
function getStoredGatewaySelectionValue()
{
    try {
        return normalizeGatewayCode(window.localStorage.getItem(getGatewayStorageKey()) || '');
    } catch (error) {
        return '';
    }
}

/**
 * Returns whether the current cart stores a manual non-MultiSafepay selection.
 *
 * @returns {boolean}
 */
function hasStoredNonMultiSafepaySelection()
{
    return getStoredGatewaySelectionValue() === multisafepayNonMultiSafepaySelectionMarker &&
        getLastUsedGatewaySourceFromStorage() === multisafepayManualSelectionSource;
}

/**
 * Removes stale checkout preference entries from previous carts.
 *
 * @returns {void}
 */
function cleanupStaleGatewaySelectionStorage()
{
    const gatewayStorageScopePrefix = getGatewayStorageScopePrefix();
    if (!gatewayStorageScopePrefix) {
        return;
    }

    const currentGatewayStorageKey = getGatewayStorageKey();
    const currentGatewayStorageSourceKey = getGatewayStorageSourceKey();
    const currentDefaultGatewayStorageKey = getDefaultGatewayStorageKey();
    const staleKeys = [];

    try {
        // Use indexed access because localStorage has no native filter iterator.
        for (let i = 0; i < window.localStorage.length; i++) {
            const key = window.localStorage.key(i);
            if (!key) {
                continue;
            }

            const isCurrentKey = key === currentGatewayStorageKey ||
                key === currentGatewayStorageSourceKey ||
                key === currentDefaultGatewayStorageKey;
            if (isCurrentKey) {
                continue;
            }

            // Never touch keys outside this shopper scope.
            if (key.indexOf(gatewayStorageScopePrefix + '_') !== 0) {
                continue;
            }

            const keyRemainder = key.substring((gatewayStorageScopePrefix + '_').length);
            // Remove only per-cart entries from the convention: <cartId>[_source|_default].
            if (/^\d+(?:_(?:source|default))?$/.test(keyRemainder)) {
                staleKeys.push(key);
            }
        }

        staleKeys.forEach(function (staleKey) {
            window.localStorage.removeItem(staleKey);
        });

        if (staleKeys.length) {
            debugCheckoutSelection('Cleared stale checkout storage keys: ' + staleKeys.length, 'info');
        }
    } catch (error) {
        debugCheckoutSelection('Could not clean stale checkout storage keys: ' + (error && error.message ? error.message : error), 'warn');
    }
}

/**
 * Keeps stored checkout selection aligned with default gateway updates.
 *
 * @returns {void}
 */
function synchronizeStoredSelectionWithCurrentDefault()
{
    const configuredDefaultGateway = getConfiguredDefaultGateway();
    const gatewayStorageKey = getGatewayStorageKey();
    const sourceStorageKey = getGatewayStorageSourceKey();
    const defaultGatewayStorageKey = getDefaultGatewayStorageKey();
    // A cart/customer storage key or configured default change means the previous
    // synchronization result is no longer reusable for this page lifecycle.
    const synchronizationSignature = gatewayStorageKey + '|' + configuredDefaultGateway;

    if (multisafepayLastSelectionSyncSignature === synchronizationSignature) {
        // This checkout state was already normalized during a previous refresh,
        // so skip the same storage cleanup/write cycle.
        return;
    }

    // Keep storage bounded before reading any preferred gateway candidate.
    cleanupStaleGatewaySelectionStorage();

    try {
        const storedDefaultGateway = normalizeGatewayCode(window.localStorage.getItem(defaultGatewayStorageKey) || '');
        const storedLastUsedGateway = normalizeGatewayCode(window.localStorage.getItem(gatewayStorageKey) || '');

        if (
            storedDefaultGateway &&
            configuredDefaultGateway &&
            storedDefaultGateway !== configuredDefaultGateway &&
            storedLastUsedGateway === storedDefaultGateway
        ) {
            // Stored preference still mirrors an outdated default; clear it to let the new default apply.
            window.localStorage.removeItem(gatewayStorageKey);
            window.localStorage.removeItem(sourceStorageKey);
            debugCheckoutSelection('Default gateway changed in admin. Clearing last used gateway equal to previous default: ' + storedDefaultGateway, 'info');
        }

        if (configuredDefaultGateway) {
            window.localStorage.setItem(defaultGatewayStorageKey, configuredDefaultGateway);
        } else {
            window.localStorage.removeItem(defaultGatewayStorageKey);
        }
    } catch (error) {
        debugCheckoutSelection('Could not synchronize stored gateway with configured default: ' + (error && error.message ? error.message : error), 'warn');
    }

    // Mark this storage/default combination as synchronized for later
    // triggerCommonMethods() runs in the same page lifecycle.
    multisafepayLastSelectionSyncSignature = synchronizationSignature;
}

/**
 * Reads the last used gateway from localStorage.
 *
 * @returns {string}
 */
function getLastUsedGatewayFromStorage()
{
    try {
        const storedLastUsedGateway = getStoredGatewaySelectionValue();

        if (!storedLastUsedGateway || storedLastUsedGateway === multisafepayNonMultiSafepaySelectionMarker) {
            return '';
        }

        // Only values explicitly marked as manual are eligible for priority over merchant default.
        if (getLastUsedGatewaySourceFromStorage() !== multisafepayManualSelectionSource) {
            return '';
        }

        return storedLastUsedGateway;
    } catch (error) {
        debugCheckoutSelection('Could not read last used gateway from storage: ' + (error && error.message ? error.message : error), 'warn');
        return '';
    }
}

/**
 * Stores the provided gateway code as the last used method in localStorage.
 *
 * @param {string} gatewayCode
 * @param {string} [source='manual']
 * @returns {void}
 */
function setLastUsedGatewayInStorage(gatewayCode, source)
{
    const normalizedGatewayCode = normalizeGatewayCode(gatewayCode);
    if (!normalizedGatewayCode) {
        return;
    }

    try {
        window.localStorage.setItem(getGatewayStorageKey(), normalizedGatewayCode);
        window.localStorage.setItem(getGatewayStorageSourceKey(), source || multisafepayManualSelectionSource);
    } catch (error) {
        debugCheckoutSelection('Could not write last used gateway to storage: ' + (error && error.message ? error.message : error), 'warn');
    }
}

/**
 * Normalizes gateway values to uppercase string format.
 *
 * @param {*} gatewayCode
 * @returns {string}
 */
function normalizeGatewayCode(gatewayCode)
{
    if (!gatewayCode) {
        return '';
    }

    return String(gatewayCode).toUpperCase();
}
