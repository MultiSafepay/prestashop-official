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
 * Set of global variables following the Google Pay API
 *
 * They need to be created from the global scope
 */
const baseRequest = {
    apiVersion: 2,
    apiVersionMinor: 0
};

const tokenizationSpecification = {
    type: 'PAYMENT_GATEWAY',
    parameters: {
        'gateway': 'multisafepay',
        'gatewayMerchantId': configGatewayMerchantId.toString()
    }
};

const allowedCardNetworks = ['MASTERCARD', 'VISA'];
const allowedCardAuthMethods = ['CRYPTOGRAM_3DS', 'PAN_ONLY'];

const baseCardPaymentMethod = {
    type: 'CARD',
    parameters: {
        allowedAuthMethods: allowedCardAuthMethods,
        allowedCardNetworks: allowedCardNetworks
    }
};

const cardPaymentMethod = Object.assign(
    {tokenizationSpecification: tokenizationSpecification},
    baseCardPaymentMethod
);

/**
 * Checking if the Google Pay API file has been loaded
 */
let paymentsClient = false, isReadyToPayRequest = false;

/**
 * Get the boolean value for debug mode
 * @type {boolean}
 */
const debugModeGooglePay = configGooglePayDebugMode === true;

(function ($) {
    $(function () {
        if (!window.google || !window.google.payments || !window.google.payments.api) {
            console.error('Error initializing Google Pay: Script not loaded');
        } else {
            const googlePayConfigEnvironment = configEnvironment === 'LIVE' ? 'PRODUCTION' : 'TEST';
            paymentsClient = new google.payments.api.PaymentsClient({environment: googlePayConfigEnvironment});
            isReadyToPayRequest = Object.assign({}, baseRequest);
            isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];
        }
    });
})(jQuery);

/**
 * Class for Google Pay Direct
 */
class GooglePayDirect {
    constructor(containerId, isLegacyOPC, isLatestOPC)
    {
        this.containerId = containerId;
        this.isLegacyOPC = isLegacyOPC;
        this.isLatestOPC = isLatestOPC;
        this.debug = debugModeGooglePay;
        this.init()
            .then(() => {
                debugDirect('Google Pay Direct initialized', this.debug, 'log');
            })
            .catch(error => {
                console.error('Error initializing Google Pay Direct:', error);
            });
    }

    /**
     * Initialize the process calling to create the button
     *
     * @returns {Promise<void>}
     */
    async init()
    {
        try {
            await this.createGooglePayButton();
        } catch (error) {
            console.error('Error creating Google Pay button:', error);
        }
    }

    /**
     * Validate the Terms of Service and prevent event propagation if not checked
     *
     * @param {Event} event - The click event
     * @param {string} source - Source identifier for debugging
     * @returns {boolean} - true if TOS is checked, false otherwise
     */
    validateTosAndPreventEvent(event, source = 'unknown')
    {
        const checkTos = isTosChecked();
        if (!checkTos) {
            event.preventDefault();
            event.stopImmediatePropagation();
            debugDirect(`Terms of Service for Google Pay not checked - ${source} click prevented`, this.debug, 'warn');
            return false;
        }
        return true;
    }

    /**
     * Create the Google Pay button
     *
     * @returns {Promise<void>}
     */
    async createGooglePayButton()
    {
        // Check if previous buttons already exist and remove them
        cleanUpDirectButtons();

        if (!paymentsClient || !paymentsClient.createButton) {
            debugDirect('Error creating Google Pay button: Script not loaded rightly', this.debug);
            return;
        }

        let buttonContainer = document.getElementById(this.containerId);

        // Features of the button
        const button = paymentsClient.createButton({
            buttonType: 'plain',
            buttonColor: 'black',
            onClick: this.onGooglePaymentButtonClicked.bind(this)
        });

        // Add a capturing event listener to intercept clicks before Google Pay processes them
        button.addEventListener('click', (event) => {
            if (!this.validateTosAndPreventEvent(event, 'button')) {
                return false;
            }
        }, true);

        if (this.isLegacyOPC || this.isLatestOPC) {
            // Create a wrapper div to avoid the PrestaShop automated disabling
            const wrapperDiv = document.createElement('div');

            // Add the click event to the wrapper to avoid the propagation,
            // so the button can be clicked without activate the redirect mode
            wrapperDiv.addEventListener('click', (event) => {
                if (!this.validateTosAndPreventEvent(event, 'wrapper')) {
                    return false;
                }
                event.stopPropagation();
            }, true);

            // Add the OPC classes to the button
            if (this.isLegacyOPC) {
                // Add the specific container for the button
                buttonContainer = document.querySelector('#' + this.containerId + ' > div');
                button.firstChild.classList.add('btn', 'btn-primary', 'btn-lg', 'pull-right');
            } else if (this.isLatestOPC) {
                buttonContainer.style.textAlign = 'right';
            }
            // Append the button to the wrapper
            wrapperDiv.appendChild(button);
            // Append the wrapper to the container
            buttonContainer.appendChild(wrapperDiv);
        } else {
            buttonContainer = buttonContainer.parentElement;
            // Append the button to the "parent" container,
            // so we can avoid the automated disabling from PrestaShop
            buttonContainer.appendChild(button);
        }

        if (!buttonContainer) {
            debugDirect('Button container not found', this.debug);
        }
    }

