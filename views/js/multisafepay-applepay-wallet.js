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
 * Class for Apple Pay Direct
 */
class ApplePayDirect {
    constructor(containerId, isLegacyOPC, isLatestOPC)
    {
        this.containerId = containerId;
        this.isLegacyOPC= isLegacyOPC;
        this.isLatestOPC = isLatestOPC;
        this.debug = configApplePayDebugMode === true;
        const directWalletConfig = window.multisafepayDirectWalletConfig;
        this.config = {
            applePayVersion: 10,
            supportedNetworks: ['amex', 'maestro', 'masterCard', 'visa', 'vPay'],
            merchantCapabilities: ['supports3DS'],
            billingContactFields: ['postalAddress', 'name', 'phone', 'email'],
            shippingContactFields: ['postalAddress', 'name', 'phone', 'email'],
            cartDetailsEndpoint: directWalletConfig.cartDetailsEndpoint,
            multiSafepayServerScript: directWalletConfig.applePaySessionEndpoint
        };

        this.init()
            .then(() => {
                debugDirect('Apple Pay Direct initialized', this.debug, 'log');
            })
            .catch(error => {
                console.error('Error initializing Apple Pay Direct:', error);
            });
    }

    /**
     * Initialize Apple Pay Direct
     *
     * @returns {Promise<void>}
     */
    async init()
    {
        try {
            await this.createApplePayButton();
        } catch (error) {
            console.error('Error creating Apple Pay button:', error);
        }
    }

    /**
     * Append the wallet button using the same confirmation wrapper structure
     * used on the initial checkout render.
     *
     * @param {HTMLElement} button
     * @param {HTMLElement} buttonContainer
     * @returns {boolean}
     */
    appendToConfirmationWrapper(button, buttonContainer)
    {
        const parentContainer = buttonContainer.parentElement;
        if (!parentContainer) {
            debugDirect('Button container not found', this.debug);
            return false;
        }

        const wrapperDiv = document.createElement('div');
        wrapperDiv.classList.add('multisafepay-wallet-button-wrapper');
        wrapperDiv.appendChild(button);
        parentContainer.appendChild(wrapperDiv);

        return true;
    }

    /**
     * Event handler for Apple Pay button click
     *
     * @param {Event} event
     * @returns {Promise<void>}
     */
    onApplePaymentButtonClicked = async(event) => {
        if (
            window.multisafepayCheckoutUtils &&
            typeof window.multisafepayCheckoutUtils.validateTheCheckoutBeforePayment === 'function' &&
            !await window.multisafepayCheckoutUtils.validateTheCheckoutBeforePayment()
        ) {
            event.preventDefault();
            event.stopImmediatePropagation();
            debugDirect('The Checkout validation blocked Apple Pay', this.debug, 'warn');
            return;
        }

        // Modern "One Page Checkout PS" disables the Apple Pay button and shows its own delivery-address alert.
        const reportOpcDeliveryValidity = !(this.isLatestOPC && this.containerId === 'payment-confirmation');
        const checkoutApprovalError = getDirectWalletCheckoutApprovalError('Apple Pay', { reportOpcDeliveryValidity });

        if (checkoutApprovalError) {
            debugDirect(checkoutApprovalError, this.debug, 'warn');
            return;
        }

        try {
            await this.beginApplePaySession();
        } catch (error) {
            console.error('Error starting Apple Pay session:', error);
        }
    }

    /**
     * Create Apple Pay button
     *
     * @returns {Promise<void>}
     */
    async createApplePayButton()
    {
        // Check if previous buttons already exist and remove them
        cleanUpDirectButtons();

        let buttonContainer = document.getElementById(this.containerId);
        if (!buttonContainer) {
            debugDirect('Button container not found', this.debug);
            return;
        }

        // Features of the button
        const button = document.createElement('button');
        button.className = 'apple-pay-button apple-pay-button-black';
        button.style.cursor = 'pointer';
        button.style.height = '40px';
        button.addEventListener('click', this.onApplePaymentButtonClicked);

        // Use the standard confirmation wrapper only outside legacy and modern One Page Checkout PS flows.
        // The Checkout is not excluded because it uses #confirm_order when available.
        if (this.containerId === 'payment-confirmation' && !this.isLegacyOPC && !this.isLatestOPC) {
            button.style.width = '160px';
            this.appendToConfirmationWrapper(button, buttonContainer);
            return;
        }

        if (this.isLegacyOPC || this.isLatestOPC) {
            // Create a wrapper div to avoid the PrestaShop automated disabling
            const wrapperDiv = document.createElement('div');

            // Add the click event to the wrapper to avoid the propagation,
            // so the button can be clicked without activate the redirect mode
            wrapperDiv.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            // Add the OPC classes to the button
            button.className += ' btn btn-primary btn-lg pull-right';
            if (this.isLegacyOPC) {
                const legacyContainer = document.querySelector('#' + this.containerId + ' > div');
                if (legacyContainer) {
                    buttonContainer = legacyContainer;
                } else {
                    debugDirect('Legacy One Page Checkout button container not found', this.debug, 'warn');
                }
            } else {
                if (buttonContainer) {
                    buttonContainer.style.textAlign = 'right';
                }
            }

            // Append the button to the wrapper
            wrapperDiv.appendChild(button);
            // Append the wrapper to the container
            buttonContainer.appendChild(wrapperDiv);
        } else {
            button.style.width = '160px';
            // Append the button to the "parent" container,
            // so we can avoid the automated disabling from PrestaShop
            buttonContainer = buttonContainer.parentElement;
            if (!buttonContainer) {
                debugDirect('Button container not found', this.debug);
                return;
            }

            buttonContainer.appendChild(button);
        }
    }

