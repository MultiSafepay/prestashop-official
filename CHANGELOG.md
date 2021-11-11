# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

***

## 5.0.0-RC-2
Release date: Nov 11th, 2021

### Added
+ PRES-227: Add Payment Component for Credit Card Payment Option.
+ PRES-203: Add logo uploader field for generic gateways.

## Fixed:
+ PRES-248: Fix method getLanguageCode to support language codes with two characters
+ PRES-243: Handle error response if a GET request reach the notification url, which is expecting only POST requests.

## Changed
+ PRES-244: Replace logo of Bancontact with new one

***

## 5.0.0-RC-1
Release date: Oct 27th, 2021

5.0.0 is a complete rewrite of the MultiSafepay payment module for PrestaShop.
The 5.x plugin can work simultaneously with an older version of the plugin without producing errors between them.
If you are upgrading from a 4.x version of our plugin, it is recommended to only disable the payment methods without uninstalling the version 4.x module until you are sure that all orders created through a payment method from that plugin has been fully processed. Once you are sure about that you can safely disable and remove the 4.x version of the plugin and leave only the latest version 5.x.

### Changed
+ Complete rewrite of the plugin.
+ Order flow has changed creating the order before the payment is submitted and processed, avoiding errors on previous versions like missing orders or duplicated orders.
+ Order reference is used as order id within the MultiSafepay transaction information instead of the cart id, making this one match with the information received by the customer and with the information listed in the PrestaShop orders page. 
+ Improve logging on debug mode to trace any information that might be important related with the behavior of the plugin.
+ Improve support for order collections, (multiple orders splitted from one single cart)
+ Improve support for multi stores.
+ Improve filters for each payment method which can be set by minimum amount, maximum amount, countries, currencies, customer group or selected carrrier.
+ Supported gateways: AfterPay, Alipay, American Express, Apple Pay, Bancontact, Bank Transfer, Belfius, Betaal per Maand, CBC, Credit card, Dotpay, E-Invoicing, EPS, Giropay, Google Pay, iDEAL, iDEAL QR, in3, ING Home'Pay, KBC, Klarna, Maestro, Mastercard, MultiSafepay, Pay After Delivery, PayPal, Paysafecard, Request to Pay, SEPA Direct Debit, Sofort, Trustly, TrustPay, Visa, WeChat Pay.
+ Supported gift cards: Baby Giftcard, Beauty and wellness, Boekenbon, Fashion gift card, Fashioncheque, Fietsenbon, Gezondheidsbon, Givacard, Good4fun Giftcard, Goodcard, Nationale tuinbon, Parfum cadeaukaart, Podium cadeaukaart, Sport & Fit, VVV Cadeaukaart, Webshop gift card, Wellness gift card, Wijncadeau, Winkelcheque, YourGift.
+ POST notifications, instead of using GET notifications.
+ Remove the bank details when invoicing the customer using Bank Transfer. This is handled by MultiSafepay directly.
+ Improve logo resolution of all payment methods.

### Added
+ Add 3 generic gateways which lets you connect to almost every payment method we offer, without updating the plugin.
+ Add tokenization support for American Express, Credit Card, SEPA Direct Debit, iDEAL, Maestro, Mastercard and VISA gateways.
+ Add support to switch between direct and redirect transactions in SEPA Direct Debit, AfterPay, Bank Transfer, E-Invoicing, iDEAL, in3, Pay After Delivery.
+ Add support to send the invoice ID to MultiSafepay when a transaction is invoiced within PrestaShop.
+ Add a new page in the customer account section to allow the customer to remove their tokens.
+ Add a settings field to define a custom order description.
+ Add a settings field to select if the confirmation order email should be sent after a customer places an order, but before the merchant receives the payment.
+ Add drag & drop support to sort the payment options in the backoffice settings page and checkout page. 
+ Add new element in the backoffice main menu, under "Improve" section, to easily access the MultiSafepay settings page.
