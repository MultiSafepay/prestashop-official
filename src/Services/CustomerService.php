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

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use Order as PrestaShopOrder;
use Address as PrestaShopAddress;
use Country as PrestaShopCountry;
use Language as PrestaShopLanguage;
use State as PrestaShopState;

/**
 * Class CustomerService
 *
 * @package MultiSafepay\PrestaShop\Services
 */
class CustomerService {

    public function __construct( PrestaShopOrder $order )
    {
        $this->order            = $order;
        $this->customer         = $this->order->getCustomer();
        $this->invoice_address  = new PrestaShopAddress($this->order->id_address_invoice);
        $this->invoice_country  = new PrestaShopCountry($this->invoice_address->id_country);
        $this->shipping_address = new PrestaShopAddress($this->order->id_address_delivery);
        $this->shipping_country = new PrestaShopCountry($this->shipping_address->id_country);
    }

    /**
     * @param Order $order
     * @return CustomerDetails
     */
    public function create_customer_details(): CustomerDetails {
        $customer_address = $this->create_address(
            $this->invoice_address->address1,
            $this->invoice_address->address2,
            $this->invoice_country->iso_code,
            PrestaShopState::getNameById($this->invoice_address->id_state) ? PrestaShopState::getNameById($this->invoice_address->id_state) : '',
            $this->invoice_address->city,
            $this->invoice_address->postcode
        );
        return $this->create_customer(
            $customer_address,
            $this->customer->email,
            $this->invoice_address->phone,
            $this->invoice_address->firstname,
            $this->invoice_address->lastname,
            '',
            '',
            $this->invoice_address->company
        );
    }

    /**
     * @param WC_Order $order
     * @return CustomerDetails
     */
    public function create_delivery_details(): CustomerDetails {
        $delivery_address = $this->create_address(
            $this->shipping_address->address1,
            $this->shipping_address->address2,
            $this->shipping_country->iso_code,
            PrestaShopState::getNameById($this->shipping_address->id_state) ? PrestaShopState::getNameById($this->shipping_address->id_state) : '',
            $this->shipping_address->city,
            $this->shipping_address->postcode
        );

        return $this->create_customer(
            $delivery_address,
            $this->customer->email,
            $this->shipping_address->phone,
            $this->shipping_address->firstname,
            $this->shipping_address->lastname,
            '',
            '',
            $this->invoice_address->company
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
    private function create_customer(
        Address $address,
        string $email_address,
        string $phone_number,
        string $first_name,
        string $last_name,
        string $ip_address,
        string $user_agent,
        string $company_name = null
    ): CustomerDetails {
        $customer_details = new CustomerDetails();
        $customer_details
            ->addAddress($address)
            ->addEmailAddress(new EmailAddress($email_address))
            ->addFirstName( $first_name )
            ->addLastName( $last_name )
            ->addPhoneNumber( new PhoneNumber( $phone_number ) )
            ->addLocale( $this->getLanguageCode( PrestaShopLanguage::getIsoById($this->order->id_lang)))
            ->addCompanyName( $company_name ? $company_name : '' );

        if ( ! empty( $ip_address ) ) {
            $customer_details->addIpAddressAsString( $ip_address );
        }

        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $customer_details->addForwardedIpAsString( $_SERVER['HTTP_X_FORWARDED_FOR'] );
        }

        if ( ! empty( $user_agent ) ) {
            $customer_details->addUserAgent( $user_agent );
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
    private function create_address(
        string $address_line_1,
        string $address_line_2,
        string $country,
        string $state,
        string $city,
        string $zip_code
    ): Address {
        $address_parser = new AddressParser();
        $address        = $address_parser->parse( $address_line_1, $address_line_2 );
        $street       = $address[0];
        $house_number = $address[1];
        $customer_address = new Address();
        return $customer_address
            ->addStreetName( $street )
            ->addHouseNumber( $house_number )
            ->addState( $state )
            ->addCity( $city )
            ->addCountry( new Country( $country ) )
            ->addZipCode( $zip_code );
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
        $parts = explode('-', $locale);
        $language_code = $parts[0] . '_' . strtoupper($parts[1]);
        return $language_code;
    }

}
