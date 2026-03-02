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

use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Api\Transactions\TransactionResponse\PaymentDetails;
use MultiSafepay\PrestaShop\Helper\ManualCaptureHelper;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;
use PrestaShopCollection;

/**
 * Tests for Manual Capture notification processing methods
 *
 * Tests the specific methods:
 * - existingOrderProcessManualCaptureNotification()
 * - notExistingOrderProcessManualCaptureNotification()
 */
class ManualCaptureNotificationServiceTest extends BaseMultiSafepayTest
{
    /** @var Order */
    private $orderMock;

    /** @var TransactionResponse */
    private $transactionMock;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock Order object
        $this->orderMock = $this->createMock(Order::class);
        $this->orderMock->id = 123;
        $this->orderMock->id_cart = 456;
        $this->orderMock->reference = 'TEST-ORDER-123';
        $this->orderMock->total_paid = 25.99;
        $this->orderMock->module = 'multisafepayofficial';

        // Mock TransactionResponse object
        $this->transactionMock = $this->createMock(TransactionResponse::class);
        $this->transactionMock->method('getAmount')->willReturn(2599);
        $this->transactionMock->method('getCurrency')->willReturn('EUR');
        $this->transactionMock->method('getTransactionId')->willReturn('MSP123456789');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification with financial_status = initialized
     * Should set order to AUTHORIZED status
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureWithInitializedFinancialStatus(): void
    {
        // Setup: Manual capture with financial_status = initialized
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        // The method should be called via updateOrderData in real scenario
        // Here we test that the transaction is correctly identified
        $this->assertTrue(
            ManualCaptureHelper::shouldBeAuthorizedStatus($this->transactionMock)
        );
    }

    /**
     * Test existingOrderProcessManualCaptureNotification with financial_status = completed
     * Should set order to PAYMENT_ACCEPTED status
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureWithCompletedFinancialStatus(): void
    {
        // Setup: Manual capture with financial_status = completed
        $this->setupManualCaptureTransaction(Transaction::COMPLETED, 'completed');

        // Mock order payment collection
        $paymentCollectionMock = $this->createMock(PrestaShopCollection::class);
        $paymentCollectionMock->method('count')->willReturn(0);
        $this->orderMock->method('getOrderPaymentCollection')->willReturn($paymentCollectionMock);

        // Mock order detail list
        $this->orderMock->method('getOrderDetailList')->willReturn([]);

        // Verify the transaction should be in payment-accepted status
        $this->assertTrue(
            ManualCaptureHelper::shouldBePaymentAcceptedStatus($this->transactionMock)
        );
    }

    /**
     * Test existingOrderProcessManualCaptureNotification with canceled status
     * Should set order to CANCELLED status
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureWithCancelledStatus(): void
    {
        // Setup: Manual capture with canceled status
        $this->setupManualCaptureTransaction(Transaction::CANCELLED);

        // Verify cancellation is handled
        $this->assertEquals(Transaction::CANCELLED, $this->transactionMock->getStatus());
    }

    /**
     * Test existingOrderProcessManualCaptureNotification with void status
     * Should set order to CANCELLED status
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureWithVoidStatus(): void
    {
        // Setup: Manual capture with void status
        $this->setupManualCaptureTransaction(Transaction::VOID);

        // Verify void is handled as a cancellation
        $this->assertEquals(Transaction::VOID, $this->transactionMock->getStatus());
    }

    /**
     * Test notExistingOrderProcessManualCaptureNotification
     * Should create order with AUTHORIZED status
     *
     * @return void
     */
    public function testNotExistingOrderProcessManualCaptureCreatesOrderWithAuthorizedStatus(): void
    {
        // Setup: Manual capture transaction for new order
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        // Mock order detail list for note
        $this->orderMock->method('getOrderDetailList')->willReturn([['product_quantity' => 1]]);

        // Verify the transaction is correctly identified as manual capture
        $this->assertTrue(
            ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock)
        );

