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
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\PrestaShop\Builder\OrderRequest\CustomerBuilder;

use Address as PrestaShopAddress;
use Country;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use State;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class AddressBuilder
 * @package MultiSafepay\PrestaShop\Builder\OrderRequest\CustomerBuilder
 */
class AddressBuilder
{
    /**
     * @param PrestaShopAddress $address
     *
     * @return Address
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function build(
        PrestaShopAddress $address
    ): Address {
        return $this->createAddress(
            $address->address1,
            $address->address2,
            (new Country($address->id_country))->iso_code,
            State::getNameById($address->id_state) ?: '',
            $address->city,
            $address->postcode
        );
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
            ->addCountry(new \MultiSafepay\ValueObject\Customer\Country($country))
            ->addZipCode($zipCode);
    }
}
