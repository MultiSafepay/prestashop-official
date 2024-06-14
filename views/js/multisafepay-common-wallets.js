/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
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

/**
 * Global function
 *
 * Clean up the buttons created by Google Pay and Apple Pay
 *
 * @returns {void}
 */
function cleanUpDirectButtons()
{
    const buttonClasses = ['#gpay-button-online-api-id', '.gpay-button', '.apple-pay-button'];

    buttonClasses.forEach(buttonClass => {
        const buttons = document.querySelectorAll(buttonClass);
        buttons.forEach(button => {
            if (!(button instanceof HTMLElement)) {
                return;
            }

            const parentDiv = button.parentElement;
            if (!(parentDiv instanceof HTMLElement)) {
                button.remove();
                return;
            }

            // Check if the button is covered by a <div> tag and contains only the button
            const isDivTag = parentDiv.tagName.toLowerCase() === 'div';
            const hasSingleChild = parentDiv.childNodes.length === 1;

            if (isDivTag && hasSingleChild) {
                parentDiv.remove();
            } else {
                button.remove();
            }
        });
    });
}

/**
 * Global function
 *
 * Check if the terms of service checkbox are checked
 *
 * @returns {boolean}
 */
function isTosChecked()
{
    const conditionsToApprove = document.getElementById('conditions-to-approve');
    if (conditionsToApprove) {
        if (!conditionsToApprove.checkValidity()) {
            conditionsToApprove.reportValidity();
            return false;
        }
    }
    return true;
}

/**
 * Global function
 *
 * Show the error message taking into account its debug mode and type
 *
 * @param {string} debugMessage
 * @param {boolean} debugStatus
 * @param {string} loggingType
 */
function debugDirect(debugMessage, debugStatus, loggingType = 'error')
{
    // Validation in case the loggingType is not written correctly
    const allowedTypeArray = ['log', 'info', 'warn', 'error', 'debug'];

    if (!allowedTypeArray.includes(loggingType)) {
        loggingType = 'log';
    }

    if (debugMessage && debugStatus) {
        console[loggingType](debugMessage);
    }
}

/**
 * Global function
 *
 * Get the customer's browser information
 *
 * @returns {string}
 */
function getCustomerBrowserInfo()
{
    const nav = window.navigator;
    let javaEnabled = false;
    let platform = '';
    let cookiesEnabled = false;
    let language = '';
    let userAgent = '';

    try {
        javaEnabled = nav.javaEnabled() || false;
    } catch (error) {
        console.error('javaEnabled is not supported by this browser', error);
    }
    try {
        platform = nav.platform || '';
    } catch (error) {
        console.error('platform is not supported by this browser', error);
    }
    try {
        cookiesEnabled = !!nav.cookieEnabled || false;
    } catch (error) {
        console.error('cookiesEnabled is not supported by this browser', error);
    }
    try {
        language = nav.language || '';
    } catch (error) {
        console.error('language is not supported by this browser', error);
    }
    try {
        userAgent = nav.userAgent || '';
    } catch (error) {
        console.error('userAgent is not supported by this browser', error);
    }

    let info = {
        browser: {
            javascript_enabled: true,
            java_enabled: javaEnabled,
            cookies_enabled: cookiesEnabled,
            language: language,
            screen_color_depth: window.screen.colorDepth,
            screen_height: window.screen.height,
            screen_width: window.screen.width,
            time_zone: new Date().getTimezoneOffset(),
            user_agent: userAgent,
            platform: platform
        }
    };
    return JSON.stringify(info);
}

/**
 * Class used to manage both Google Pay and Apple Pay
 */
class GoogleApplePayDirectHandler {
    constructor(isLegacyOPC = false, isLatestOPC = false)
    {
        this.isLegacyOPC = isLegacyOPC;
        this.isLatestOPC = isLatestOPC;
        this.debug = ((typeof configGooglePayDebugMode !== 'undefined') && (configGooglePayDebugMode === true)) ||
                     ((typeof configApplePayDebugMode !== 'undefined') && (configApplePayDebugMode === true));
        this.init()
            .then(() => {
                debugDirect('Handler of Google Pay and Apple Pay direct initialized', this.debug, 'log');
            })
            .catch(error => {
                console.error('Error initializing the handler for the direct payments:', error);
            });
    }

    /**
     * Initialize the class to manage both payment methods
     *
     * @returns {Promise<void>}
     */
    async init()
    {
        this.toggleGoogleAndAppleDirect();
    }

    /**
     * Toggle the display of the place order button
     *
     * @param {string} display
     * @param {Element|null} placeOrderId
     * @returns {void}
     */
    togglePlaceOrderDisplay(display, placeOrderId)
    {
        if (placeOrderId) {
            placeOrderId.setAttribute('style', 'display: ' + display + ' !important');
        }
    }

    /**
     * Handle the click on the Google Pay button
     * and launch its process
     *
     * @param {Element|null} placeOrderId
     * @param {string} containerId
     * @returns {Promise<void>}
     */
    async handleGooglePayClick(placeOrderId, containerId)
    {
        // Hide the place order button
        this.togglePlaceOrderDisplay('none', placeOrderId);

        // Getting global variables from Google Pay API
        if (paymentsClient && paymentsClient.isReadyToPay) {
            if (isReadyToPayRequest.allowedPaymentMethods.length === 0) {
                return;
            }

            try {
                const response = await paymentsClient.isReadyToPay(isReadyToPayRequest);
                if (response.result) {
                    new GooglePayDirect(containerId, this.isLegacyOPC, this.isLatestOPC);
                }
            } catch (error) {
                console.error(error);
            }
        } else {
            this.handleOtherPaymentClick(placeOrderId);
            debugDirect('Google Pay API is not available, redirect payment will be used.', this.debug, 'warn');
        }
    }