    /**
     * Create the Apple Pay payment request object and session
     *
     * Some variables from the global scope are launched from
     * the internal code of Prestashop
     *
     * @returns {Promise<void>}
     */
    async beginApplePaySession()
    {
        // OPC can refresh carrier/payment blocks without reloading Media::addJsDef values.
        // Refresh the cart details here so Apple Pay authorizes the same data sent in the OrderRequest.
        const cartDetails = await fetchDirectWalletCartDetails(
            this.config.cartDetailsEndpoint,
            'Apple Pay',
            this.debug
        );

        // Create the payment request object
        const paymentRequest = {
            countryCode: cartDetails.countryCode,
            currencyCode: cartDetails.currencyCode,
            merchantCapabilities: this.config.merchantCapabilities,
            supportedNetworks: this.config.supportedNetworks,
            total: {
                label: configApplePayMerchantName,
                type: 'final',
                amount: cartDetails.totalPrice.toFixed(2),
            },
            requiredBillingContactFields: this.config.billingContactFields,
            requiredShippingContactFields: this.config.shippingContactFields
        };

        // Create the session and handle the events
        const session = new ApplePaySession(this.config.applePayVersion, paymentRequest);
        session.onvalidatemerchant = (event) => this.handleValidateMerchant(event, session);
        session.onpaymentauthorized = (event) => this.handlePaymentAuthorized(event, session);
        session.begin();
    }

    /**
     * Fetch merchant session data from MultiSafepay
     *
     * @param {string} validationURL
     * @param {string} originDomain
     * @returns {Promise<object>}
     */
    async fetchMerchantSession(validationURL, originDomain)
    {
        const data = new URLSearchParams();
        data.append('validation_url', validationURL);
        data.append('origin_domain', originDomain);

        const response = await fetch(this.config.multiSafepayServerScript, {
            method: 'POST',
            body: data,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });

        return await response.json();
    }

    /**
     * Validate merchant
     *
     * @param {object} event
     * @param {object} session
     * @returns {Promise<void>}
     */
    handleValidateMerchant = async(event, session) => {
        try {
            const validationURL = event.validationURL;
            const originDomain = window.location.hostname;

            const merchantSession = await this.fetchMerchantSession(validationURL, originDomain);
            if (merchantSession && (typeof merchantSession === 'object')) {
                session.completeMerchantValidation(merchantSession);
            } else {
                debugDirect('Error validating merchant', this.debug);
                session.abort();
            }
        } catch (error) {
            console.error('Error validating merchant:', error);
            session.abort();
        }
    }

    /**
     * Handle payment authorized
     *
     * @param {object} event
     * @param {object} session
     * @returns {Promise<void>}
     */
    handlePaymentAuthorized = async(event, session) => {
        try {
            const paymentToken = JSON.stringify(event.payment.token);
            const success = await this.submitApplePayForm(paymentToken);
            if (success) {
                session.completePayment(ApplePaySession.STATUS_SUCCESS);
            } else {
                session.completePayment(ApplePaySession.STATUS_FAILURE);
                debugDirect('Error processing Apple Pay payment', this.debug);
            }
        } catch (error) {
            session.completePayment(ApplePaySession.STATUS_FAILURE);
            console.error('Error processing Apple Pay payment:', error);
        }
    }

    /**
     * Submit the Apple Pay form
     *
     * @param {string} paymentToken
     * @returns {Promise<boolean>}
     */
    async submitApplePayForm(paymentToken)
    {
        if (
            window.multisafepayCheckoutUtils &&
            typeof window.multisafepayCheckoutUtils.validateTheCheckoutBeforePayment === 'function' &&
            !await window.multisafepayCheckoutUtils.validateTheCheckoutBeforePayment()
        ) {
            debugDirect('The Checkout validation blocked Apple Pay', this.debug, 'warn');
            return false;
        }

        if ((typeof (paymentToken) !== 'string') || (paymentToken.trim() === '')) {
            debugDirect('Invalid payload provided', this.debug);
            return false;
        }

        const applepayForm = document.getElementById(
            'multisafepay-form-applepay'
        );

        if (!applepayForm) {
            debugDirect('Apple Pay form not found', this.debug);
            return false;
        }

        // Settings the features of the input field
        const inputField = document.createElement('input');
        inputField.type = 'hidden';
        inputField.name = 'payment_token';
        inputField.value = paymentToken;

        // Settings the features of the browser field
        const browserField = document.createElement('input');
        browserField.type = 'hidden';
        browserField.name = 'browser';
        browserField.value = getCustomerBrowserInfo();

        // Add the hidden field to the form including the token value
        applepayForm.appendChild(inputField);
        // Add the hidden field to the form including the browser info
        applepayForm.appendChild(browserField);
        // Submit the form automatically
        applepayForm.submit();
        return true;
    }
}
