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
 * Build the direct wallet controller URL for this module.
 *
 * @param {string} controllerName
 * @returns {string}
 */
function getDirectWalletControllerUrl(controllerName)
{
    return './index.php?fc=module&module=multisafepayofficial&controller=' + controllerName;
}

/**
 * Shared controller endpoints used by Apple Pay and Google Pay direct flows.
 *
 * Wallet-specific scripts are loaded before this file, but instantiated by this file,
 * so exposing the URLs on the window keeps both scripts aligned in one place.
 */
window.multisafepayDirectWalletConfig = {
    cartDetailsEndpoint: getDirectWalletControllerUrl('cartdetails'),
    applePaySessionEndpoint: getDirectWalletControllerUrl('applepaysession')
};

/**
 * Global function
 *
 * Clean up the buttons created by Google Pay and Apple Pay
 *
 * @returns {void}
 */
function cleanUpDirectButtons()
{
    const buttonClasses = ['#gpay-button-online-api-id', '.gpay-button', '.gpay-card-info-container', '.apple-pay-button'];

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

    document.querySelectorAll('.multisafepay-wallet-button-wrapper').forEach(wrapper => {
        if (!(wrapper instanceof HTMLElement)) {
            return;
        }

        if (!wrapper.querySelector('#gpay-button-online-api-id, .gpay-button, .gpay-card-info-container, .apple-pay-button')) {
            wrapper.remove();
        }
    });
}

/**
 * Global function
 *
 * Check if the terms of service checkbox is checked
 *
 * @returns {boolean}
 */
function isTosChecked()
{
    const conditionsToApprove = document.getElementById('conditions-to-approve');

    // If the checkout doesn't render Terms of Service (optional in some OPC setups),
    // we must not block Google Pay / Apple Pay.
    if (!conditionsToApprove) {
        return true;
    }

    // Prefer explicit checkbox validation so we can ignore hidden/disabled fields.
    const requiredCheckboxes = Array.from(
        conditionsToApprove.querySelectorAll('input[type="checkbox"][required]')
    ).filter((checkbox) => {
        // getClientRects().length === 0 usually means "not visible" (display:none or detached)
        return !checkbox.disabled && checkbox.getClientRects().length > 0;
    });

    // No required (visible) checkbox means there's nothing to approve.
    if (requiredCheckboxes.length === 0) {
        return true;
    }

    for (const checkbox of requiredCheckboxes) {
        if (!checkbox.checked) {
            checkbox.focus();
            if (typeof checkbox.reportValidity === 'function') {
                checkbox.reportValidity();
            }
            return false;
        }
    }

    // If it's a <form>, keep HTML5 validation for any other constraints that might exist.
    if (typeof conditionsToApprove.checkValidity === 'function') {
        if (!conditionsToApprove.checkValidity()) {
            if (typeof conditionsToApprove.reportValidity === 'function') {
                conditionsToApprove.reportValidity();
            }
            return false;
        }
    }

    return true;
}

/**
 * Check if the optional One Page Checkout PS delivery-address confirmation is approved.
 *
 * @param {boolean} reportValidity
 * @returns {boolean}
 */
function isOpcDeliveryAddressConfirmationChecked(reportValidity)
{
    const deliveryAddressConfirmation = document.getElementById(
        window.multisafepayCheckoutUtils.onePageCheckoutPs.deliveryAddressConfirmationId
    );

    if (
        !deliveryAddressConfirmation ||
        deliveryAddressConfirmation.disabled ||
        deliveryAddressConfirmation.getClientRects().length === 0
    ) {
        return true;
    }

    if (deliveryAddressConfirmation.checked) {
        return true;
    }

    deliveryAddressConfirmation.focus();
    // Some wallet flows need the native browser hint; modern "One Page Checkout PS"
    // for Apple Pay already shows its own alert.
    if (reportValidity && typeof deliveryAddressConfirmation.reportValidity === 'function') {
        deliveryAddressConfirmation.reportValidity();
    }

    return false;
}

