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
    constructor()
    {
        this.debug = configApplePayDebugMode === true;
        this.config = {
            applePayVersion: 10,
            supportedNetworks: ['amex', 'maestro', 'masterCard', 'visa', 'vPay'],
            merchantCapabilities: ['supports3DS'],
            billingContactFields: ['postalAddress', 'name', 'phone', 'email'],
            shippingContactFields: ['postalAddress', 'name', 'phone', 'email'],
            multiSafepayServerScript: './index.php?fc=module&module=multisafepayofficial&controller=applepaysession'
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
     * Event handler for Apple Pay button click
     *
     * @returns {Promise<void>}
     */
    onApplePaymentButtonClicked = async() => {
        const checkTos = isTosChecked();
        if (checkTos) {
            try {
                await this.beginApplePaySession();
            } catch (error) {
                console.error('Error starting Apple Pay session:', error);
            }
        } else {
            debugDirect('Terms of Service for Apple Pay not checked', this.debug, 'warn');
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

        const buttonContainer = document.getElementById('payment-confirmation');
        if (!buttonContainer) {
            debugDirect('Button container not found', this.debug);
            return;
        }

        // Features of the button
        const button = document.createElement('button');
        button.className = 'apple-pay-button apple-pay-button-black';
        button.style.cursor = 'pointer';
        button.addEventListener('click', this.onApplePaymentButtonClicked);

        // Append the button to the "parent" container,
        // to avoid the automated disabling from PrestaShop
        buttonContainer.parentElement.appendChild(button);
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
        // Create the payment request object
        const paymentRequest = {
            countryCode: configApplePayCountryCode,
            currencyCode: configApplePayCurrencyCode,
            merchantCapabilities: this.config.merchantCapabilities,
            supportedNetworks: this.config.supportedNetworks,
            total: {
                label: configApplePayMerchantName,
                type: 'final',
                amount: configApplePayTotalPrice.toFixed(2),
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
