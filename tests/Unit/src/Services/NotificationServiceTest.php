<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      TechSupport <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace MultiSafepay\Tests\Services;

use Configuration;
use Exception;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Api\Transactions\TransactionResponse\PaymentDetails;
use MultiSafepay\PrestaShop\PaymentOptions\Base\BasePaymentOption;
use MultiSafepay\PrestaShop\Services\NotExistingOrderNotificationService;
use MultiSafepay\PrestaShop\Services\NotificationService;
use MultiSafepay\PrestaShop\Services\PaymentOptionService;
use MultiSafepay\PrestaShop\Services\OrderService;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use MultisafepayOfficial;
use Order;
use OrderState;
use PrestaShopDatabaseException;
use PrestaShopException;
use ReflectionClass;
use ReflectionException;
use TypeError;
use Validate;

class NotificationServiceTest extends BaseMultiSafepayTest
{
    /** @var string */
    protected $rawPostNotification;

    /** @var NotificationService */
    protected $notificationService;

    /** @var PaymentOptionService */
    protected $paymentOptionService;

    /**
     * Set up test dependencies and a representative notification payload.
     *
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->rawPostNotification = '{"amount":3161,"amount_refunded":0,"checkout_options":{"alternate":[{"name":"21","rules":[{"country":"","rate":"0.21"}],"standalone":""}],"default":[]},"costs":[],"created":"2021-09-16T12:54:00","currency":"EUR","custom_info":{"custom_1":null,"custom_2":null,"custom_3":null},"customer":{"address1":"Kraanspoor,","address2":null,"city":"Amsterdam","country":"NL","country_name":null,"email":"example@multisafepay.com","first_name":"John","house_number":39,"last_name":"Doe","locale":"en_US","phone1":null,"phone2":"","state":null,"zip_code":"1033SC"},"description":"Payment for order: VQWGYTXNT","fastcheckout":"NO","financial_status":"initialized","items":"<table border=\"0\" cellpadding=\"5\" width=\"100%\">\n<tr>\n<th width=\"10%\"><font size=\"2\" face=\"Verdana\">Quantity </font></th>\n<th align=\"left\"></th>\n<th align=\"left\"><font size=\"2\" face=\"Verdana\">Details </font></th>\n<th width=\"19%\" align=\"right\"><font size=\"2\" face=\"Verdana\">Price </font></th>\n</tr>\n<tr>\n<td align=\"center\"><font size=\"2\" face=\"Verdana\">1</font></td>\n<td width=\"6%\"></td>\n<td width=\"65%\"><font size=\"2\" face=\"Verdana\">Hummingbird printed t-shirt (S-Black)</font></td>\n<td align=\"right\">&euro;<font size=\"2\" face=\"Verdana\">19.12</font>\n</td>\n</tr>\n<tr>\n<td align=\"center\"><font size=\"2\" face=\"Verdana\">1</font></td>\n<td width=\"6%\"></td>\n<td width=\"65%\"><font size=\"2\" face=\"Verdana\">My carrier</font></td>\n<td align=\"right\">&euro;<font size=\"2\" face=\"Verdana\">7.00</font>\n</td>\n</tr>\n<tr bgcolor=\"#E9F1F7\">\n<td colspan=\"3\" align=\"right\"><font size=\"2\" face=\"Verdana\">VAT:</font></td>\n<td align=\"right\">&euro;<font size=\"2\" face=\"Verdana\">5.49</font>\n</td>\n</tr>\n<tr bgcolor=\"#E9F1F7\">\n<td colspan=\"3\" align=\"right\"><font size=\"2\" face=\"Verdana\">Total:</font></td>\n<td align=\"right\">&euro;<font size=\"2\" face=\"Verdana\">31.61</font>\n</td>\n</tr>\n</table>","modified":"2021-09-16T12:54:00","order_adjustment":{"total_adjustment":5.49,"total_tax":5.49},"order_id":"VQWGYTXNT","order_total":31.61,"payment_details":{"account_bic":"ABNANL2A","account_holder_name":"John Doe","account_iban":"NL87ABNA0000000001","account_id":"1","external_transaction_id":"3202125849722770","recurring_flow":null,"recurring_id":"9989673550264204568","recurring_model":null,"type":"DIRDEB"},"payment_methods":[{"account_bic":"ABNANL2A","account_holder_name":"John Doe","account_iban":"NL87ABNA0000000001","account_id":"1","amount":3161,"currency":"EUR","description":"Payment for order: VQWGYTXNT","external_transaction_id":"3202125849722770","payment_description":"Direct Debit","status":"initialized","type":"DIRDEB"}],"reason":"","reason_code":"","related_transactions":null,"shopping_cart":{"items":[{"cashback":"","currency":"EUR","description":"","image":"","merchant_item_id":"1-2","name":"Hummingbird printed t-shirt ( S- Black )","options":[],"product_url":"","quantity":"1","tax_table_selector":"21","unit_price":"19.1200000000","weight":{"unit":"KG","value":"0.3"}},{"cashback":"","currency":"EUR","description":"","image":"","merchant_item_id":"msp-shipping","name":"My carrier","options":[],"product_url":"","quantity":"1","tax_table_selector":"21","unit_price":"7.00","weight":{"unit":null,"value":null}}]},"status":"initialized","transaction_id":4972277,"var1":null,"var2":null,"var3":null}';

        /** @var MultisafepayOfficial $mockMultisafepay */
        $mockMultisafepay = $this->createMock(MultisafepayOfficial::class);
        /** @var SdkService $mockSdk */
        $mockSdk = $this->createMock(SdkService::class);
        /** @var PaymentOptionService $mockPaymentOptionService */
        $mockPaymentOptionService = $this->createMock(PaymentOptionService::class);
        /** @var OrderService $mockOrderService */
        $mockOrderService = $this->createMock(OrderService::class);

