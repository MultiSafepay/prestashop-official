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

(function (window, document) {
    // Several checkout entrypoint read multisafepayCheckoutUtils while parsing.
    // Keep the shared checkout utils in this file and load it before checkout consumers.
    const createCheckoutUtils = function () {
        let theCheckoutValidationPromise = null;
        const debugMessagePrefix = '[MultiSafepay] ';
        const allowedDebugLoggingTypes = ['log', 'info', 'warn', 'error', 'debug'];
        const opcDeliveryAddressConfirmationSelector = '#chk-confirm_address_delivery';

        return {
            onePageCheckoutPs: {
                deliveryAddressConfirmationSelector: opcDeliveryAddressConfirmationSelector,
                deliveryAddressConfirmationId: opcDeliveryAddressConfirmationSelector.substring(1)
            },

            logDebugMessage: function (message, debugStatus, loggingType) {
                let loggerType = loggingType || 'log';

                if (allowedDebugLoggingTypes.indexOf(loggerType) === -1) {
                    loggerType = 'log';
                }

                if (!message || !debugStatus) {
                    return;
                }

                let prefixedMessage = message;

                if (message.indexOf(debugMessagePrefix) !== 0) {
                    prefixedMessage = debugMessagePrefix + message;
                }

                console[loggerType](prefixedMessage);
            },

            hasPrestashopEventBus: function () {
                return typeof prestashop !== 'undefined' && prestashop && typeof prestashop.on === 'function';
            },

            getCheckoutCompatibilityState: function (checkoutHint) {
                // window.OPC and #opc_main identify One Page Checkout PS as active, regardless of its refresh events.
                const hasOnePageCheckoutPsMarkers = typeof window.OPC !== 'undefined' || document.getElementById('opc_main') !== null;
                const theCheckoutConfirmButton = document.querySelector('#confirm_order[data-link-action="x-confirm-order"]');
                // The Checkout uses a dedicated body class when its "Payment options on separate page" is enabled.
                const hasTheCheckoutMarkers = (
                    document.body !== null &&
                    (
                        document.body.classList.contains('thecheckout-module') ||
                        document.body.classList.contains('tc-separate-payment')
                    )
                ) || theCheckoutConfirmButton !== null;
                const isOnePageCheckoutPsActive = checkoutHint === 'onepagecheckoutps' || hasOnePageCheckoutPsMarkers;

                return {
                    isNativeOnePageCheckoutActive: document.querySelector('#opc-form.one-page-checkout') !== null,
                    isOnePageCheckoutPsActive: isOnePageCheckoutPsActive,
                    isTheCheckoutActive: checkoutHint === 'thecheckout' || (hasTheCheckoutMarkers && !isOnePageCheckoutPsActive),
                    hasExternalConfirmationButton: theCheckoutConfirmButton !== null
                };
            },

            validateTheCheckoutBeforePayment: function () {
                if (!this.getCheckoutCompatibilityState().isTheCheckoutActive) {
                    return Promise.resolve(true);
                }

                if (theCheckoutValidationPromise) {
                    return theCheckoutValidationPromise;
                }

                if (
                    typeof window.confirmOrder !== 'function' ||
                    typeof window.jQuery !== 'function'
                ) {
                    // The Checkout required fields and postcode rules are configurable/server-side.
                    // Without its validation API there is no reliable local selector fallback.
                    return Promise.resolve(false);
                }

                theCheckoutValidationPromise = new Promise(function (resolve) {
                    let validationFinished = false;

                    const finishValidation = function (isValid) {
                        if (validationFinished) {
                            return;
                        }

                        validationFinished = true;
                        resolve(isValid);
                    };

                    const validationTimeoutId = window.setTimeout(function () {
                        window.jQuery('[data-link-action=x-confirm-order]')
                            .prop('disabled', false)
                            .css('cursor', 'pointer')
                            .removeClass('confirm-loading');
                        finishValidation(false);
                    }, 15000);

                    try {
                        // prepare_confirmation runs The Checkout account/address validation only.
                        // This avoids the final-payment refresh that can reset embedded components.
                        // data-link-action=x-confirm-order lets The Checkout render all validation errors.
                        window.confirmOrder(
                            window.jQuery('<button id="prepare_confirmation" type="button" data-link-action="x-confirm-order"></button>'),
                            function () {
                                window.clearTimeout(validationTimeoutId);
                                finishValidation(true);
                            },
                            function () {
                                window.clearTimeout(validationTimeoutId);
                                finishValidation(false);
                            }
                        );
                    } catch (exception) {
                        window.clearTimeout(validationTimeoutId);
                        finishValidation(false);
                    }
                }).then(function (isValid) {
                    theCheckoutValidationPromise = null;
                    return isValid;
                });

                return theCheckoutValidationPromise;
            }
        };
    };

    window.multisafepayCheckoutUtils = createCheckoutUtils();
})(window, document);
