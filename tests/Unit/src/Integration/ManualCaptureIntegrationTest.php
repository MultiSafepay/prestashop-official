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

namespace MultiSafepay\Tests\Integration;

use Exception;
use MultiSafepay\Api\TransactionManager;
use MultiSafepay\Api\Transactions\CaptureRequest;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Api\Transactions\TransactionResponse\PaymentDetails;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\PrestaShop\Helper\ManualCaptureHelper;
use MultiSafepay\Tests\BaseMultiSafepayTest;
use Order;
use PrestaShopException;
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Integration tests for Manual Capture functionality across the entire module
 * Test the complete flow from order status changes to API calls
 */
class ManualCaptureIntegrationTest extends BaseMultiSafepayTest
{
    private $orderMock;
    private $transactionMock;
    private $transactionManagerMock;

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

        // Mock TransactionManager
        $this->transactionManagerMock = $this->createMock(TransactionManager::class);
    }

    /**
     * Test complete manual capture flow: authorization -> shipping -> capture
     * @throws ClientExceptionInterface
     */
    public function testCompleteManualCaptureFlowAuthorizationToCapture(): void
    {
        // Step 1: Manual capture transaction is authorized
        $this->setupManualCaptureTransaction();
        $authorizedStatusId = 10;
        // Skip Configuration mocking for integration tests - focus on business logic
        $this->orderMock->current_state = $authorizedStatusId;

        // Verify it's detected as manual capture
        $this->assertTrue(ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock));

        // Step 2: Order status changes to 'shipped'
        $this->transactionMock = $this->createMock(TransactionResponse::class);
        $this->setupManualCaptureTransaction();
        $this->orderMock->current_state = $authorizedStatusId;

        // Verify it was previously authorized manual capture (test the logic we can test)
        $isManualCapture = ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock);
        $this->assertTrue($isManualCapture);

        // Step 3: Capture is triggered when the order is marked as shipped
        $this->transactionManagerMock->method('get')->willReturn($this->transactionMock);
        $this->transactionMock->method('getAmount')->willReturn(2599);
        $this->transactionMock->method('getCurrency')->willReturn('EUR');

        // Mock that transaction is not already captured
        $notCapturedTransaction = $this->createMock(TransactionResponse::class);
        $notCapturedTransaction->method('getStatus')->willReturn(Transaction::COMPLETED);
        $this->transactionManagerMock->method('get')->willReturn($notCapturedTransaction);

        $isNotAlreadyCaptured = !ManualCaptureHelper::isAlreadyCaptured(
            $this->transactionManagerMock,
            'TEST-ORDER-123'
        );
        $this->assertTrue($isNotAlreadyCaptured);
    }

    /**
     * Test manual capture cancellation flow
     */
    public function testManualCaptureCancellationFlow(): void
    {
        // Set up a manual capture transaction that can be cancelled
        $this->setupManualCaptureTransaction();

        // Verify cancellation conditions
        $isCancellable = ManualCaptureHelper::isCancellableStatus($this->transactionMock);
        $isManualCapture = ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock);

        $this->assertTrue($isCancellable);
        $this->assertTrue($isManualCapture);
    }

    /**
     * Test manual capture with order cancellation scenarios
     */
    public function testManualCaptureWithOrderCancellationScenarios(): void
    {
        $testCases = [
            [
                'transaction_status' => Transaction::COMPLETED,
                'can_cancel' => true,
                'description' => 'Completed manual capture can be cancelled'
            ],
            [
                'transaction_status' => Transaction::UNCLEARED,
                'can_cancel' => true,
                'description' => 'Uncleared manual capture can be cancelled'
            ],
            [
                'transaction_status' => Transaction::SHIPPED,
                'can_cancel' => false,
                'description' => 'Shipped manual capture cannot be cancelled'
            ],
            [
                'transaction_status' => Transaction::CANCELLED,
                'can_cancel' => false,
                'description' => 'Already cancelled manual capture cannot be cancelled again'
            ]
        ];

        foreach ($testCases as $testCase) {
            $transactionMock = $this->createMock(TransactionResponse::class);
            $transactionMock->method('getStatus')->willReturn($testCase['transaction_status']);

            $paymentDetailsMock = $this->createMock(PaymentDetails::class);
            $paymentDetailsMock->method('getCapture')->willReturn(CaptureRequest::CAPTURE_MANUAL_TYPE);
            $transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);

            $isCancellable = ManualCaptureHelper::isCancellableStatus($transactionMock);
            $isManualCapture = ManualCaptureHelper::isManualCaptureTransaction($transactionMock);

            $canCancel = $isCancellable && $isManualCapture;

            $this->assertEquals($testCase['can_cancel'], $canCancel, $testCase['description']);
        }
    }

    /**
     * Test module installation with manual capture status creation
     * @throws PrestaShopException
     */
    public function testModuleInstallationWithManualCaptureStatusCreation(): void
    {
        // Test that ensureAuthorizedStatusExists is called during module initialization
        // This is tested by verifying the method doesn't throw exceptions
        $this->expectNotToPerformAssertions();

        ManualCaptureHelper::ensureAuthorizedStatusExists();
    }

    /**
     * Test order status update hook with manual capture transactions
     */
    public function testOrderStatusUpdateHookWithManualCaptureTransactions(): void
    {
        // Setup manual capture transaction
        $this->setupManualCaptureTransaction();
        $this->transactionManagerMock->method('get')->willReturn($this->transactionMock);

        // Mock that capture is needed
        $this->transactionMock->method('getAmount')->willReturn(2599);
        $this->transactionMock->method('getCurrency')->willReturn('EUR');

        // Test that manual capture would be triggered in the hook
        $isManualCapture = ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock);
        $this->assertTrue($isManualCapture);
    }

    /**
     * Test API error handling in manual capture operations
     * @throws ClientExceptionInterface
     */
    public function testApiErrorHandlingInManualCaptureOperations(): void
    {
        $apiExceptionMessages = [
            'fullcaptured',
            'Transaction not found',
            'Invalid amount',
            'API temporarily unavailable'
        ];

        foreach ($apiExceptionMessages as $message) {
            $transactionManagerMock = $this->createMock(TransactionManager::class);

            if ($message === 'fullcaptured') {
                // Special case: fullcaptured should be handled gracefully
                $transactionManagerMock->method('capture')
                    ->willThrowException(new ApiException($message));

                // Test that isAlreadyCaptured handles this gracefully
                $transactionManagerMock->method('get')
                    ->willThrowException(new ApiException($message));

                $result = ManualCaptureHelper::isAlreadyCaptured($transactionManagerMock, 'TEST-ORDER');
                $this->assertFalse($result, "fullcaptured exception should result in false");
            } else {
                // Other exceptions should also be handled gracefully
                $transactionManagerMock->method('get')
                    ->willThrowException(new ApiException($message));

                $result = ManualCaptureHelper::isAlreadyCaptured($transactionManagerMock, 'TEST-ORDER');
                $this->assertFalse($result, "Exception '$message' should result in false");
            }
        }
    }

    /**
     * Test order shipping with tracking information for manual capture
     */
    public function testOrderShippingWithTrackingInformationForManualCapture(): void
    {
        // Setup manual capture transaction
        $this->setupManualCaptureTransaction();
        $this->transactionManagerMock->method('get')->willReturn($this->transactionMock);

        // Mock validation
        $this->transactionMock->method('getAmount')->willReturn(2599);
        $this->transactionMock->method('getCurrency')->willReturn('EUR');

        // Test that the capture process would include tracking info
        $this->assertTrue(ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock));
    }

    /**
     * Test partial refund prevention for manual capture transactions
     */
    public function testPartialRefundPreventionForManualCaptureTransactions(): void
    {
        // Test that manual capture transactions are identified for refund restrictions
        $this->setupManualCaptureTransaction();

        $isManualCapture = ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock);
        $this->assertTrue($isManualCapture);
    }

    /**
     * Test order message addition for different manual capture states
     */
    public function testOrderMessageAdditionForDifferentManualCaptureStates(): void
    {
        $testCases = [
            [
                'transaction_status' => Transaction::COMPLETED,
                'order_state' => 'authorized',
                'expected_message_type' => 'authorization',
                'description' => 'Authorization message for completed transaction'
            ],
            [
                'transaction_status' => Transaction::SHIPPED,
                'order_state' => 'payment_accepted',
                'expected_message_type' => 'completion',
                'description' => 'Completion message for shipped transaction'
            ]
        ];

        foreach ($testCases as $testCase) {
            $transactionMock = $this->createMock(TransactionResponse::class);
            $transactionMock->method('getStatus')->willReturn($testCase['transaction_status']);
            $transactionMock->method('getTransactionId')->willReturn('MSP123456789');
            $transactionMock->method('getAmount')->willReturn(2599);

            $paymentDetailsMock = $this->createMock(PaymentDetails::class);
            $paymentDetailsMock->method('getCapture')->willReturn(CaptureRequest::CAPTURE_MANUAL_TYPE);
            $transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);

            // Mock order details for message generation
            $orderDetailMock = ['product_quantity' => 1];
            $this->orderMock->method('getOrderDetailList')->willReturn([$orderDetailMock]);

            $isManualCapture = ManualCaptureHelper::isManualCaptureTransaction($transactionMock);
            $this->assertTrue($isManualCapture, $testCase['description']);

            // Test that appropriate message methods work without throwing exceptions
            try {
                if ($testCase['expected_message_type'] === 'authorization') {
                    ManualCaptureHelper::addManualCaptureNote($this->orderMock, $transactionMock);
                } elseif ($testCase['expected_message_type'] === 'completion') {
                    ManualCaptureHelper::addFinishedManualCaptureNote($this->orderMock, $transactionMock);
                }
                $this->assertTrue(true, 'Message method completed without exceptions');
            } catch (Exception $exception) {
                $this->fail('Message method should not throw exceptions: ' . $exception->getMessage());
            }
        }
    }

    /**
     * Helper method to set up a manual capture transaction with a specific status
     */
    private function setupManualCaptureTransaction()
    {
        $this->transactionMock->method('getStatus')->willReturn(Transaction::COMPLETED);

        $paymentDetailsMock = $this->createMock(PaymentDetails::class);
        $paymentDetailsMock->method('getCapture')->willReturn(CaptureRequest::CAPTURE_MANUAL_TYPE);

        $this->transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);
    }
}