        $paymentOptionMock = $this->getMockBuilder(BasePaymentOption::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getFrontEndName'])
            ->getMock();
        $paymentOptionMock->method('getFrontEndName')->willReturn('MultiSafepay');

        $mockPaymentOptionService->method('getMultiSafepayPaymentOption')->willReturn($paymentOptionMock);
        $this->paymentOptionService = $mockPaymentOptionService;

        $this->notificationService = new NotExistingOrderNotificationService($mockMultisafepay, $mockSdk, $mockPaymentOptionService, $mockOrderService);
    }

    /**
     * Test getTransactionFromPostNotification returns a TransactionResponse for valid JSON payloads.
     *
     * @throws PrestaShopException
     */
    public function testGetTransactionFromPostNotification(): void
    {
        $output = $this->notificationService->getTransactionFromPostNotification($this->rawPostNotification);
        self::assertInstanceOf(TransactionResponse::class, $output);
    }

    /**
     * Test getTransactionFromPostNotification throws TypeError for an empty notification body.
     *
     * @throws PrestaShopException
     */
    public function testFailToGetTransactionFromEmptyBodyPostNotification(): void
    {
        $this->expectException(TypeError::class);
        $this->notificationService->getTransactionFromPostNotification('');
    }

    /**
     * Test getOrderStatusId returns an integer state identifier for known transaction statuses.
     *
     * @return void
     */
    public function testGetOrderStatusId(): void
    {
        $orderStatusId = $this->notificationService->getOrderStatusId('completed');
        self::assertIsInt($orderStatusId);
    }

    /**
     * Test shouldStatusBeUpdated returns false when the order is already in partial status
     * and callback does not contain unprocessed capture events.
     *
     * @throws PrestaShopException
     */
    public function testShouldStatusBeUpdatedReturnsFalseForPartialCaptureWithoutNewEvents(): void
    {
        $partialCapturedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');

        $orderMock = $this->createMock(Order::class);
        $orderMock->id = 123;
        $orderMock->id_cart = 456;
        $orderMock->module = 'multisafepayofficial';
        $orderMock->current_state = $partialCapturedStatusId;

        $transactionMock = $this->createManualCapturePartialTransactionMock([]);

        $result = $this->notificationService->shouldStatusBeUpdated($orderMock, $transactionMock);

        self::assertFalse($result);
    }

    /**
     * Test shouldStatusBeUpdated returns true when the current order state differs
     * from the target partially captured state.
     *
     * @throws PrestaShopException
     */
    public function testShouldStatusBeUpdatedReturnsTrueForPartialCaptureWhenCurrentStateDiffers(): void
    {
        $partialCapturedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');

        $orderMock = $this->createMock(Order::class);
        $orderMock->id = 123;
        $orderMock->id_cart = 456;
        $orderMock->module = 'multisafepayofficial';
        $orderMock->current_state = $partialCapturedStatusId + 1;

        $transactionMock = $this->createManualCapturePartialTransactionMock([]);

        $result = $this->notificationService->shouldStatusBeUpdated($orderMock, $transactionMock);

        self::assertTrue($result);
    }

