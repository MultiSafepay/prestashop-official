<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
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

use AddressCore as PrestaShopAddress;
use CountryCore as PrestaShopCountry;
use LanguageCore as PrestaShopLanguage;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use OrderCore as PrestaShopOrder;
use StateCore as PrestaShopState;

/**
 * Class CustomerService
 *
 * @todo Check for customer IP addresses and user agent arguments
 *
 * @package MultiSafepay\PrestaShop\Services
 */
class CustomerService
{
    /**
     * @param PrestaShopOrder $order
     * @return CustomerDetails
     */
    public function createCustomerDetails(PrestaShopOrder $order): CustomerDetails
    {
        $invoiceAddress = $this->getCustomerAddress((int) $order->id_address_invoice);

        $customerAddress = $this->createAddress(
            $invoiceAddress->address1,
            $invoiceAddress->address2,
            (new PrestaShopCountry($invoiceAddress->id_country))->iso_code,
            PrestaShopState::getNameById($invoiceAddress->id_state) ? PrestaShopState::getNameById($invoiceAddress->id_state) : '',
            $invoiceAddress->city,
            $invoiceAddress->postcode
        );

        return $this->createCustomer(
            $customerAddress,
            $order->getCustomer()->email,
            $invoiceAddress->phone,
            $invoiceAddress->firstname,
            $invoiceAddress->lastname,
            '',
            '',
            $order->id_lang,
            $invoiceAddress->company
        );
    }

    /**
     * @param PrestaShopOrder $order
     * @return CustomerDetails
     */
    public function createDeliveryDetails(PrestaShopOrder $order): CustomerDetails
    {
        $shippingAddress = $this->getCustomerAddress((int) $order->id_address_delivery);

        $deliveryAddress = $this->createAddress(
            $shippingAddress->address1,
            $shippingAddress->address2,
            (new PrestaShopCountry($shippingAddress->id_country))->iso_code,
            PrestaShopState::getNameById($shippingAddress->id_state) ? PrestaShopState::getNameById($shippingAddress->id_state) : '',
            $shippingAddress->city,
            $shippingAddress->postcode
        );

        return $this->createCustomer(
            $deliveryAddress,
            $order->getCustomer()->email,
            $shippingAddress->phone,
            $shippingAddress->firstname,
            $shippingAddress->lastname,
            '',
            '',
            $order->id_lang,
            $shippingAddress->company
        );
    }

    /**
     * Return CustomerDetails object
     *
     * @param Address $address
     * @param string  $emailAddress
     * @param string  $phoneNumber
     * @param string  $firstName
     * @param string  $lastName
     * @param string  $ipAddress
     * @param string  $userAgent
     * @param string  $companyName
     * @return CustomerDetails
     */
    private function createCustomer(
        Address $address,
        string $emailAddress,
        string $phoneNumber,
        string $firstName,
        string $lastName,
        string $ipAddress,
        string $userAgent,
        string $langId,
        string $companyName = null
    ): CustomerDetails {
        $customerDetails = new CustomerDetails();
        $customerDetails
            ->addAddress($address)
            ->addEmailAddress(new EmailAddress($emailAddress))
            ->addFirstName($firstName)
            ->addLastName($lastName)
            ->addPhoneNumber(new PhoneNumber($phoneNumber))
            ->addLocale($this->getLanguageCode(PrestaShopLanguage::getIsoById((int) $langId)))
            ->addCompanyName($companyName ? $companyName : '');

        if (! empty($ipAddress)) {
            $customerDetails->addIpAddressAsString($ipAddress);
        }

        if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $customerDetails->addForwardedIpAsString($_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        if (! empty($userAgent)) {
            $customerDetails->addUserAgent($userAgent);
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
        $locale = PrestaShopLanguage::getLanguageCodeByIso($isoCode);
        $parts = explode('-', (string) $locale);
        $languageCode = $parts[0] . '_' . strtoupper($parts[1]);
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
