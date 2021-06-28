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
        $invoice_address = $this->getCustomerAddress((int) $order->id_address_invoice);

        $customer_address = $this->createAddress(
            $invoice_address->address1,
            $invoice_address->address2,
            (new PrestaShopCountry($invoice_address->id_country))->iso_code,
            PrestaShopState::getNameById($invoice_address->id_state) ? PrestaShopState::getNameById($invoice_address->id_state) : '',
            $invoice_address->city,
            $invoice_address->postcode
        );

        return $this->createCustomer(
            $customer_address,
            $order->getCustomer()->email,
            $invoice_address->phone,
            $invoice_address->firstname,
            $invoice_address->lastname,
            '',
            '',
            $order->id_lang,
            $invoice_address->company
        );
    }

    /**
     * @param PrestaShopOrder $order
     * @return CustomerDetails
     */
    public function createDeliveryDetails(PrestaShopOrder $order): CustomerDetails
    {
        $shipping_address = $this->getCustomerAddress((int) $order->id_address_delivery);

        $delivery_address = $this->createAddress(
            $shipping_address->address1,
            $shipping_address->address2,
            (new PrestaShopCountry($shipping_address->id_country))->iso_code,
            PrestaShopState::getNameById($shipping_address->id_state) ? PrestaShopState::getNameById($shipping_address->id_state) : '',
            $shipping_address->city,
            $shipping_address->postcode
        );

        return $this->createCustomer(
            $delivery_address,
            $order->getCustomer()->email,
            $shipping_address->phone,
            $shipping_address->firstname,
            $shipping_address->lastname,
            '',
            '',
            $order->id_lang,
            $shipping_address->company
        );
    }

    /**
     * Return CustomerDetails object
     *
     * @param Address $address
     * @param string  $email_address
     * @param string  $phone_number
     * @param string  $first_name
     * @param string  $last_name
     * @param string  $ip_address
     * @param string  $user_agent
     * @param string  $company_name
     * @return CustomerDetails
     */
    private function createCustomer(
        Address $address,
        string $email_address,
        string $phone_number,
        string $first_name,
        string $last_name,
        string $ip_address,
        string $user_agent,
        string $lang_id,
        string $company_name = null
    ): CustomerDetails {
        $customer_details = new CustomerDetails();
        $customer_details
            ->addAddress($address)
            ->addEmailAddress(new EmailAddress($email_address))
            ->addFirstName($first_name)
            ->addLastName($last_name)
            ->addPhoneNumber(new PhoneNumber($phone_number))
            ->addLocale($this->getLanguageCode(PrestaShopLanguage::getIsoById((int) $lang_id)))
            ->addCompanyName($company_name ? $company_name : '');

        if (! empty($ip_address)) {
            $customer_details->addIpAddressAsString($ip_address);
        }

        if (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $customer_details->addForwardedIpAsString($_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        if (! empty($user_agent)) {
            $customer_details->addUserAgent($user_agent);
        }

        return $customer_details;
    }

    /**
     * Return Address object
     *
     * @param string $address_line_1
     * @param string $address_line_2
     * @param string $country
     * @param string $state
     * @param string $city
     * @param string $zip_code
     * @return Address
     */
    private function createAddress(
        string $address_line_1,
        string $address_line_2,
        string $country,
        string $state,
        string $city,
        string $zip_code
    ): Address {
        $address_parser = new AddressParser();
        $address        = $address_parser->parse($address_line_1, $address_line_2);
        $street       = $address[0];
        $house_number = $address[1];
        $customer_address = new Address();
        return $customer_address
            ->addStreetName($street)
            ->addHouseNumber($house_number)
            ->addState($state)
            ->addCity($city)
            ->addCountry(new Country($country))
            ->addZipCode($zip_code);
    }


    /**
     * Return locale code
     *
     * @param string $iso_code
     * @return string
     */
    private function getLanguageCode(string $iso_code): string
    {
        $locale = PrestaShopLanguage::getLanguageCodeByIso($iso_code);
        $parts = explode('-', (string) $locale);
        $language_code = $parts[0] . '_' . strtoupper($parts[1]);
        return $language_code;
    }

    /**
     * Return the Address by the given address id
     *
     * @param int $address_id
     * @return PrestaShopAddress
     */
    private function getCustomerAddress(int $address_id)
    {
        return new PrestaShopAddress((int) $address_id);
    }
}