    /**
     * Test shouldStatusBeUpdated returns true when the callback contains capture events
     * that have not been processed into order history yet.
     *
     * @throws PrestaShopException
     */
    public function testShouldStatusBeUpdatedReturnsTrueForPartialCaptureWithUnprocessedEvents(): void
    {
        $originalPartialCapturedStatus = Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');
        $invalidPartialCapturedStatusId = $this->resolveNonExistingOrderStateId();
        Configuration::updateValue('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED', $invalidPartialCapturedStatusId);

        $orderMock = $this->createMock(Order::class);
        $orderMock->id = 123;
        $orderMock->id_cart = 456;
        $orderMock->module = 'multisafepayofficial';
        $orderMock->current_state = $invalidPartialCapturedStatusId;

        $transactionMock = $this->createManualCapturePartialTransactionMock(
            [
                [
                    'type' => 'capture',
                    'amount' => 500,
                ],
            ]
        );

        try {
            $result = $this->notificationService->shouldStatusBeUpdated($orderMock, $transactionMock);
        } finally {
            Configuration::updateValue('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED', $originalPartialCapturedStatus);
        }

        self::assertTrue($result);
    }

    /**
     * Test shouldStatusBeUpdated returns the same decision regardless of debug mode.
     *
     * @throws PrestaShopException
     */
    public function testShouldStatusBeUpdatedReturnsSameResultWithDebugModeEnabledOrDisabled(): void
    {
        $partialCapturedStatusId = (int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');

        $orderMock = $this->createMock(Order::class);
        $orderMock->id = 123;
        $orderMock->id_cart = 456;
        $orderMock->module = 'multisafepayofficial';
        $orderMock->current_state = $partialCapturedStatusId + 1;

        $transactionMock = $this->createManualCapturePartialTransactionMock([]);

        $originalDebugMode = Configuration::get('MULTISAFEPAY_OFFICIAL_DEBUG_MODE');

        try {
            Configuration::updateValue('MULTISAFEPAY_OFFICIAL_DEBUG_MODE', 0);
            $resultWithDebugDisabled = $this->notificationService->shouldStatusBeUpdated($orderMock, $transactionMock);

            Configuration::updateValue('MULTISAFEPAY_OFFICIAL_DEBUG_MODE', 1);
            $resultWithDebugEnabled = $this->notificationService->shouldStatusBeUpdated($orderMock, $transactionMock);
        } finally {
            Configuration::updateValue('MULTISAFEPAY_OFFICIAL_DEBUG_MODE', $originalDebugMode);
        }

        self::assertSame($resultWithDebugDisabled, $resultWithDebugEnabled);
    }

    /**
     * Test isValidOrderStateId returns false for non-positive and non-existing order state IDs.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testIsValidOrderStateIdReturnsFalseForInvalidValues(): void
    {
        $this->assertFalse($this->invokePrivateMethod('isValidOrderStateId', [0]));
        $this->assertFalse($this->invokePrivateMethod('isValidOrderStateId', [-1]));
        $this->assertFalse($this->invokePrivateMethod('isValidOrderStateId', [987654321]));
    }

    /**
     * Test normalizeManualCapturePaymentRows exits early when transaction ID is empty after trim.
     * @throws ReflectionException
     */
    public function testNormalizeManualCapturePaymentRowsSkipsWhenTransactionIdIsEmptyAfterTrim(): void
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock->id = 123;
        $orderMock->id_cart = 456;
        $orderMock->id_lang = 1;
        $orderMock->expects($this->never())->method('getOrderPaymentCollection');

        $paymentDetailsMock = $this->createMock(PaymentDetails::class);
        $paymentDetailsMock->method('getType')->willReturn('IDEAL');

        $transactionMock = $this->createMock(TransactionResponse::class);
        $transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);
        $transactionMock->method('getTransactionId')->willReturn('   ');

