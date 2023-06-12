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

(function ($) {
    $(function () {
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

        const labelTos = document.querySelector(
            '.condition-label label[for="conditions_to_approve[terms-and-conditions]"]'
        );

        const checkboxTos = document.getElementById(
            'conditions_to_approve[terms-and-conditions]'
        );


        const cardPaymentMethod = Object.assign(
            {tokenizationSpecification: tokenizationSpecification},
            baseCardPaymentMethod
        );

        const paymentsClient = new google.payments.api.PaymentsClient({environment: configEnvironment});
        const isReadyToPayRequest = Object.assign({}, baseRequest);
        isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];

        function addGooglePayButton()
        {
            const buttonContainer = document.getElementById(
                'payment-confirmation'
            );
            const button = paymentsClient.createButton({
                buttonType: 'plain',
                buttonColor: 'black',
                onClick: onGooglePaymentButtonClicked
            });
            if (buttonContainer) {
                buttonContainer.parentElement.appendChild(button);
            }
        }

        function getGooglePaymentDataRequest()
        {
            const paymentDataRequest = Object.assign({}, baseRequest);
            paymentDataRequest.allowedPaymentMethods = [cardPaymentMethod];
            paymentDataRequest.transactionInfo = {
                totalPriceStatus: 'FINAL',
                totalPrice: configTotalPrice.toString(),
                currencyCode: configCurrencyCode,
                countryCode: configCountryCode
            };
            paymentDataRequest.merchantInfo = {
                merchantName: configMerchantName,
                merchantId: configMerchantId
            };
            return paymentDataRequest;
        }

        function onGooglePaymentButtonClicked()
        {
            if (checkboxTos) {
                if (checkboxTos.checked) {
                    const paymentDataRequest = getGooglePaymentDataRequest();
                    paymentsClient.loadPaymentData(paymentDataRequest)
                        .then(function (paymentData) {
                            processGooglePayment(paymentData);
                        })
                        .catch(function (err) {
                            console.log(err);
                        });
                } else {
                    if (labelTos) {
                        const conditionsToApprove = document.getElementById('conditions-to-approve');
                        if (conditionsToApprove) {
                            if (!conditionsToApprove.checkValidity()) {
                                conditionsToApprove.reportValidity();
                            }
                        }
                    }
                }
            }
        }

        function submitGooglePayForm(tokenValue)
        {
            if ((typeof(tokenValue) !== 'string') || (tokenValue.trim() === '')) {
                console.error('Invalid payload provided');
                return;
            }

            const googlepayForm = document.getElementById(
                'multisafepay-form-googlepay'
            );
            if (!googlepayForm) {
                console.error('Google Pay form not found');
                return;
            }

            const inputField = document.createElement('input');
            inputField.type = 'hidden';
            inputField.name = 'payment_token';
            inputField.value = tokenValue;

            googlepayForm.appendChild(inputField);
            googlepayForm.submit();
        }

        function processGooglePayment(paymentData)
        {
            // Extract the token from the payment data sent by Google Pay
            const payload = paymentData.paymentMethodData.tokenizationData.token;
            submitGooglePayForm(payload);
        }

        function onGooglePayLoaded()
        {
            // Place order button position
            const placeOrder = document.querySelector(
                '#payment-confirmation div.ps-shown-by-js'
            );

            // Check if the payment option is Google Pay
            const isGooglePay = (dataModuleName) => dataModuleName && dataModuleName.includes('GOOGLEPAY');

            // Toggle the place order button
            const togglePlaceOrderButton = (display) => {
                if (placeOrder) {
                    placeOrder.setAttribute(
                        'style',
                        'display: ' + display + ' !important'
                    );
                }
            };

            const removeGooglePayButtons = () => {
                const gpayButtons = document.querySelectorAll('.gpay-button');
                gpayButtons.forEach((button) => {
                    button.parentElement.parentElement.removeChild(button.parentElement);
                });
            };

            // Bind click event for Google Pay
            const googlePayClickEvent = (paymentId) => {
                document.getElementById(paymentId).addEventListener('click', () => {
                    togglePlaceOrderButton('none');

                    // Check if Google Pay is available on the device
                    if (paymentsClient && paymentsClient.isReadyToPay) {
                        if (isReadyToPayRequest.allowedPaymentMethods.length === 0) {
                            return;
                        }

                        paymentsClient.isReadyToPay(isReadyToPayRequest)
                            .then((response) => {
                                if (response.result) {
                                    addGooglePayButton();
                                }
                            })
                            .catch((err) => {
                                // Show error in developer console for debugging
                                console.error(err);
                            });
                    }
                });
            };

            // Bind click event for other payment options
            const otherPaymentClickEvent = (paymentId) => {
                document.getElementById(paymentId).addEventListener('click', () => {
                    togglePlaceOrderButton('block');
                    removeGooglePayButtons();
                });
            };

            // Loop through all payment options and bind click events
            document.querySelectorAll('[id^="payment-option-"]').forEach((element) => {
                const dataModuleName = element.getAttribute('data-module-name');
                const paymentId = element.getAttribute('id');

                if ((dataModuleName !== null) && !paymentId.includes('container')) {
                    if (isGooglePay(dataModuleName)) {
                        googlePayClickEvent(paymentId);
                    } else if (dataModuleName || (dataModuleName === '')) { // Gateway name could be defined but empty
                        otherPaymentClickEvent(paymentId);
                    }
                }
            });
        }
        onGooglePayLoaded();
    });
})(jQuery);
