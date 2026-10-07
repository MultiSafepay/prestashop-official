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
const multisafepayPaymentComponentInstances = new Map();
let multisafepayNativeCheckoutButtonIsEnabled = false;

const MultiSafepayPaymentComponent = function (config, gateway, paymentComponentId) {

    let paymentComponent = null;
    // Null means the SDK has not emitted an onValidation state for this component yet.
    let paymentComponentIsValid = null;
    let recurringPaymentComponentLayoutObserver = null;
    let paymentComponentIsLoaded = false;
    const paymentComponentElement = document.getElementById('multisafepay-payment-component-' + paymentComponentId);
    const paymentComponentEventSuffix = paymentComponentIdAsEventSuffix(paymentComponentId);
    const paymentComponentValidationEventName = 'multisafepayPaymentComponentValidation';
    const checkoutConfirmationButtonLockDataKey = 'multisafepayPaymentComponentButtonLock';
    const checkoutConfirmationButtonDisabledAttribute = 'data-multisafepay-payment-component-disabled';
    const paymentComponentIdAsString = String(paymentComponentId);
    // `#conditions-to-approve` is the native checkout/OPC conditions container ID.
    // `.js-conditions-to-approve` is the companion class on that same native checkout/OPC container.
    const checkoutTermsCheckboxSelector = '#conditions-to-approve input[type="checkbox"], .js-conditions-to-approve input[type="checkbox"]';
    // OPC only: when "Settings > General > Confirm delivery address before checkout"
    // (OPC_CONFIRM_ADDRESS) renders this "payment-step" checkbox.
    const opcCheckoutUtils = window.multisafepayCheckoutUtils.onePageCheckoutPs;
    const opcDeliveryAddressConfirmationSelector = opcCheckoutUtils.deliveryAddressConfirmationSelector;

    this.construct = function (config, gateway, paymentComponentId) {
        initializePaymentComponent();
        onPaymentComponentLayoutChange();
        onSubmitCheckoutForm();
        synchronizeCheckoutConfirmationButtonWithPaymentComponentState();
    };

    const getPaymentComponent = function () {
        if ( ! paymentComponent ) {
            paymentComponent = getNewPaymentComponent();
        }

        return paymentComponent;
    };

    const getNewPaymentComponent = function () {
        return new MultiSafepay(
            {
                env: config.env,
                apiToken: config.apiToken,
                order: config.orderData,
                recurring: config.recurring
            }
        );
    };

    const insertPayload = function (payload) {
        $("#multisafepay-form-" + paymentComponentId + " input[name='payload']").val(payload);
    };

    const insertTokenize = function (tokenize) {
        $("#multisafepay-form-" + paymentComponentId + " input[name='tokenize']").val(tokenize);
    };

    const removePayload = function () {
        $("#multisafepay-form-" + paymentComponentId + " input[name='payload']").val('');
    };

    /**
     * Returns every checkout confirmation button that can submit the selected payment form.
     *
     * Native checkout, One Page Checkout PS, and The Checkout use different button IDs.
     *
     * @returns {string[]}
     */
    const getCheckoutConfirmationButtonSelectors = function () {
        const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();
        const checkoutConfirmationButtonSelectors = ['#payment-confirmation button'];

        if (checkoutCompatibilityState.isTheCheckoutActive) {
            checkoutConfirmationButtonSelectors.push('#tc-payment-confirmation button');
        }

        if (checkoutCompatibilityState.hasExternalConfirmationButton) {
            checkoutConfirmationButtonSelectors.push('#confirm_order');
        }

        if (checkoutCompatibilityState.isOnePageCheckoutPsActive) {
            checkoutConfirmationButtonSelectors.push('#btn-placer_order', '#btn_place_order');
        }

        if (checkoutCompatibilityState.isNativeOnePageCheckoutActive) {
            checkoutConfirmationButtonSelectors.push('#opc-pay-button');
        }

        return checkoutConfirmationButtonSelectors;
    };

    /**
     * Marks the shared checkout CTA (Call to Action) as blocked by this Payment
     * Component validation state.
     *
     * The marker lets MultiSafepay later release only Payment Component locks.
     *
     * @param {jQuery} checkoutConfirmationButton
     * @returns {void}
     */
    const markCheckoutConfirmationButtonLocked = function (checkoutConfirmationButton) {
        checkoutConfirmationButton
            .data(checkoutConfirmationButtonLockDataKey, true)
            .attr(checkoutConfirmationButtonDisabledAttribute, paymentComponentIdAsString);
    };

    /**
     * Returns whether checkout-level rules still block order confirmation.
     *
     * These checks are independent of the Payment Component validation state.
     *
     * @returns {boolean}
     */
    const shouldKeepCheckoutConfirmationButtonDisabled = function () {
        // Native checkout, OPC, and The Checkout all require a selected payment option.
        if (!$("input[name='payment-option']:checked").length) {
            return true;
        }

        // Only checkout-level terms inside the condition container can block the CTA here.
        // Payment-method checkboxes, such as card-specific options, must not block another selected method.
        if (
            $(checkoutTermsCheckboxSelector)
                .not(':checked')
                .length > 0
        ) {
            return true;
        }

        // One Page Checkout PS can optionally render an address confirmation checkbox in the payment step.
        const deliveryAddressConfirmation = $(opcDeliveryAddressConfirmationSelector);

        // OPC only: keep the final order button disabled while that optional
        // payment-step checkbox exists and remains unchecked.
        return deliveryAddressConfirmation.length > 0 && !deliveryAddressConfirmation.is(':checked');
    };

    /**
     * Releases a Payment Component validation lock from the shared checkout CTA.
     *
     * If checkout-level rules still block the order, the lock is kept so it can be retried later.
     *
     * @param {jQuery} checkoutConfirmationButton
     * @param {boolean} checkoutMustKeepConfirmationButtonDisabled
     * @param {boolean} releaseAnyPaymentComponentLock
     * @returns {void}
     */
    const releaseCheckoutConfirmationButtonLock = function (
        checkoutConfirmationButton,
        checkoutMustKeepConfirmationButtonDisabled,
        releaseAnyPaymentComponentLock
    ) {
        const hasPaymentComponentLock = checkoutConfirmationButton.data(checkoutConfirmationButtonLockDataKey);
        const paymentComponentDisabledOwner = checkoutConfirmationButton.attr(checkoutConfirmationButtonDisabledAttribute);

        if (
            !hasPaymentComponentLock ||
            (
                !releaseAnyPaymentComponentLock &&
                paymentComponentDisabledOwner !== paymentComponentIdAsString
            )
        ) {
            return;
        }

        if (checkoutMustKeepConfirmationButtonDisabled) {
            return;
        }

        checkoutConfirmationButton
            .removeData(checkoutConfirmationButtonLockDataKey)
            .removeAttr(checkoutConfirmationButtonDisabledAttribute);

        checkoutConfirmationButton
            .removeClass('disabled')
            .prop('disabled', false)
            .removeAttr('disabled');
    };

    /**
     * Applies or releases the Payment Component validation lock on every checkout CTA.
     *
     * Releasing first checks checkout-level blockers so MultiSafepay does not bypass them.
     *
     * @param {boolean} disabled
     * @param {boolean} [releaseAnyPaymentComponentLock=false]
     * @returns {void}
     */
    const setCheckoutConfirmationButtonDisabled = function (disabled, releaseAnyPaymentComponentLock) {
        if (!paymentComponentElement.isConnected) {
            return;
        }
        const checkoutConfirmationButtons = $(getCheckoutConfirmationButtonSelectors().join(', '));

        if (disabled) {
            checkoutConfirmationButtons
                .each(function () {
                    markCheckoutConfirmationButtonLocked($(this));
                })
                .addClass('disabled')
                .prop('disabled', true)
                .attr('disabled', 'disabled');
            return;
        }

        const checkoutMustKeepConfirmationButtonDisabled = shouldKeepCheckoutConfirmationButtonDisabled();

        checkoutConfirmationButtons.each(function () {
            releaseCheckoutConfirmationButtonLock(
                $(this),
                checkoutMustKeepConfirmationButtonDisabled || (this.id === 'opc-pay-button' && !multisafepayNativeCheckoutButtonIsEnabled),
                releaseAnyPaymentComponentLock === true
            );
        });
    };

    /**
     * Reapplies the requested CTA lock state after checkout scripts finish their own handlers.
     *
     * OPC and The Checkout can update the confirmation button shortly after a validation event.
     *
     * @param {boolean} disabled
     * @param {boolean} [releaseAnyPaymentComponentLock=false]
     * @returns {void}
     */
    const setCheckoutConfirmationButtonDisabledWithDelay = function (disabled, releaseAnyPaymentComponentLock) {
        setCheckoutConfirmationButtonDisabled(disabled, releaseAnyPaymentComponentLock);
        // Native OPC reports its own gate through opcFormValidated; do not replay stale
        // button decisions after a newer validation/selection event.
        if (window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isNativeOnePageCheckoutActive) {
            return;
        }
        setTimeout(function () {
            setCheckoutConfirmationButtonDisabled(disabled, releaseAnyPaymentComponentLock);
        }, 0);
        setTimeout(function () {
            setCheckoutConfirmationButtonDisabled(disabled, releaseAnyPaymentComponentLock);
        }, 250);
    };

    /**
     * Ensures hidden or non-selected payment components cannot change the visible checkout CTA.
     *
     * @returns {boolean}
     */
    const isCurrentPaymentComponentSelected = function () {
        if (typeof window.getSelectedMultiSafepayPaymentForm !== 'function') {
            return true;
        }

        const selectedPaymentForm = window.getSelectedMultiSafepayPaymentForm();

        return selectedPaymentForm.length && selectedPaymentForm.attr('id') === 'multisafepay-form-' + paymentComponentId;
    };

    /**
     * Reconciles the shared checkout CTA with the latest SDK validation state.
     *
     * A non-selected component releases only its own lock; the selected valid component
     * may also clear a stale lock left by a previously selected Payment Component.
     *
     * @returns {void}
     */
    const synchronizeCheckoutConfirmationButtonWithPaymentComponentState = function () {
        if (!paymentComponentElement.isConnected) {
            return;
        }
        if (!isCurrentPaymentComponentSelected()) {
            setCheckoutConfirmationButtonDisabledWithDelay(false);
            return;
        }

        if (
            window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isNativeOnePageCheckoutActive &&
            !paymentComponentIsLoaded
        ) {
            setCheckoutConfirmationButtonDisabledWithDelay(true);
            return;
        }

        if (paymentComponentIsValid === null) {
            if (window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isNativeOnePageCheckoutActive) {
                setCheckoutConfirmationButtonDisabledWithDelay(true);
            }
            return;
        }

        setCheckoutConfirmationButtonDisabledWithDelay(!paymentComponentIsValid, true);
    };

    /**
     * Normalizes the SDK validation callback payload into a boolean.
     *
     * Current components emit `{valid: boolean}`, while the fallbacks keep older shapes safe.
     *
     * @param {boolean|Object} validationState
     * @returns {boolean}
     */
    const normalizePaymentComponentValidationState = function (validationState) {
        if (typeof validationState === 'boolean') {
            return validationState;
        }

        if (validationState && typeof validationState.valid === 'boolean') {
            return validationState.valid;
        }

        if (validationState && typeof validationState.isValid === 'boolean') {
            return validationState.isValid;
        }

        return !getPaymentComponent().hasErrors();
    };

    /**
     * Stores the latest SDK validation state and mirrors it to the checkout submit button.
     *
     * @param {boolean|Object} validationState
     * @returns {void}
     */
    const updatePaymentComponentValidationState = function (validationState) {
        paymentComponentIsValid = normalizePaymentComponentValidationState(validationState);

        if (!paymentComponentIsValid) {
            $(document).trigger(paymentComponentValidationEventName, [paymentComponentId]);
        }

        synchronizeCheckoutConfirmationButtonWithPaymentComponentState();
    };

    /**
     * Forces a checkout/payment-component wrapper to grow with its content.
     *
     * External checkout modules can keep stale heights around the payment form after switching methods.
     *
     * @param {HTMLElement} element
     * @returns {void}
     */
    const forceElementAutoHeight = function (element) {
        if (!element) {
            return;
        }

        element.style.setProperty('height', 'auto', 'important');
        element.style.setProperty('overflow', 'visible', 'important');
    };

    /**
     * Restores the visible checkout containers around the selected payment component.
     *
     * The SDK accordion can be open while the checkout wrapper keeps the old collapsed height.
     *
     * @returns {void}
     */
    const normalizeSelectedPaymentComponentContainerLayout = function () {
        const paymentForm = $('#multisafepay-form-' + paymentComponentId);

        forceElementAutoHeight(paymentForm.closest('.js-payment-option-form').get(0));
        forceElementAutoHeight(paymentForm.get(0));
        forceElementAutoHeight(paymentForm.closest('.form-group').get(0));
        forceElementAutoHeight(paymentForm.closest('.module_payment_container.selected').get(0));
    };

    /**
     * Restores recurring card fields when checkout switching leaves an expanded item collapsed.
     *
     * Any component can emit onValidation(false) while another component is hidden. If the SDK
     * keeps the new-card option expanded during that sequence, the body can retain `height: 0`
     * when the customer returns and hides the iframes.
     *
     * @returns {void}
     */
    const normalizeExpandedRecurringPaymentComponentLayout = function () {
        if (!paymentComponentElement.isConnected) {
            return;
        }
        if (isCurrentPaymentComponentSelected()) {
            normalizeSelectedPaymentComponentContainerLayout();
        }

        $('#multisafepay-payment-component-' + paymentComponentId)
            .find('.msp-ui-recurring-single-payment .msp-ui-radio-button-item-body')
            .each(function () {
                const recurringPaymentBody = this;
                const recurringPaymentOption = $(recurringPaymentBody).closest('.msp-ui-recurring-single-payment');

                if (!recurringPaymentOption.hasClass('expanded')) {
                    recurringPaymentBody.style.removeProperty('height');
                    recurringPaymentBody.style.removeProperty('overflow');
                    return;
                }

                // Checkout scripts or the SDK can reapply `height: 0px` while the component is hidden.
                // Force the expanded body open without relying on scrollHeight, which can be
                // 0 while checkout integrations are toggling visibility.
                if (
                    recurringPaymentBody.style.getPropertyValue('height') !== 'auto' ||
                    recurringPaymentBody.style.getPropertyPriority('height') !== 'important' ||
                    recurringPaymentBody.style.getPropertyValue('overflow') !== 'visible' ||
                    recurringPaymentBody.style.getPropertyPriority('overflow') !== 'important'
                ) {
                    recurringPaymentBody.style.setProperty('height', 'auto', 'important');
                    recurringPaymentBody.style.setProperty('overflow', 'visible', 'important');
                }
            });
    };

    /**
     * Runs the layout fix immediately and after the current checkout animation/event burst.
     *
     * @returns {void}
     */
    const scheduleExpandedRecurringPaymentComponentLayoutFix = function () {
        normalizeExpandedRecurringPaymentComponentLayout();
        setTimeout(normalizeExpandedRecurringPaymentComponentLayout, 0);
        setTimeout(normalizeExpandedRecurringPaymentComponentLayout, 250);
    };

    /**
     * Rechecks layout and submit-button state after checkout scripts process a payment change.
     *
     * A component can become selected after its latest onValidation callback already fired.
     *
     * @returns {void}
     */
    const synchronizePaymentComponentLayoutAndCheckoutState = function () {
        scheduleExpandedRecurringPaymentComponentLayoutFix();
        synchronizeCheckoutConfirmationButtonWithPaymentComponentState();
    };

    /**
     * Watches the SDK accordion for late class/style changes made after checkout switching.
     *
     * This keeps the expanded new-card body visible if checkout scripts or the SDK writes `height: 0px` later.
     *
     * @returns {void}
     */
    const observeExpandedRecurringPaymentComponentLayout = function () {
        if (recurringPaymentComponentLayoutObserver || typeof MutationObserver === 'undefined') {
            return;
        }

        const paymentComponentElement = document.getElementById('multisafepay-payment-component-' + paymentComponentId);
        if (!paymentComponentElement) {
            return;
        }

        recurringPaymentComponentLayoutObserver = new MutationObserver(function (mutations) {
            const hasRecurringLayoutMutation = mutations.some(function (mutation) {
                return $(mutation.target).closest('.msp-ui-recurring-single-payment, .msp-ui-radio-button-item-body').length > 0;
            });

            if (hasRecurringLayoutMutation) {
                setTimeout(normalizeExpandedRecurringPaymentComponentLayout, 0);
            }
        });

        recurringPaymentComponentLayoutObserver.observe(paymentComponentElement, {
            attributes: true,
            attributeFilter: ['class', 'style'],
            subtree: true
        });
    };

    /**
     * Blocks form submission when the SDK says the selected component is invalid.
     *
     * `hasErrors()` is only a fallback before the first onValidation callback arrives.
     *
     * @returns {boolean}
     */
    const hasBlockingPaymentComponentValidationErrors = function () {
        if (paymentComponentIsValid !== null) {
            return !paymentComponentIsValid;
        }

        return getPaymentComponent().hasErrors();
    };

    /**
     * Initializes the SDK payment component and wires the callbacks used by checkout integrations.
     *
     * The SDK onValidation callback is the source of truth for enabling or disabling submit.
     *
     * @returns {void}
     */
    const initializePaymentComponent = function () {
        getPaymentComponent().init('payment', {
            container: '#multisafepay-payment-component-' + paymentComponentId,
            gateway: gateway,
            onLoad: state => {
                if (!paymentComponentElement.isConnected) {
                    return;
                }
                paymentComponentIsLoaded = true;
                logger('onLoad');
                observeExpandedRecurringPaymentComponentLayout();
                scheduleExpandedRecurringPaymentComponentLayoutFix();
                synchronizeCheckoutConfirmationButtonWithPaymentComponentState();
            },
            onError: state => {
                if (!paymentComponentElement.isConnected) {
                    return;
                }
                updatePaymentComponentValidationState(false);
                logger('onError');
            },
            onValidation: state => {
                if (!paymentComponentElement.isConnected) {
                    return;
                }
                updatePaymentComponentValidationState(state);
            }
        });
    };

    /**
     * Rechecks recurring component layout after checkout or SDK accordion selection changes.
     *
     * Some checkouts can select methods from wrapper clicks without firing the radio change listener.
     *
     * @returns {void}
     */
    const onPaymentComponentLayoutChange = function () {
        const eventNamespace = '.multisafepayPaymentComponentLayout' + paymentComponentEventSuffix;

        $(document)
            .off('change' + eventNamespace, "input[name='payment-option']")
            .on('change' + eventNamespace, "input[name='payment-option']", synchronizePaymentComponentLayoutAndCheckoutState);

        $(document)
            .off('click' + eventNamespace, ".payment-option, .payment-option-item, .js-payment-option, label[for^='payment-option-']")
            .on('click' + eventNamespace, ".payment-option, .payment-option-item, .js-payment-option, label[for^='payment-option-']", synchronizePaymentComponentLayoutAndCheckoutState);

        // Payment-step terms and OPC's optional address confirmation checkbox can decide
        // whether the final checkout order button stays disabled after validation.
        $(document)
            .off('change' + eventNamespace, checkoutTermsCheckboxSelector + ', ' + opcDeliveryAddressConfirmationSelector)
            .on('change' + eventNamespace, checkoutTermsCheckboxSelector + ', ' + opcDeliveryAddressConfirmationSelector, synchronizeCheckoutConfirmationButtonWithPaymentComponentState);

        $(document)
            .off(paymentComponentValidationEventName + eventNamespace)
            .on(paymentComponentValidationEventName + eventNamespace, scheduleExpandedRecurringPaymentComponentLayoutFix);

        $('#multisafepay-payment-component-' + paymentComponentId)
            .off('change' + eventNamespace, "input[name='recurring-payment-method']")
            .on('change' + eventNamespace, "input[name='recurring-payment-method']", scheduleExpandedRecurringPaymentComponentLayoutFix);

        $('#multisafepay-payment-component-' + paymentComponentId)
            .off('click' + eventNamespace, ".msp-ui-recurring-single-payment")
            .on('click' + eventNamespace, ".msp-ui-recurring-single-payment", scheduleExpandedRecurringPaymentComponentLayoutFix);
    };

    /**
     * Stops checkout submission while the selected component is invalid.
     *
     * Once valid, the SDK payload and tokenize choice are copied into the module form.
     *
     * @returns {void}
     */
    const onSubmitCheckoutForm = function () {
        const eventNamespace = '.multisafepayPaymentComponentSubmit' + paymentComponentEventSuffix;
        const paymentForm = $('#multisafepay-form-' + paymentComponentId);

        paymentForm
            .off('submit' + eventNamespace)
            .on('submit' + eventNamespace, function (event) {
                if (!preparePaymentComponentSubmission()) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                paymentForm.off('submit' + eventNamespace).submit();
            });
    };

    const preparePaymentComponentSubmission = function () {
        removePayload();
        if (
            window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isNativeOnePageCheckoutActive &&
            !paymentComponentIsLoaded
        ) {
            console.warn('[MultiSafepay] Payment Component has not finished loading.');
            synchronizeCheckoutConfirmationButtonWithPaymentComponentState();
            return false;
        }
        if (hasBlockingPaymentComponentValidationErrors()) {
            logger(getPaymentComponent().getErrors());
            updatePaymentComponentValidationState(false);
            return false;
        }

        const paymentData = getPaymentComponent().getPaymentData();
        if (!paymentData.payload) {
            console.error('[MultiSafepay] Payment Component returned an empty payload.');
            updatePaymentComponentValidationState(false);
            return false;
        }
        insertPayload(paymentData.payload);
        insertTokenize(paymentData.tokenize ?? '0');
        return true;
    };

    this.prepareSubmission = preparePaymentComponentSubmission;
    this.synchronizeCheckoutState = synchronizePaymentComponentLayoutAndCheckoutState;
    this.synchronizeValidationState = synchronizeCheckoutConfirmationButtonWithPaymentComponentState;
    this.destroy = function () {
        if (recurringPaymentComponentLayoutObserver) {
            recurringPaymentComponentLayoutObserver.disconnect();
        }
        $(document).off('.multisafepayPaymentComponentLayout' + paymentComponentEventSuffix);
    };

    const logger = function (argument) {
        if (config.debug) {
            console.log(argument);
        }
    };

    this.construct(config, gateway, paymentComponentId);

};