        $this->invokePrivateMethod('normalizeManualCapturePaymentRows', [$orderMock, $transactionMock]);
    }

    /**
     * Test partial capture flow does not throw when payment-row registration fails
     * and continues gracefully when partial captured state configuration is invalid.
     *
     * @throws PrestaShopException
     */
    public function testExistingOrderProcessManualCaptureNotificationContinuesWhenPartialPaymentRegistrationFails(): void
    {
        $originalPartialCapturedStatus = Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED');
        Configuration::updateValue('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED', 0);

        $orderMock = $this->createMock(Order::class);
        $orderMock->id = 123;
        $orderMock->id_cart = 456;
        $orderMock->id_currency = 1;

        $paymentDetailsMock = $this->createMock(PaymentDetails::class);
        $paymentDetailsMock->method('getCapture')->willReturn(CaptureRequest::CAPTURE_MANUAL_TYPE);
        $paymentDetailsMock->method('getType')->willReturn('IDEAL');

        $transactionMock = $this->createMock(TransactionResponse::class);
        $transactionMock->method('getStatus')->willReturn('completed');
        $transactionMock->method('getFinancialStatus')->willReturn('initialized');
        $transactionMock->method('getAmount')->willReturn(1912);
        $transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);
        $transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_remain' => '1412',
            ],
            'related_transactions' => [
                [
                    'type' => 'capture',
                    'amount' => 500,
                ],
            ],
        ]);

        $transactionIdCallCount = 0;
        $transactionMock->method('getTransactionId')->willReturnCallback(static function () use (&$transactionIdCallCount) {
            $transactionIdCallCount++;

            if ($transactionIdCallCount === 1) {
                return 'MSP-OK-123';
            }

            throw new Exception('Simulated payment-row registration failure');
        });

        try {
            $this->notificationService->existingOrderProcessManualCaptureNotification($orderMock, $transactionMock);
        } finally {
            Configuration::updateValue('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED', $originalPartialCapturedStatus);
        }

        $this->assertGreaterThanOrEqual(
            2,
            $transactionIdCallCount,
            'The simulated payment-row registration failure path must be exercised.'
        );
        $this->assertEquals(
            $originalPartialCapturedStatus,
            Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED'),
            'The original partial-captured status configuration must be restored after the test.'
        );
    }

    /**
     * Test sync gating in the completed manual-capture flow prevents rewriting existing partial rows.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testShouldSyncSingleStepManualCapturePaymentGating(): void
    {
        $this->assertFalse(
            $this->invokePrivateMethod('shouldSyncSingleStepManualCapturePayment', [2, false]),
            'Existing partial payments must not be fully synced/rewritten.'
        );

        $this->assertFalse(
            $this->invokePrivateMethod('shouldSyncSingleStepManualCapturePayment', [0, true]),
            'When state change already created a payment row, full sync must be skipped.'
        );

        $this->assertTrue(
            $this->invokePrivateMethod('shouldSyncSingleStepManualCapturePayment', [0, false]),
            'Single-step flow without existing rows should still sync once.'
        );
    }

    /**
     * Invoke a private NotificationService method using reflection.
     *
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     * @throws ReflectionException
     */
    private function invokePrivateMethod(string $methodName, array $arguments = [])
    {
        $reflection = new ReflectionClass(get_class($this->notificationService));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->notificationService, $arguments);
    }

    /**
     * Resolve an order-state ID that does not exist in the current test database.
     *
     * @return int
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function resolveNonExistingOrderStateId(): int
    {
        $candidateId = max((int)Configuration::get('MULTISAFEPAY_OFFICIAL_OS_PARTIAL_CAPTURED'), 1000000);

        while (Validate::isLoadedObject(new OrderState($candidateId))) {
            $candidateId++;
        }

        return $candidateId;
    }

    /**
     * Create a manual-capture transaction mock configured for partial-capture scenarios.
     *
     * @param array   $relatedTransactions Related callback transactions payload.
     *
     * @return TransactionResponse
     */
    private function createManualCapturePartialTransactionMock(array $relatedTransactions): TransactionResponse
    {
        $paymentDetailsMock = $this->createMock(PaymentDetails::class);
        $paymentDetailsMock->method('getCapture')->willReturn(CaptureRequest::CAPTURE_MANUAL_TYPE);

        $transactionMock = $this->createMock(TransactionResponse::class);
        $transactionMock->method('getStatus')->willReturn('completed');
        $transactionMock->method('getFinancialStatus')->willReturn('initialized');
        $transactionMock->method('getAmount')->willReturn(1912);
        $transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);
        $transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_remain' => '1412',
            ],
            'related_transactions' => $relatedTransactions,
        ]);

        return $transactionMock;
    }
}