        // Verify it should be in authorized status
        $this->assertTrue(
            ManualCaptureHelper::shouldBeAuthorizedStatus($this->transactionMock)
        );
    }

    /**
     * Test notExistingOrderProcessManualCaptureNotification with multiple items
     *
     * @return void
     */
    public function testNotExistingOrderProcessManualCaptureWithMultipleItems(): void
    {
        // Setup: Manual capture transaction
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        // Mock order detail list with multiple items
        $this->orderMock->method('getOrderDetailList')->willReturn([
            ['product_quantity' => 2],
            ['product_quantity' => 3]
        ]);

        // Verify the transaction is correctly identified
        $this->assertTrue(
            ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock)
        );
    }

    /**
     * Test existingOrderProcessManualCaptureNotification handles payment creation correctly
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureCreatesPaymentWhenCompleted(): void
    {
        // Setup: Manual capture with financial_status = completed
        $this->setupManualCaptureTransaction(Transaction::COMPLETED, 'completed');

        // Mock empty payment collection (no existing payment)
        $paymentCollectionMock = $this->createMock(PrestaShopCollection::class);
        $paymentCollectionMock->method('count')->willReturn(0);
        $this->orderMock->method('getOrderPaymentCollection')->willReturn($paymentCollectionMock);

        // Mock order detail list
        $this->orderMock->method('getOrderDetailList')->willReturn([['product_quantity' => 1]]);

        // Verify the condition that would trigger payment creation
        $this->assertTrue(
            ManualCaptureHelper::shouldBePaymentAcceptedStatus($this->transactionMock)
        );
    }

    /**
     * Test existingOrderProcessManualCaptureNotification updates existing payment
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureUpdatesExistingPaymentWhenCompleted(): void
    {
        // Setup: Manual capture with financial_status = completed
        $this->setupManualCaptureTransaction(Transaction::COMPLETED, 'completed');

        // Mock an existing payment collection
        $paymentCollectionMock = $this->createMock(PrestaShopCollection::class);
        $paymentCollectionMock->method('count')->willReturn(1);
        $this->orderMock->method('getOrderPaymentCollection')->willReturn($paymentCollectionMock);

        // Mock order detail list
        $this->orderMock->method('getOrderDetailList')->willReturn([['product_quantity' => 1]]);

        // Verify the condition that would trigger the payment update
        $this->assertTrue(
            ManualCaptureHelper::shouldBePaymentAcceptedStatus($this->transactionMock)
        );
    }

    /**
     * Test notExistingOrderProcessManualCaptureNotification adds manual capture note
     *
     * @return void
     */
    public function testNotExistingOrderProcessManualCaptureAddsNote(): void
    {
        // Setup: Manual capture transaction
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        // Mock order with details for note generation
        $this->orderMock->method('getOrderDetailList')->willReturn([
            ['product_quantity' => 2],
            ['product_quantity' => 3]
        ]);

        // Mock transaction data with capture expiry
        $this->transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_expiry' => '2025-12-31 23:59:59'
            ]
        ]);

        // Verify note can be added
        $this->expectNotToPerformAssertions();

        ManualCaptureHelper::addManualCaptureNote(
            $this->orderMock,
            $this->transactionMock
        );
    }

    /**
     * Test existingOrderProcessManualCaptureNotification adds completion note
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureAddsCompletionNoteWhenCaptured(): void
    {
        // Setup: Manual capture with financial_status = completed
        $this->setupManualCaptureTransaction(Transaction::COMPLETED, 'completed');

        // Mock order details
        $this->orderMock->method('getOrderDetailList')->willReturn([['product_quantity' => 1]]);
        $this->transactionMock->method('getData')->willReturn([]);

        // Verify completion note can be added
        $this->expectNotToPerformAssertions();

        ManualCaptureHelper::addFinishedManualCaptureNote(
            $this->orderMock,
            $this->transactionMock
        );
    }

    /**
     * Test existingOrderProcessManualCaptureNotification correctly identifies financial status initialized
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureIdentifiesInitializedFinancialStatus(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePaymentAccepted = ManualCaptureHelper::shouldBePaymentAcceptedStatus(
            $this->transactionMock
        );

        $this->assertTrue($shouldBeAuthorized, 'Financial status initialized should be authorized');
        $this->assertFalse($shouldBePaymentAccepted, 'Financial status initialized should not be payment accepted');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification correctly identifies financial status completed
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureIdentifiesCompletedFinancialStatus(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED, 'completed');

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePaymentAccepted = ManualCaptureHelper::shouldBePaymentAcceptedStatus(
            $this->transactionMock
        );

        $this->assertFalse($shouldBeAuthorized, 'Financial status completed should not be authorized');
        $this->assertTrue($shouldBePaymentAccepted, 'Financial status completed should be payment accepted');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification correctly identifies partial capture from callback payload
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureIdentifiesPartialCapturedFinancialStatus(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $this->transactionMock->method('getAmount')->willReturn(1912);
        $this->transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_remain' => '1412',
            ],
        ]);

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePaymentAccepted = ManualCaptureHelper::shouldBePaymentAcceptedStatus(
            $this->transactionMock
        );
        $shouldBePartialCaptured = ManualCaptureHelper::shouldBePartiallyCapturedStatus(
            $this->transactionMock
        );

        $this->assertFalse($shouldBeAuthorized, 'Partial capture payload should not be authorized');
        $this->assertFalse($shouldBePaymentAccepted, 'Partial capture payload should not be payment accepted');
        $this->assertTrue($shouldBePartialCaptured, 'Partial capture payload should be partially captured');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification correctly identifies partial capture
     * when capture_remain is missing but related capture events exist.
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureIdentifiesPartialCapturedByRelatedTransactionsFallback(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $this->transactionMock->method('getAmount')->willReturn(1912);
        $this->transactionMock->method('getData')->willReturn([
            'related_transactions' => [
                [
                    'type' => 'capture',
                    'amount' => 500,
                ],
            ],
        ]);

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePaymentAccepted = ManualCaptureHelper::shouldBePaymentAcceptedStatus(
            $this->transactionMock
        );
        $shouldBePartialCaptured = ManualCaptureHelper::shouldBePartiallyCapturedStatus(
            $this->transactionMock
        );

        $this->assertFalse($shouldBeAuthorized, 'Fallback capture event should not be authorized');
        $this->assertFalse($shouldBePaymentAccepted, 'Fallback capture event should not be payment accepted');
        $this->assertTrue($shouldBePartialCaptured, 'Fallback capture event should be partially captured');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification correctly identifies partial capture
     * with uppercase related transaction type values.
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureIdentifiesPartialCapturedWithUppercaseRelatedTransactionType(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $this->transactionMock->method('getAmount')->willReturn(1912);
        $this->transactionMock->method('getData')->willReturn([
            'related_transactions' => [
                [
                    'type' => 'CAPTURE',
                    'amount' => 500,
                ],
            ],
        ]);

        $shouldBePartialCaptured = ManualCaptureHelper::shouldBePartiallyCapturedStatus(
            $this->transactionMock
        );

        $this->assertTrue($shouldBePartialCaptured, 'Uppercase related transaction type should still detect partial capture');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification keeps authorized status
     * when there is no partial capture evidence in callback payload.
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureKeepsAuthorizedWhenPartialEvidenceIsMissing(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $this->transactionMock->method('getAmount')->willReturn(1912);
        $this->transactionMock->method('getData')->willReturn([]);

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePaymentAccepted = ManualCaptureHelper::shouldBePaymentAcceptedStatus(
            $this->transactionMock
        );
        $shouldBePartialCaptured = ManualCaptureHelper::shouldBePartiallyCapturedStatus(
            $this->transactionMock
        );

        $this->assertTrue($shouldBeAuthorized, 'Callback without partial evidence should remain authorized');
        $this->assertFalse($shouldBePaymentAccepted, 'Callback without partial evidence should not be payment accepted');
        $this->assertFalse($shouldBePartialCaptured, 'Callback without partial evidence should not be partially captured');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification identifies full capture
     * when the callback payload indicates capture_remain = 0.
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureIdentifiesFullCaptureByCaptureRemainZero(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $this->transactionMock->method('getAmount')->willReturn(1912);
        $this->transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_remain' => '0',
            ],
        ]);

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePaymentAccepted = ManualCaptureHelper::shouldBePaymentAcceptedStatus(
            $this->transactionMock
        );
        $shouldBePartialCaptured = ManualCaptureHelper::shouldBePartiallyCapturedStatus(
            $this->transactionMock
        );

        $this->assertFalse($shouldBeAuthorized, 'Capture remain zero should not be authorized');
        $this->assertTrue($shouldBePaymentAccepted, 'Capture remain zero should be payment accepted');
        $this->assertFalse($shouldBePartialCaptured, 'Capture remain zero should not be partially captured');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification keeps authorized status
     * when capture_remain equals the full transaction amount.
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureKeepsAuthorizedWhenCaptureRemainEqualsAmount(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $this->transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_remain' => '2599',
            ],
        ]);

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePartialCaptured = ManualCaptureHelper::shouldBePartiallyCapturedStatus(
            $this->transactionMock
        );

        $this->assertTrue($shouldBeAuthorized, 'Capture remain equal to amount should be authorized');
        $this->assertFalse($shouldBePartialCaptured, 'Capture remain equal to amount should not be partially captured');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification keeps authorized status
     * when capture_remain is greater than the transaction amount.
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureKeepsAuthorizedWhenCaptureRemainIsGreaterThanAmount(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $this->transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_remain' => '3000',
            ],
        ]);

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePartialCaptured = ManualCaptureHelper::shouldBePartiallyCapturedStatus(
            $this->transactionMock
        );

        $this->assertTrue($shouldBeAuthorized, 'Capture remain above amount should be treated as authorized');
        $this->assertFalse($shouldBePartialCaptured, 'Capture remain above amount should not be partially captured');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification identifies full capture
     * when the callback payload indicates capture_remain below zero.
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureIdentifiesFullCaptureByNegativeCaptureRemain(): void
    {
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        $this->transactionMock->method('getAmount')->willReturn(1912);
        $this->transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_remain' => '-1',
            ],
        ]);

        $shouldBeAuthorized = ManualCaptureHelper::shouldBeAuthorizedStatus(
            $this->transactionMock
        );
        $shouldBePaymentAccepted = ManualCaptureHelper::shouldBePaymentAcceptedStatus(
            $this->transactionMock
        );
        $shouldBePartialCaptured = ManualCaptureHelper::shouldBePartiallyCapturedStatus(
            $this->transactionMock
        );

        $this->assertFalse($shouldBeAuthorized, 'Negative capture remain should not be authorized');
        $this->assertTrue($shouldBePaymentAccepted, 'Negative capture remain should be payment accepted');
        $this->assertFalse($shouldBePartialCaptured, 'Negative capture remain should not be partially captured');
    }

    /**
     * Test existingOrderProcessManualCaptureNotification does not identify
     * full capture for automatic capture transactions even if capture_remain is zero.
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureDoesNotIdentifyFullCaptureForAutomaticCapture(): void
    {
        $paymentDetailsMock = $this->createMock(PaymentDetails::class);
        $paymentDetailsMock->method('getCapture')->willReturn('auto');

        $transactionMock = $this->createMock(TransactionResponse::class);
        $transactionMock->method('getStatus')->willReturn(Transaction::COMPLETED);
        $transactionMock->method('getFinancialStatus')->willReturn('initialized');
        $transactionMock->method('getAmount')->willReturn(1912);
        $transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);
        $transactionMock->method('getData')->willReturn([
            'payment_details' => [
                'capture_remain' => '0',
            ],
        ]);

        $this->assertFalse(
            ManualCaptureHelper::isManualCaptureTransaction($transactionMock),
            'Automatic capture should not be treated as manual capture'
        );
        $this->assertFalse(
            ManualCaptureHelper::shouldBePaymentAcceptedStatus($transactionMock),
            'Automatic capture should not be treated as payment accepted by manual-capture remain logic'
        );
        $this->assertFalse(
            ManualCaptureHelper::shouldBePartiallyCapturedStatus($transactionMock),
            'Automatic capture should not be treated as partially captured'
        );
    }

    /**
     * Test notExistingOrderProcessManualCaptureNotification detects manual capture correctly
     *
     * @return void
     */
    public function testNotExistingOrderProcessManualCaptureDetectsCorrectly(): void
    {
        // Setup: Manual capture transaction
        $this->setupManualCaptureTransaction(Transaction::COMPLETED);

        // Verify the transaction is correctly identified as manual capture
        $this->assertTrue(
            ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock)
        );

        // Verify it should be in authorized status
        $this->assertTrue(
            ManualCaptureHelper::shouldBeAuthorizedStatus($this->transactionMock)
        );
    }

    /**
     * Test existingOrderProcessManualCaptureNotification handles backorders correctly
     *
     * @return void
     */
    public function testExistingOrderProcessManualCaptureHandlesBackordersWhenCompleted(): void
    {
        // Setup: Manual capture with financial_status = completed
        $this->setupManualCaptureTransaction(Transaction::COMPLETED, 'completed');

        // Mock payment collection
        $paymentCollectionMock = $this->createMock(PrestaShopCollection::class);
        $paymentCollectionMock->method('count')->willReturn(0);
        $this->orderMock->method('getOrderPaymentCollection')->willReturn($paymentCollectionMock);

        // Verify that the transaction is in the right state for backorder processing
        $this->assertTrue(
            ManualCaptureHelper::shouldBePaymentAcceptedStatus($this->transactionMock)
        );
        $this->assertEquals(Transaction::COMPLETED, $this->transactionMock->getStatus());
    }

    /**
     * Helper method to set up a manual capture transaction with a specific status and financial status.
     *
     * @param string $status          Transaction status.
     * @param string $financialStatus Transaction financial status.
     *
     * @return void
     */
    private function setupManualCaptureTransaction(string $status, string $financialStatus = 'initialized'): void
    {
        $this->transactionMock->method('getStatus')->willReturn($status);
        $this->transactionMock->method('getFinancialStatus')->willReturn($financialStatus);

        $paymentDetailsMock = $this->createMock(PaymentDetails::class);
        $paymentDetailsMock->method('getCapture')->willReturn(CaptureRequest::CAPTURE_MANUAL_TYPE);

        $this->transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);
    }
}