function paymentComponentIdAsEventSuffix(paymentComponentId)
{
    return String(paymentComponentId).replace(/[^a-zA-Z0-9]/g, '');
}

function createMultiSafepayPaymentComponents()
{
    multisafepayPaymentComponentInstances.forEach(function (instance, element) {
        if (!element.isConnected) {
            instance.destroy();
            multisafepayPaymentComponentInstances.delete(element);
        }
    });
    $("[id^='multisafepay-payment-component-']").each(function () {
        if (multisafepayPaymentComponentInstances.has(this)) {
            return;
        }
        multisafepayPaymentComponentInstances.set(
            this,
            new MultiSafepayPaymentComponent(window['multisafepayPaymentComponentConfig' + $(this).data('payment-id')], $(this).data('gateway'), $(this).data('payment-component-id'))
        );
    });
}

function getSelectedNativeMultiSafepayPaymentComponent()
{
    const selectedForm = window.getSelectedMultiSafepayPaymentForm();
    const element = selectedForm.find("[id^='multisafepay-payment-component-']").get(0);
    return element ? multisafepayPaymentComponentInstances.get(element) : null;
}

if (window.multisafepayCheckoutUtils.hasPrestashopEventBus()) {
    prestashop.on('opcPaymentMethodsUpdated', function () {
        createMultiSafepayPaymentComponents();
    });

    const synchronizeNativeCheckoutComponents = function (validationOnly) {
        if (!window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isNativeOnePageCheckoutActive) {
            return;
        }
        // A refresh may remove a previously locked component entirely (e.g. a carrier
        // restricts that gateway). Do not leave its lock on a hosted payment selection.
        const selectedForm = window.getSelectedMultiSafepayPaymentForm();
        if (!selectedForm.find("[id^='multisafepay-payment-component-']").length && multisafepayNativeCheckoutButtonIsEnabled) {
            $('#opc-pay-button')
                .filter('[data-multisafepay-payment-component-disabled]')
                .removeData('multisafepayPaymentComponentButtonLock')
                .removeAttr('data-multisafepay-payment-component-disabled')
                .removeClass('disabled')
                .prop('disabled', false)
                .removeAttr('disabled');
        }
        multisafepayPaymentComponentInstances.forEach(function (instance) {
            if (validationOnly === true) {
                instance.synchronizeValidationState();
            } else {
                instance.synchronizeCheckoutState();
            }
        });
    };
    prestashop.on('opcPaymentMethodSelected', synchronizeNativeCheckoutComponents);
    prestashop.on('opcPaymentMethodsRefreshed', synchronizeNativeCheckoutComponents);
    prestashop.on('opcFormValidated', function () {
        // OPC writes its button gate before emitting this event. Its isValid payload
        // includes field validity too, while OPC intentionally permits clicks to show errors.
        multisafepayNativeCheckoutButtonIsEnabled = $('#opc-pay-button').prop('disabled') === false;
        synchronizeNativeCheckoutComponents(true);
    });

    // OPC submits the inner form with native submit(), bypassing DOM submit handlers.
    prestashop.on('opcFinalSubmitStarted', function () {
        if (!window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isNativeOnePageCheckoutActive) {
            return;
        }
        const selectedForm = window.getSelectedMultiSafepayPaymentForm();
        const instance = getSelectedNativeMultiSafepayPaymentComponent();
        if (
            selectedForm.find("[id^='multisafepay-payment-component-']").length &&
            (!instance || !instance.prepareSubmission())
        ) {
            // Throwing aborts OPC's synchronous handoff; its submit error path releases its lock.
            throw new Error('[MultiSafepay] Payment Component validation blocked the native checkout handoff.');
        }
    });
}