/**
 * Return the first checkout approval error that blocks direct wallets.
 *
 * @param {string} paymentMethodName
 * @param {Object} [options]
 * @param {boolean} [options.reportOpcDeliveryValidity]
 * @returns {string}
 */
function getDirectWalletCheckoutApprovalError(paymentMethodName, options = {})
{
    // Report native validity by default, so Google Pay and flows outside "One Page Checkout PS" still guide the customer.
    const reportOpcDeliveryValidity = options.reportOpcDeliveryValidity !== false;

    if (!isTosChecked()) {
        return 'Terms of Service for ' + paymentMethodName + ' not checked';
    }

    if (!isOpcDeliveryAddressConfirmationChecked(reportOpcDeliveryValidity)) {
        return 'Delivery address confirmation for ' + paymentMethodName + ' not checked';
    }

    return '';
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
    window.multisafepayCheckoutUtils.logDebugMessage(debugMessage, debugStatus, loggingType);
}

/**
 * Return the direct wallet cart details settings injected by PHP.
 *
 * @returns {{maxAttempts: number, errorMessage: string}}
 */
function getDirectWalletCartDetailsConfig()
{
    const cartDetailsConfig = window.multisafepayDirectWalletCartDetailsConfig || {};
    const maxAttempts = Number(cartDetailsConfig.maxAttempts);
    const errorMessage = typeof cartDetailsConfig.errorMessage === 'string' ? cartDetailsConfig.errorMessage.trim() : '';

    return {
        maxAttempts: Number.isFinite(maxAttempts) && maxAttempts > 0 ? Math.floor(maxAttempts) : 1,
        errorMessage: errorMessage
    };
}

/**
 * Remove the direct wallet cart details error from a previous attempt.
 *
 * @returns {void}
 */
