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

namespace MultiSafepay\PrestaShop\Util;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;

/**
 * Class CustomerUtil
 * @package MultiSafepay\PrestaShop\Util
 */
class CustomerUtil
{
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
}
