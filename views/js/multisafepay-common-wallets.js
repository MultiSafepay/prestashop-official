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
    const buttonClasses = ['.gpay-button', '.apple-pay-button'];

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
    constructor()
    {
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
     * @returns {Promise<void>}
     */
    async handleGooglePayClick(placeOrderId)
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
                    new GooglePayDirect();
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
     * @returns {void}
     */
    handleApplePayClick(placeOrderId)
    {
        // Hide the place order button
        this.togglePlaceOrderDisplay('none', placeOrderId);
        new ApplePayDirect();
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
     * Toggle the Google Pay, and Apple Pay buttons and once clicked,
     * redirect to the right classes via specific methods
     *
     * @returns {void}
     */
    toggleGoogleAndAppleDirect()
    {
        const inputGooglePay = 'GOOGLEPAY', inputApplePay = 'APPLEPAY';
        /** @var {Element|null} placeOrderId */
        const placeOrderId = document.querySelector(
            '#payment-confirmation div.ps-shown-by-js'
        );

        // Object destructuring assignment was introduced in ECMAScript 6 (ES2015) in June 2015.
        // Google Chrome: March 2016 - Mozilla Firefox: March 2017 - Microsoft Edge: July 2015.
        // Apple Safari: September 2016 - Opera: March 2016.
        const { googlePayScriptExists, applePayScriptExists } = this.checkLoadedDirectScripts();

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

                    if (!paymentId.includes('container')) {
                        if (inputGooglePayMatch && googlePayScriptExists) {
                            element.addEventListener('click', () => this.handleGooglePayClick(placeOrderId));
                        } else if (inputApplePayMatch && applePayScriptExists) {
                            element.addEventListener('click', () => this.handleApplePayClick(placeOrderId));
                        } else {
                            element.addEventListener('click', () => this.handleOtherPaymentClick(placeOrderId));
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
    });
})(jQuery);