function clearDirectWalletCartDetailsError()
{
    const existingError = document.getElementById('multisafepay-direct-wallet-cart-details-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Show a blocking checkout error when the cart details cannot be refreshed.
 *
 * @param {string} errorMessage
 * @returns {void}
 */
function showDirectWalletCartDetailsError(errorMessage)
{
    clearDirectWalletCartDetailsError();

    // Use PrestaShop's alert classes so the message inherits the active theme styling.
    const errorElement = document.createElement('div');
    errorElement.id = 'multisafepay-direct-wallet-cart-details-error';
    errorElement.className = 'alert alert-danger';
    errorElement.setAttribute('role', 'alert');
    errorElement.setAttribute('tabindex', '-1');
    errorElement.textContent = errorMessage;

    // Prefer placing the error near the wallet button, then fall back to standard checkout containers.
    const walletWrapper = document.querySelector('.multisafepay-wallet-button-wrapper');
    const paymentConfirmation = document.getElementById('payment-confirmation');
    const paymentOptions = document.querySelector('.payment-options');
    const referenceElement = walletWrapper || paymentConfirmation || paymentOptions;

    if (referenceElement && referenceElement.parentNode) {
        referenceElement.parentNode.insertBefore(errorElement, referenceElement);
    } else {
        document.body.insertBefore(errorElement, document.body.firstChild);
    }

    // Move focus to the alert so keyboard and screen-reader users notice the blocking error.
    errorElement.focus();
}

/**
 * Check whether a cart details value contains a non-empty string.
 *
 * @param {*} value
 * @returns {boolean}
 */
function isDirectWalletCartDetailsString(value)
{
    return typeof value === 'string' && value.trim() !== '';
}

/**
 * Check whether a cart total value can be sent to a wallet request.
 *
 * @param {*} value
 * @returns {boolean}
 */
function isDirectWalletCartDetailsTotal(value)
{
    if (value === null || typeof value === 'undefined') {
        return false;
    }

    if (typeof value === 'string' && value.trim() === '') {
        return false;
    }

    return Number.isFinite(Number(value));
}

/**
 * Validate the cart details payload returned by the module endpoint.
 *
 * @param {object|null} cartDetails
 * @returns {string}
 */
function getDirectWalletCartDetailsValidationError(cartDetails)
{
    if (!cartDetails || typeof cartDetails !== 'object') {
        return 'Cart details response is empty';
    }

    if (!isDirectWalletCartDetailsTotal(cartDetails.total_price)) {
        return 'Cart details response does not include a valid total_price';
    }

    if (!isDirectWalletCartDetailsString(cartDetails.currency_code)) {
        return 'Cart details response does not include a valid currency_code';
    }

    if (!isDirectWalletCartDetailsString(cartDetails.country_code)) {
        return 'Cart details response does not include a valid country_code';
    }

    return '';
}

/**
 * Fetch current server-side cart details for direct wallet payment sheets.
 *
 * @param {string} cartDetailsEndpoint
 * @param {string} paymentMethodName
 * @param {boolean} debugStatus
 * @returns {Promise<object>}
 * @throws {Error}
 */
async function fetchDirectWalletCartDetailsResponse(cartDetailsEndpoint, paymentMethodName, debugStatus)
{
    // Remove stale errors before starting a fresh refresh attempt.
    clearDirectWalletCartDetailsError();

    // PHP owns the retry policy and translated customer message for this checkout flow.
    const cartDetailsConfig = getDirectWalletCartDetailsConfig();
    let latestErrorMessage = '';

    // The wallet amount must match the server-side cart; retry transient checkout refresh failures first.
    for (let attempt = 1; attempt <= cartDetailsConfig.maxAttempts; attempt++) {
        try {
            const response = await fetch(cartDetailsEndpoint, {
                method: 'POST'
            });

            // Do not parse failed HTTP responses as usable cart totals.
            if (!response.ok) {
                throw new Error('Cart details request failed with status ' + response.status);
            }

            const cartDetails = await response.json();
            const validationError = getDirectWalletCartDetailsValidationError(cartDetails);
            if (validationError) {
                throw new Error(validationError);
            }

            // Return only freshly fetched cart details; initial Media::addJsDef values are not a payment fallback.
            return cartDetails;
        } catch (error) {
            latestErrorMessage = error && error.message ? error.message : String(error);
            debugDirect(
                paymentMethodName + ' cart details refresh attempt ' + attempt + ' failed: ' + latestErrorMessage,
                debugStatus,
                'warn'
            );
        }
    }

    // After all attempts fail, block the wallet flow so no stale amount can be authorized.
    if (cartDetailsConfig.errorMessage) {
        showDirectWalletCartDetailsError(cartDetailsConfig.errorMessage);
    }

    throw new Error('Direct wallet cart details refresh failed. Last error: ' + latestErrorMessage);
}

/**
 * Fetch and normalize the latest cart details for a direct wallet payment sheet.
 *
 * @param {string} cartDetailsEndpoint
 * @param {string} paymentMethodName
 * @param {boolean} debugStatus
 * @returns {Promise<{totalPrice: number, currencyCode: string, countryCode: string}>}
 * @throws {Error}
 */
async function fetchDirectWalletCartDetails(cartDetailsEndpoint, paymentMethodName, debugStatus)
{
    const cartDetails = await fetchDirectWalletCartDetailsResponse(
        cartDetailsEndpoint,
        paymentMethodName,
        debugStatus
    );

    return {
        totalPrice: Number(cartDetails.total_price),
        currencyCode: cartDetails.currency_code.trim(),
        countryCode: cartDetails.country_code.trim()
    };
}

let multisafepayDirectWalletInitializationTimeoutId = null;
let multisafepayDirectWalletLastInitializationSignature = '';

/**
 * Return a stable signature for the current checkout state used by direct wallets.
 *
 * Checkout events can fire repeatedly while the customer changes methods or fills payment fields.
 * This keeps only the wallet-relevant state, so regular method changes do not recreate wallet handlers.
 *
 * @param {boolean} isLegacyOPC
 * @param {boolean} isLatestOPC
 * @returns {string}
 */
function getDirectWalletInitializationSignature(isLegacyOPC, isLatestOPC)
{
    const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();
    const checkoutButtons = [
        '#confirm_order',
        '#payment-confirmation div.ps-shown-by-js',
        '#btn_place_order',
        '#btn-placer_order'
    ].map(selector => selector + ':' + (document.querySelector(selector) ? '1' : '0')).join('|');
    const paymentOptions = Array.from(document.querySelectorAll('[id^="payment-option-"]'))
        .filter(element => !String(element.getAttribute('id') || '').includes('container'))
        .map(element => {
            const targetElement = element.closest('div[id^="payment-option-"]') || element;
            const moduleName = element.getAttribute('data-module-name') || '';
            const isDirectWalletPaymentOption = moduleName.includes('GOOGLEPAY') || moduleName.includes('APPLEPAY');
            const hasDirectWalletHandler = Boolean(targetElement.directWalletClickHandler);

            return [
                element.getAttribute('id') || '',
                moduleName,
                isDirectWalletPaymentOption && !hasDirectWalletHandler && element.checked ? '1' : '0',
                hasDirectWalletHandler ? '1' : '0'
            ].join(':');
        })
        .join('|');

    return [
        isLegacyOPC ? 'legacy-opc' : 'standard-opc',
        isLatestOPC ? 'latest-opc' : 'standard-checkout',
        checkoutCompatibilityState.isOnePageCheckoutPsActive ? 'opc' : 'no-opc',
        checkoutCompatibilityState.isTheCheckoutActive ? 'the-checkout' : 'no-the-checkout',
        checkoutCompatibilityState.hasExternalConfirmationButton ? 'external-confirmation' : 'no-external-confirmation',
        checkoutButtons,
        paymentOptions
    ].join('||');
}

/**
 * Schedule direct wallet initialization once per effective checkout state.
 *
 * Checkout integrations can emit several events for one visible change.
 * This runs the initialization once and skips it when the signature does not change.
 *
 * @param {boolean} [isLegacyOPC=false]
 * @param {boolean} [isLatestOPC=false]
 * @returns {void}
 */
function scheduleGoogleApplePayDirectHandler(isLegacyOPC = false, isLatestOPC = false)
{
    if (multisafepayDirectWalletInitializationTimeoutId) {
        clearTimeout(multisafepayDirectWalletInitializationTimeoutId);
    }

    multisafepayDirectWalletInitializationTimeoutId = setTimeout(function () {
        multisafepayDirectWalletInitializationTimeoutId = null;

        const initializationSignature = getDirectWalletInitializationSignature(isLegacyOPC, isLatestOPC);
        if (initializationSignature === multisafepayDirectWalletLastInitializationSignature) {
            return;
        }

        multisafepayDirectWalletLastInitializationSignature = initializationSignature;

        const directWalletHandler = new GoogleApplePayDirectHandler(isLegacyOPC, isLatestOPC);
        if (directWalletHandler.initializationPromise && typeof directWalletHandler.initializationPromise.then === 'function') {
            directWalletHandler.initializationPromise.then(function () {
                multisafepayDirectWalletLastInitializationSignature = getDirectWalletInitializationSignature(isLegacyOPC, isLatestOPC);
            });
        }
    }, 0);
}

/**
 * Ensure the CSS hooks used to hide checkout CTAs are available.
 *
 * @returns {void}
 */
function ensureDirectWalletVisibilityStyles()
{
    if (document.getElementById('multisafepay-direct-wallet-visibility-styles')) {
        return;
    }

    const styleTag = document.createElement('style');
    styleTag.id = 'multisafepay-direct-wallet-visibility-styles';
    styleTag.textContent = [
        'body.multisafepay-hide-confirm-order #confirm_order { display: none !important; }',
        'body.multisafepay-hide-native-confirmation #payment-confirmation div.ps-shown-by-js { display: none !important; }',
        'body.multisafepay-hide-opc-confirm-order #btn_place_order, body.multisafepay-hide-opc-confirm-order #btn-placer_order { display: none !important; }'
    ].join(' ');

    document.head.appendChild(styleTag);
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
        this.initializationPromise = this.init()
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
     * @param {string} placeOrderSelector
     * @returns {void}
     */
    togglePlaceOrderDisplay(display, placeOrderSelector)
    {
        const placeOrderElement = document.querySelector(placeOrderSelector);
        const bodyClassMap = {
            '#confirm_order': 'multisafepay-hide-confirm-order',
            '#payment-confirmation div.ps-shown-by-js': 'multisafepay-hide-native-confirmation',
            '#btn-placer_order': 'multisafepay-hide-opc-confirm-order',
            '#btn_place_order': 'multisafepay-hide-opc-confirm-order'
        };
        const bodyClassName = bodyClassMap[placeOrderSelector];

        ensureDirectWalletVisibilityStyles();

        if (bodyClassName) {
            document.body.classList.toggle(bodyClassName, display === 'none');
        }

        if (placeOrderElement) {
            if (display === 'none') {
                placeOrderElement.style.setProperty('display', 'none', 'important');
            } else {
                placeOrderElement.style.removeProperty('display');
            }
        }
    }

    /**
     * Handle the click on the Google Pay button
     * and launch its process
     *
     * @param {string} placeOrderSelector
     * @param {string} containerId
     * @returns {Promise<void>}
     */
    async handleGooglePayClick(placeOrderSelector, containerId)
    {
        // Getting global variables from Google Pay API
        if (paymentsClient && paymentsClient.isReadyToPay) {
            if (isReadyToPayRequest.allowedPaymentMethods.length === 0) {
                return;
            }

            try {
                const response = await paymentsClient.isReadyToPay(isReadyToPayRequest);
                if (response.result) {
                    this.togglePlaceOrderDisplay('none', placeOrderSelector);
                    new GooglePayDirect(containerId, this.isLegacyOPC, this.isLatestOPC);
                }
            } catch (error) {
                console.error(error);
            }
        } else {
            this.handleOtherPaymentClick(placeOrderSelector);
            debugDirect('Google Pay API is not available, redirect payment will be used.', this.debug, 'warn');
        }
    }

    /**
     * Handle the click on the Apple Pay button
     * and launch its process
     *
     * @param {string} placeOrderSelector
     * @param {string} containerId
     * @returns {void}
     */
    handleApplePayClick(placeOrderSelector, containerId)
    {
        // Hide the place order button
        this.togglePlaceOrderDisplay('none', placeOrderSelector);
        new ApplePayDirect(containerId, this.isLegacyOPC, this.isLatestOPC);
    }

    /**
     * Handle the click on the other payment methods
     * and clean up the Google Pay, and Apple Pay buttons
     *
     * @param {string} placeOrderSelector
     * @returns {void}
     */
    handleOtherPaymentClick(placeOrderSelector)
    {
        // Show the place order button
        this.togglePlaceOrderDisplay('block', placeOrderSelector);
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
        let googlePayScriptExists = typeof GooglePayDirect === 'function';
        let applePayScriptExists = typeof ApplePayDirect === 'function';

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
                    resolve(null);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }

    /**
     * Resolve the One Page Checkout PS order button selector across supported versions.
     *
     * @returns {string}
     */
    getOnePageCheckoutPlaceOrderSelector()
    {
        const placeOrderSelectors = this.isLegacyOPC ? ['#btn_place_order', '#btn-placer_order'] : ['#btn-placer_order', '#btn_place_order'];

        return placeOrderSelectors.find(selector => document.querySelector(selector)) || placeOrderSelectors[0];
    }

    /**
     * Resolve the visible checkout CTA and the container used for direct wallets.
     *
     * @returns {{placeOrderSelector: string, containerId: string}}
     */
    getDirectWalletCheckoutButtonContext()
    {
        const checkoutCompatibilityState = window.multisafepayCheckoutUtils.getCheckoutCompatibilityState();

        if (this.isLegacyOPC) {
            return {
                placeOrderSelector: this.getOnePageCheckoutPlaceOrderSelector(),
                containerId: 'buttons_footer_review'
            };
        }

        if (this.isLatestOPC) {
            return {
                placeOrderSelector: this.getOnePageCheckoutPlaceOrderSelector(),
                containerId: 'payment-confirmation'
            };
        }

        if (
            checkoutCompatibilityState.isTheCheckoutActive &&
            checkoutCompatibilityState.hasExternalConfirmationButton
        ) {
            return {
                placeOrderSelector: '#confirm_order',
                containerId: 'confirm_order'
            };
        }

        return {
            placeOrderSelector: '#payment-confirmation div.ps-shown-by-js',
            containerId: 'payment-confirmation'
        };
    }

    /**
     * Toggle the Google Pay and Apple Pay buttons and once clicked,
     * redirect to the right classes via specific methods
     *
     * @returns {void}
     */
    async toggleGoogleAndAppleDirect()
    {
        const inputGooglePay = 'GOOGLEPAY', inputApplePay = 'APPLEPAY';
        const checkoutButtonContext = this.getDirectWalletCheckoutButtonContext();

        if (!document.querySelector(checkoutButtonContext.placeOrderSelector)) {
            await this.waitForElement(checkoutButtonContext.placeOrderSelector);
        }

        const placeOrderSelector = checkoutButtonContext.placeOrderSelector;
        const containerId = checkoutButtonContext.containerId;

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

                        const radioInput = element.tagName === 'INPUT' ? element : document.getElementById(paymentId);
                        const isChecked = radioInput && radioInput.checked;
                        const handlerContextSignature = [paymentId, placeOrderSelector, containerId].join('|');
                        const shouldHandleCheckedDirectWallet = isChecked && (
                            targetElement.directWalletCheckedSignature !== handlerContextSignature ||
                            !document.querySelector('#gpay-button-online-api-id, .gpay-button, .gpay-card-info-container, .apple-pay-button')
                        );

                        if (targetElement.directWalletClickHandler) {
                            targetElement.removeEventListener('click', targetElement.directWalletClickHandler);
                        }

                        if (inputGooglePayMatch && googlePayScriptExists) {
                            targetElement.directWalletClickHandler = () => this.handleGooglePayClick(placeOrderSelector, containerId);
                            targetElement.addEventListener('click', targetElement.directWalletClickHandler);
                            if (shouldHandleCheckedDirectWallet) {
                                targetElement.directWalletCheckedSignature = handlerContextSignature;
                                this.handleGooglePayClick(placeOrderSelector, containerId);
                            }
                        } else if (inputApplePayMatch && applePayScriptExists) {
                            targetElement.directWalletClickHandler = () => this.handleApplePayClick(placeOrderSelector, containerId);
                            targetElement.addEventListener('click', targetElement.directWalletClickHandler);
                            if (shouldHandleCheckedDirectWallet) {
                                targetElement.directWalletCheckedSignature = handlerContextSignature;
                                this.handleApplePayClick(placeOrderSelector, containerId);
                            }
                        } else {
                            targetElement.directWalletClickHandler = () => this.handleOtherPaymentClick(placeOrderSelector);
                            targetElement.addEventListener('click', targetElement.directWalletClickHandler);
                            targetElement.directWalletCheckedSignature = '';
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
        scheduleGoogleApplePayDirectHandler();

        // One Page Checkout PS support. Version 4.0.X
        $(document).on('opc-load-payment:completed', function () {
            scheduleGoogleApplePayDirectHandler(true, false);
        });

        // One Page Checkout PS support. Version 4.1.X & 5.0.X
        if (window.multisafepayCheckoutUtils.hasPrestashopEventBus()) {
            prestashop.on(
                'changedCheckoutStep',
                function () {
                    scheduleGoogleApplePayDirectHandler();
                }
            );

            // OPC activity is detected by shared DOM markers. This event
            // initializes wallets after the modern payment-list refresh.
            prestashop.on(
                'opc-payment-getPaymentList-complete',
                function () {
                    scheduleGoogleApplePayDirectHandler(false, true);
                }
            );

            prestashop.on(
                'thecheckout_updatePaymentBlock',
                function (event) {
                    if (
                        event &&
                        event.reason === 'update' &&
                        window.multisafepayCheckoutUtils.getCheckoutCompatibilityState().isTheCheckoutActive
                    ) {
                        scheduleGoogleApplePayDirectHandler();
                    }
                }
            );
        }
    });
})(jQuery);
