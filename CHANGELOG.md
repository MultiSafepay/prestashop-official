# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

***

## 5.6.2
Release date: Jan 11th, 2023

### Fixed
+ PRES-352: Fix an issue where a partial refund is processed, where it should not be possible, because a voucher is being generated on PrestaShop versions lower than 1.7.7.0

***

## 5.6.1
Release date: Dec 29th, 2022 

### Fixed
+ PRES-350: Fix partial refunds, which was failing in PrestaShop versions lower than 1.7.7.0 

***

## 5.6.0
Release date: Dec 5th, 2022 

### Added
+ DAVAMS-485: Add MyBank
+ DAVAMS-522: Add Amazon Pay
+ PRES-345: Add a setting field to define final order statuses

### Fixed
+ PRES-346: Fix refunds calculating the tax amount incorrectly when site is displaying prices without tax

### Changed
+ DAVAMS-544: AfterPay -> Riverty rebrand

***

## 5.5.1
Release date: July 7th, 2022

### Added
+ PRES-342: Extend compatibility of Payment Component with One Page Checkout PrestaShop version 4.1.X

### Fixed
+ PRES-339: Removing trailing comma which produces an error in PHP 7.2

***

## 5.5.0
Release date: May 25th, 2022

### Added
+ PRES-305: Add a new settings field to switch between order flows. Order can be created after or before the payment
+ DAVAMS-477: Add Alipay+ Payment Option
+ DAVAMS-467: Add a terms and conditions checkbox when AfterPay is set as direct 

### Fixed
+ PRES-335: Fix error in system report section, when a module is active in database but files has been deleted

***

## 5.4.2
Release date: Apr 20th, 2022

### Fixed
+  PRES-332: Fix missing service error in settings page affecting PrestaShop 1.7.7.5


***

## 5.4.1
Release date: Apr 7th, 2022

### Fixed
+  PRES-322: Give declined transactions the "Payment error" status
+  PRES-323: Show declined error to the customer when using Payment Component


***

## 5.4.0
Release date: Mar 17th, 2022

### Added
+ PRES-313: Add System Report section in settings page

### Fixed
+  PRES-310: Prevent cancel order via cancel_url if order have current status is not initialized or backorder unpaid


***

## 5.3.0
Release date: Mar 2nd, 2022

### Added
+ PRES-313: Add setting field to disable the shopping cart within the Order request

### Fixed
+ PRES-304: Change order status on payment complete to backorder paid, when an order contains items without stock
+ PRES-309: Set invoice_number within the order object, for backorders paid
+ PRES-310: Prevent cancel completed orders
+ PRES-312: Fix the image of credit card within the payment component CVV field

### Changed
+ PRES-306: Replace MultiSafepay logos according with new brand guidelines
+ PRES-311: Move log files to var/log/ directory

***

## 5.2.0
Release date: Jan 25th, 2022

### Added
+ PRES-293: Add payment component support for payment options: Visa, Mastercard, Maestro and American Express
+ PRES-252: Delete the file, when remove the logo image assigned to a generic gateway
+ PRES-190: Add support to translate payment methods names using PrestaShop translation system
+ PRES-250: Return error messages in settings page when there is an error uploading the file for a generic gateway image

### Changed
+ PRES-296: Set the payment option name (payment method), within the OrderPayment object when register the payment instead register the name of the payment module.

### Fixed
+ PRES-300: Fix order notes when an order has been created using credit card payment option

***

## 5.1.1
Release date: Dec 8th, 2021

### Fixed
+ PRES-289: Prevent sending invoice id when invoice has not been properly generated

***

## 5.1.0
Release date: Dec 1st, 2021

### Added
+ PRES-281: Add support for [One Page Checkout PS](https://addons.prestashop.com/en/express-checkout-process/8503-one-page-checkout-ps-easy-fast-intuitive.html) module
+ PRES-278: Add support for [The Checkout](https://addons.prestashop.com/en/express-checkout-process/42005-the-checkout.html) module

### Fixed
+ PRES-282: Fix text not translating properly
+ PRES-277: Avoid initialize Payment Component if container is not present

### Removed
+ PRES-286: Remove ING Home'Pay Payment Option

***

## 5.0.0
Release date: Nov 22th, 2021

### Added
+ PRES-269: Add message in settings page if current version is not latest one.
+ PRES-261: Use tokenization feature within the Payment Component.
+ PRES-263: Add placeholder to text fields in the settings page.
+ PRES-268: Log cart summary.

### Fixed
+ PRES-262: Fix uninstaller due to missing method ObjectModel->softDelete() on versions lower than 1.7.6.
+ PRES-260: Fix missing method Language->getLanguageCode producing errors when Payment Component loads on versions lower than 1.7.6.

### Changed
+ PRES-253: Allow API Key fields to be empty.
+ PRES-266: Change hook used to load JS and CSS in backoffice.

***

## 5.0.0-RC-2
Release date: Nov 11th, 2021

### Added
+ PRES-227: Add Payment Component for Credit Card Payment Option.
+ PRES-203: Add logo uploader field for generic gateways.

### Fixed
+ PRES-248: Fix method getLanguageCode to support language codes with two characters
+ PRES-243: Handle error response if a GET request reach the notification url, which is expecting only POST requests.

### Changed
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