    /**
     * Handle the click on the Apple Pay button
     * and launch its process
     *
     * @param {Element|null} placeOrderId
     * @param {string} containerId
     * @returns {void}
     */
    handleApplePayClick(placeOrderId, containerId)
    {
        // Hide the place order button
        this.togglePlaceOrderDisplay('none', placeOrderId);
        new ApplePayDirect(containerId, this.isLegacyOPC, this.isLatestOPC);
    }

    /**
     * Handle the click on the other payment methods
     * and clean up the Google Pay, and Apple Pay buttons
     *
     * @param {Element|null} placeOrderId
     * @returns {void}
     */
    handleOtherPaymentClick(placeOrderId)
    {
        // Show the place order button
        this.togglePlaceOrderDisplay('block', placeOrderId);
        // Check if previous buttons already exist and remove them
        cleanUpDirectButtons();
    }

    /**
     * Check if the Google Pay, and Apple Pay has been
     * configured as direct payment methods
     *
     * @returns {{googlePayScriptExists: boolean, applePayScriptExists: boolean}}
     */
    checkLoadedDirectScripts()
    {
        const googlePayScriptName = 'multisafepay-googlepay-wallet.js';
        const applePayScriptName = 'multisafepay-applepay-wallet.js';
        const scriptTags = document.getElementsByTagName('script');
        let googlePayScriptExists = false;
        let applePayScriptExists = false;

        for (let i = 0, scriptLength = scriptTags.length; i < scriptLength; i++) {
            if (scriptTags[i].src.includes(googlePayScriptName)) {
                googlePayScriptExists = true;
            } else if (scriptTags[i].src.includes(applePayScriptName)) {
                applePayScriptExists = true;
            }
            // We can stop the loop if both scripts are loaded
            if (googlePayScriptExists && applePayScriptExists) {
                break;
            }
        }
        return { googlePayScriptExists, applePayScriptExists };
    }

    /**
     * Wait for an element to be loaded
     *
     * @param selector
     * @param maxAttempts
     * @returns {Promise<unknown>}
     */
    waitForElement(selector, maxAttempts = 30)
    {
        let attempts = 0;

        return new Promise(resolve => {
            if (document.querySelector(selector)) {
                return resolve(document.querySelector(selector));
            }
            const observer = new MutationObserver(() => {
                attempts++;
                if (document.querySelector(selector)) {
                    resolve(document.querySelector(selector));
                    observer.disconnect();
                } else if (attempts >= maxAttempts) {
                    observer.disconnect();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }

    /**
     * Toggle the Google Pay, and Apple Pay buttons and once clicked,
     * redirect to the right classes via specific methods
     *
     * @returns {void}
     */
    async toggleGoogleAndAppleDirect()
    {
        const inputGooglePay = 'GOOGLEPAY', inputApplePay = 'APPLEPAY';

        /** @var {Element|null} placeOrderId */
        let placeOrderId = null;
        let checkoutButtonId = '#payment-confirmation div.ps-shown-by-js';
        let containerId = 'payment-confirmation';
        if (this.isLegacyOPC) {
            /** @var {string} checkoutButtonId */
            checkoutButtonId = '#btn_place_order';
            /** @var {string} containerId */
            containerId = 'buttons_footer_review';
            placeOrderId = await this.waitForElement(checkoutButtonId);
        } else {
            placeOrderId = document.querySelector(
                checkoutButtonId
            );
        }

        // Object destructuring assignment was introduced in ECMAScript 6 (ES2015) in June 2015.
        const {googlePayScriptExists, applePayScriptExists} = this.checkLoadedDirectScripts();

        document.querySelectorAll('[id^="payment-option-"]')
            .forEach((element) => {
                /** @var {string|null} moduleName */
                const moduleName = element.getAttribute('data-module-name');
                let inputGooglePayMatch = false, inputApplePayMatch = false;
                if (moduleName !== null) {
                    /** @var {boolean} inputGooglePayMatch */
                    inputGooglePayMatch = moduleName && moduleName.includes(inputGooglePay);
                    /** @var {boolean} inputApplePayMatch */
                    inputApplePayMatch = moduleName && moduleName.includes(inputApplePay);
                    /** @var {string|null} paymentId */
                    const paymentId = element.getAttribute('id');
                    /** @var {Element|null} parentElement */
                    const parentElement = element.closest('div[id^="payment-option-"]');

                    if (!paymentId.includes('container')) {
                        const targetElement = parentElement ? parentElement : element;
                        if (inputGooglePayMatch && googlePayScriptExists) {
                            targetElement.addEventListener('click', () => this.handleGooglePayClick(placeOrderId, containerId));
                        } else if (inputApplePayMatch && applePayScriptExists) {
                            targetElement.addEventListener('click', () => this.handleApplePayClick(placeOrderId, containerId));
                        } else {
                            targetElement.addEventListener('click', () => this.handleOtherPaymentClick(placeOrderId));
                        }
                    }
                }
            });
    }
}

(function ($) {
    $(function () {
        /**
         * Initialize the class to launch Google Pay and Apple Pay
         */
        new GoogleApplePayDirectHandler();

        // One Page Checkout PS support. Version 4.0.X
        $(document).on('opc-load-payment:completed', function () {
            new GoogleApplePayDirectHandler(true, false);
        });

        // One Page Checkout PS support. Version 4.1.X & 5.0.X
        if (typeof prestashop !== 'undefined') {
            prestashop.on(
                'opc-payment-getPaymentList-complete',
                function (event) {
                    new GoogleApplePayDirectHandler(false, true);
                }
            );
        }
    });
})(jQuery);
