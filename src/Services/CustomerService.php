<?php declare(strict_types=1);

namespace MultiSafepay\PrestaShop\Services;

use Address as PrestaShopAddress;
use Country as PrestaShopCountry;
use Language;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use Order;
use State;

/**
 * Class CustomerService
 * @package MultiSafepay\PrestaShop\Services
 */
class CustomerService
{
    /**
     * @param Order $order
     * @return CustomerDetails
     */
    public function createCustomerDetails(Order $order): CustomerDetails
    {
        $invoiceAddress = $this->getCustomerAddress((int) $order->id_address_invoice);

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
            $order->getCustomer()->email,
            $invoiceAddress->phone,
            $invoiceAddress->firstname,
            $invoiceAddress->lastname,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $this->getLanguageCode(Language::getIsoById((int) $order->id_lang)),
            $invoiceAddress->company,
            (string)$order->id_customer
        );
    }

    /**
     * @param Order $order
     * @return CustomerDetails
     */
    public function createDeliveryDetails(Order $order): CustomerDetails
    {
        $shippingAddress = $this->getCustomerAddress((int) $order->id_address_delivery);

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
            $order->getCustomer()->email,
            $shippingAddress->phone,
            $shippingAddress->firstname,
            $shippingAddress->lastname,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $this->getLanguageCode(Language::getIsoById((int) $order->id_lang)),
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
