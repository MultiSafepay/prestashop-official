<?php
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
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Services;

use Address as PrestaShopAddress;
use Cart;
use Country as PrestaShopCountry;
use Customer;
use Language;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use Order;
use State;
use Tools;

/**
 * Class CustomerService
 * @package MultiSafepay\PrestaShop\Services
 */
class CustomerService
{
    /**
     * @param Cart $cart
     * @param Customer $customer
     *
     * @return CustomerDetails
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function createCustomerDetails(Cart $cart, Customer $customer): CustomerDetails
    {
        $invoiceAddress = $this->getCustomerAddress((int) $cart->id_address_invoice);

        $customerAddress = $this->createAddress(
            $invoiceAddress->address1,
            $invoiceAddress->address2,
            (new PrestaShopCountry($invoiceAddress->id_country))->iso_code,
            State::getNameById($invoiceAddress->id_state) ?: '',
            $invoiceAddress->city,
            $invoiceAddress->postcode
        );

        return $this->createCustomer(
            $customerAddress,
            $customer->email,
            $invoiceAddress->phone,
            $invoiceAddress->firstname,
            $invoiceAddress->lastname,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $this->getLanguageCode(Language::getIsoById((int) $cart->id_lang)),
            $invoiceAddress->company,
            (string)$customer->id
        );
    }

    /**
     * @param Cart $cart
     * @param Customer $customer
     *
     * @return CustomerDetails
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function createDeliveryDetails(Cart $cart, Customer $customer): CustomerDetails
    {
        $shippingAddress = $this->getCustomerAddress((int) $cart->id_address_delivery);

        $deliveryAddress = $this->createAddress(
            $shippingAddress->address1,
            $shippingAddress->address2,
            (new PrestaShopCountry($shippingAddress->id_country))->iso_code,
            State::getNameById($shippingAddress->id_state) ?: '',
            $shippingAddress->city,
            $shippingAddress->postcode
        );

        return $this->createCustomer(
            $deliveryAddress,
            $customer->email,
            $shippingAddress->phone,
            $shippingAddress->firstname,
            $shippingAddress->lastname,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $this->getLanguageCode(Language::getIsoById((int) $cart->id_lang)),
            $shippingAddress->company
        );
    }

    /**
     * @param Address $address
     * @param string $emailAddress
     * @param string $phoneNumber
     * @param string $firstName
     * @param string $lastName
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @param string|null $reference
     * @param string $languageCode
     * @param string|null $companyName
     *
     * @return CustomerDetails
     */
    public function createCustomer(
        Address $address,
        string $emailAddress,
        string $phoneNumber,
        string $firstName,
        string $lastName,
        ?string $ipAddress,
        ?string $userAgent,
        string $languageCode,
        string $companyName = null,
        string $reference = null
    ): CustomerDetails {
        $customerDetails = new CustomerDetails();
        $customerDetails
            ->addAddress($address)
            ->addEmailAddress(new EmailAddress($emailAddress))
            ->addFirstName($firstName)
            ->addLastName($lastName)
            ->addPhoneNumber(new PhoneNumber($phoneNumber))
            ->addLocale($languageCode)
            ->addCompanyName($companyName ?: '');

        if (! empty($ipAddress)) {
            $customerDetails->addIpAddressAsString($ipAddress);
        }

        if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $customerDetails->addForwardedIpAsString($_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        if (! empty($userAgent)) {
            $customerDetails->addUserAgent($userAgent);
        }

        if (! empty($reference)) {
            $customerDetails->addReference($reference);
        }

        return $customerDetails;
    }

    /**
     * Return Address object
     *
     * @param string $addressLine1
     * @param string $addressLine2
     * @param string $country
     * @param string $state
     * @param string $city
     * @param string $zipCode
     * @return Address
     */
    private function createAddress(
        string $addressLine1,
        string $addressLine2,
        string $country,
        string $state,
        string $city,
        string $zipCode
    ): Address {
        $addressParser   = new AddressParser();
        $address         = $addressParser->parse($addressLine1, $addressLine2);
        $street          = $address[0];
        $houseNumber     = $address[1];
        $customerAddress = new Address();
        return $customerAddress
            ->addStreetName($street)
            ->addHouseNumber($houseNumber)
            ->addState($state)
            ->addCity($city)
            ->addCountry(new Country($country))
            ->addZipCode($zipCode);
    }


    /**
     * Return locale code
     *
     * @param string $isoCode
     * @return string
     */
    private function getLanguageCode(string $isoCode): string
    {
        $locale = Language::getLanguageCodeByIso($isoCode);

        if (Tools::strlen($locale) === 2) {
            return Tools::strtolower($locale) . '_' . Tools::strtoupper($locale);
        }

        $parts = explode('-', (string) $locale);
        $languageCode = Tools::strtolower($parts[0]) . '_' . Tools::strtoupper($parts[1]);
        return $languageCode;
    }

    /**
     * Return the Address by the given address id
     *
     * @param int $addressId
     * @return PrestaShopAddress
     */
    private function getCustomerAddress(int $addressId)
    {
        return new PrestaShopAddress((int) $addressId);
    }
}