    /**
     * Create the Google Pay payment data request
     *
     * Some variables from the global scope are launched from
     * the internal code of Prestashop
     *
     * @returns {object} paymentDataRequest
     */
    getGooglePaymentDataRequest()
    {
        const paymentDataRequest = Object.assign({}, baseRequest);
        paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
        paymentDataRequest.transactionInfo = {
            totalPriceStatus: 'FINAL',
            totalPrice: configGooglePayTotalPrice.toFixed(2),
            currencyCode: configGooglePayCurrencyCode,
            countryCode: configGooglePayCountryCode
        };
        paymentDataRequest.merchantInfo = {
            merchantName: configGooglePayMerchantName,
            merchantId: configGooglePayMerchantId
        };
        return paymentDataRequest;
    }

    /**
     * Event handler for the Google Pay button
     *
     * @returns {Promise<void>}
     */
    async onGooglePaymentButtonClicked()
    {
        if (paymentsClient && paymentsClient.loadPaymentData) {
            try {
                const dataRequest = this.getGooglePaymentDataRequest();
                if (this.debug && (!dataRequest || (typeof dataRequest !== 'object'))) {
                    debugDirect('Invalid data from paymentDataRequest object', this.debug);
                }

                const paymentData = await paymentsClient.loadPaymentData(dataRequest);
                const processedPayment = this.processGooglePayment(paymentData);
                if (this.debug && !processedPayment) {
                    debugDirect('Failed to process Google Pay payment', this.debug);
                }
            } catch (message) {
                // Google Pay API can throw an error if the user cancels the payment.
                // It is shown as a warning, since this cannot be considered as an error
                console.warn('Message from the Google Pay API:', message);
            }
        }
    }

    /**
     * Submit the Google Pay form
     *
     * @param {string} tokenValue
     * @returns {boolean}
     */
    submitGooglePayForm(tokenValue)
    {
        if ((typeof (tokenValue) !== 'string') || (tokenValue.trim() === '')) {
            debugDirect('Invalid payload provided', this.debug);
            return false;
        }

        const googlepayForm = document.getElementById('multisafepay-form-googlepay');

        if (!googlepayForm) {
            debugDirect('Google Pay form not found', this.debug);
            return false;
        }

        // Settings the features of the input field
        const inputField = document.createElement('input');
        inputField.type = 'hidden';
        inputField.name = 'payment_token';
        inputField.value = tokenValue;

        // Settings the features of the browser field
        const browserField = document.createElement('input');
        browserField.type = 'hidden';
        browserField.name = 'browser';
        browserField.value = getCustomerBrowserInfo();

        // Add the hidden field to the form including the token value
        googlepayForm.appendChild(inputField);
        // Add the hidden field to the form including the browser info
        googlepayForm.appendChild(browserField);
        // Submit the form automatically
        googlepayForm.submit();
        return true;
    }

    /**
     * @param {object} paymentData
     * @returns {boolean}
     */
    processGooglePayment(paymentData)
    {
        // Validate input
        if (!paymentData ||
            !paymentData.paymentMethodData ||
            !paymentData.paymentMethodData.tokenizationData ||
            !paymentData.paymentMethodData.tokenizationData.token
        ) {
            debugDirect('Invalid payment data received', this.debug);
            return false;
        }

        // Extract the token from the payment data sent by Google Pay
        const payload = paymentData.paymentMethodData.tokenizationData.token;

        // Check if the payload is a string and not empty
        if ((typeof payload !== 'string') || (payload.trim() === '')) {
            debugDirect('Invalid token received', this.debug);
            return false;
        }

        // Call the submit function only if the payload is valid
        return this.submitGooglePayForm(payload);
    }
}
