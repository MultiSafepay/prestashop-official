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

namespace MultiSafepay\Tests\Services;

use Multisafepay;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\PrestaShop\PaymentOptions\PaymentMethods\Dirdeb;
use MultiSafepay\PrestaShop\Services\NotificationService;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Tests\BaseMultiSafepayTest;

class NotificationServiceTest extends BaseMultiSafepayTest
{
    /** @var string  */
    protected $rawPostNotification;

    /** @var NotificationService  */
    protected $notificationService;

    public function setUp(): void
    {
        parent::setUp();
        $this->rawPostNotification = '{"amount":3161,"amount_refunded":0,"checkout_options":{"alternate":[{"name":"21","rules":[{"country":"","rate":"0.21"}],"standalone":""}],"default":[]},"costs":[],"created":"2021-09-16T12:54:00","currency":"EUR","custom_info":{"custom_1":null,"custom_2":null,"custom_3":null},"customer":{"address1":"Kraanspoor,","address2":null,"city":"Amsterdam","country":"NL","country_name":null,"email":"example@multisafepay.com","first_name":"John","house_number":39,"last_name":"Doe","locale":"en_US","phone1":null,"phone2":"","state":null,"zip_code":"1033SC"},"description":"Payment for order: VQWGYTXNT","fastcheckout":"NO","financial_status":"initialized","items":"<table border=\"0\" cellpadding=\"5\" width=\"100%\">\n<tr>\n<th width=\"10%\"><font size=\"2\" face=\"Verdana\">Quantity </font></th>\n<th align=\"left\"></th>\n<th align=\"left\"><font size=\"2\" face=\"Verdana\">Details </font></th>\n<th width=\"19%\" align=\"right\"><font size=\"2\" face=\"Verdana\">Price </font></th>\n</tr>\n<tr>\n<td align=\"center\"><font size=\"2\" face=\"Verdana\">1</font></td>\n<td width=\"6%\"></td>\n<td width=\"65%\"><font size=\"2\" face=\"Verdana\">Hummingbird printed t-shirt ( S- Black )</font></td>\n<td align=\"right\">&euro;<font size=\"2\" face=\"Verdana\">19.12</font>\n</td>\n</tr>\n<tr>\n<td align=\"center\"><font size=\"2\" face=\"Verdana\">1</font></td>\n<td width=\"6%\"></td>\n<td width=\"65%\"><font size=\"2\" face=\"Verdana\">My carrier</font></td>\n<td align=\"right\">&euro;<font size=\"2\" face=\"Verdana\">7.00</font>\n</td>\n</tr>\n<tr bgcolor=\"#E9F1F7\">\n<td colspan=\"3\" align=\"right\"><font size=\"2\" face=\"Verdana\">VAT:</font></td>\n<td align=\"right\">&euro;<font size=\"2\" face=\"Verdana\">5.49</font>\n</td>\n</tr>\n<tr bgcolor=\"#E9F1F7\">\n<td colspan=\"3\" align=\"right\"><font size=\"2\" face=\"Verdana\">Total:</font></td>\n<td align=\"right\">&euro;<font size=\"2\" face=\"Verdana\">31.61</font>\n</td>\n</tr>\n</table>","modified":"2021-09-16T12:54:00","order_adjustment":{"total_adjustment":5.49,"total_tax":5.49},"order_id":"VQWGYTXNT","order_total":31.61,"payment_details":{"account_bic":"ABNANL2A","account_holder_name":"John Doe","account_iban":"NL87ABNA0000000001","account_id":"1","external_transaction_id":"3202125849722770","recurring_flow":null,"recurring_id":"9989673550264204568","recurring_model":null,"type":"DIRDEB"},"payment_methods":[{"account_bic":"ABNANL2A","account_holder_name":"John Doe","account_iban":"NL87ABNA0000000001","account_id":"1","amount":3161,"currency":"EUR","description":"Payment for order: VQWGYTXNT","external_transaction_id":"3202125849722770","payment_description":"Direct Debit","status":"initialized","type":"DIRDEB"}],"reason":"","reason_code":"","related_transactions":null,"shopping_cart":{"items":[{"cashback":"","currency":"EUR","description":"","image":"","merchant_item_id":"1-2","name":"Hummingbird printed t-shirt ( S- Black )","options":[],"product_url":"","quantity":"1","tax_table_selector":"21","unit_price":"19.1200000000","weight":{"unit":"KG","value":"0.3"}},{"cashback":"","currency":"EUR","description":"","image":"","merchant_item_id":"msp-shipping","name":"My carrier","options":[],"product_url":"","quantity":"1","tax_table_selector":"21","unit_price":"7.00","weight":{"unit":null,"value":null}}]},"status":"initialized","transaction_id":4972277,"var1":null,"var2":null,"var3":null}';

        $mockMultisafepay = $this->getMockBuilder(Multisafepay::class)->getMock();
        $mockSdk = $this->getMockBuilder(SdkService::class)->getMock();
        $mockPaymentOptionService = $this->getMockBuilder(PaymentOptionService::class)->disableOriginalConstructor()->onlyMethods(['getMultiSafepayPaymentOption'])->getMock();

        $mockDirDeb= $this->getMockBuilder(Dirdeb::class)->setConstructorArgs([$mockMultisafepay])->onlyMethods(['allowTokenization'])->getMock();

        $mockDirDeb->method('allowTokenization')->willReturn(
            false
        );

        $mockPaymentOptionService->method('getMultiSafepayPaymentOption')->willReturn($mockDirDeb);
        $mockNotificationService = $this->getMockBuilder(NotificationService::class)->setConstructorArgs(
            [$mockMultisafepay, $mockSdk, $mockPaymentOptionService]
        )->onlyMethods([])->getMock();

        $this->notificationService = $mockNotificationService;
    }

    public function testGetTransactionFromPostNotification()
    {
        $output = $this->notificationService->getTransactionFromPostNotification($this->rawPostNotification);
        self::assertInstanceOf(TransactionResponse::class, $output);
    }

    public function testFailToGetTransactionFromEmptyBodyPostNotification()
    {
        self::expectException(\TypeError::class);
        $output = $this->notificationService->getTransactionFromPostNotification('');
    }

    public function testGetOrderStatusId()
    {
        $orderStatusId = $this->notificationService->getOrderStatusId('completed');
        self::assertIsString($orderStatusId);
    }
}