// Validate before OPC starts persisting checkout data, including keyboard/form submission.
const guardNativePaymentComponentSubmission = function (event) {
    if (!window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isNativeOnePageCheckoutActive) {
        return;
    }
    const target = event.target;
    const isPayClick = event.type === 'click' && target instanceof Element && target.closest('#opc-pay-button');
    const isCheckoutSubmit = event.type === 'submit' && target.id === 'opc-form';
    if (!isPayClick && !isCheckoutSubmit) {
        return;
    }
    const selectedForm = window.getSelectedMultiSafepayPaymentForm();
    if (!selectedForm.find("[id^='multisafepay-payment-component-']").length) {
        return;
    }
    const instance = getSelectedNativeMultiSafepayPaymentComponent();
    if (!instance || !instance.prepareSubmission()) {
        console.warn('[MultiSafepay] Native checkout submission blocked: Payment Component is not ready or invalid.');
        event.preventDefault();
        event.stopImmediatePropagation();
    }
};
document.addEventListener('click', guardNativePaymentComponentSubmission, true);
document.addEventListener('submit', guardNativePaymentComponentSubmission, true);

// Support for "The Checkout module"
if (window.multisafepayCheckoutUtils.hasPrestashopEventBus()) {
    prestashop.on(
        'thecheckout_updatePaymentBlock',
        function (event) {
            if (event && event.reason === 'update') {
                createMultiSafepayPaymentComponents();
            }
        }
    );
}

// One Page Checkout PS support. Version 4.0.X
$(document).on('opc-load-payment:completed', function () {
    createMultiSafepayPaymentComponents();
});

// One Page Checkout PS support. Version 4.1.X
if (window.multisafepayCheckoutUtils.hasPrestashopEventBus()) {
    prestashop.on(
        'opc-payment-getPaymentList-complete',
        function (event) {
            createMultiSafepayPaymentComponents();
        }
    );
}

(function ($) {
    $(function () {
        // Default checkout
        createMultiSafepayPaymentComponents();
    });
})(jQuery);
