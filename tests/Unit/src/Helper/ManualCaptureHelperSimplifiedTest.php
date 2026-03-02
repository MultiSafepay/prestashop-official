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

namespace MultiSafepay\Tests\Helper;

use Exception;
use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Api\Transactions\TransactionResponse\PaymentDetails;
use MultiSafepay\PrestaShop\Helper\ManualCaptureHelper;
use MultiSafepay\Tests\BaseMultiSafepayTest;

/**
 * Simplified Manual Capture Helper Tests focusing on core functionality
 */
class ManualCaptureHelperSimplifiedTest extends BaseMultiSafepayTest
{
    private $transactionMock;
    private $paymentDetailsMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock TransactionResponse object
        $this->transactionMock = $this->createMock(TransactionResponse::class);

        // Mock PaymentDetails object
        $this->paymentDetailsMock = $this->createMock(PaymentDetails::class);
        $this->transactionMock->method('getPaymentDetails')->willReturn($this->paymentDetailsMock);
    }

    /**
     * Test isManualCaptureTransaction returns true for manual capture
     */
    public function testIsManualCaptureTransactionReturnsTrueForManualCapture(): void
    {
        $this->paymentDetailsMock->method('getCapture')->willReturn('manual');

        $result = ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock);

        $this->assertTrue($result);
    }

    /**
     * Test isManualCaptureTransaction returns false for automatic capture
     */
    public function testIsManualCaptureTransactionReturnsFalseForAutomaticCapture(): void
    {
        $this->paymentDetailsMock->method('getCapture')->willReturn('auto');

        $result = ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock);

        $this->assertFalse($result);
    }

    /**
     * Test isManualCaptureTransaction returns false for empty capture
     */
    public function testIsManualCaptureTransactionReturnsFalseForEmptyCapture(): void
    {
        $this->paymentDetailsMock->method('getCapture')->willReturn('');

        $result = ManualCaptureHelper::isManualCaptureTransaction($this->transactionMock);

        $this->assertFalse($result);
    }

    /**
     * Test isCancellableStatus returns true for completed status
     */
    public function testIsCancellableStatusReturnsTrueForCompletedStatus(): void
    {
        $this->transactionMock->method('getStatus')->willReturn(Transaction::COMPLETED);

        $result = ManualCaptureHelper::isCancellableStatus($this->transactionMock);

        $this->assertTrue($result);
    }

    /**
     * Test isCancellableStatus returns true for uncleared status
     */
    public function testIsCancellableStatusReturnsTrueForUnclearedStatus(): void
    {
        $this->transactionMock->method('getStatus')->willReturn(Transaction::UNCLEARED);

        $result = ManualCaptureHelper::isCancellableStatus($this->transactionMock);

        $this->assertTrue($result);
    }

    /**
     * Test isCancellableStatus returns false for shipped status
     */
    public function testIsCancellableStatusReturnsFalseForShippedStatus(): void
    {
        $this->transactionMock->method('getStatus')->willReturn(Transaction::SHIPPED);

        $result = ManualCaptureHelper::isCancellableStatus($this->transactionMock);

        $this->assertFalse($result);
    }

    /**
     * Test isCancellableStatus returns false for cancelled status
     */
    public function testIsCancellableStatusReturnsFalseForCancelledStatus(): void
    {
        $this->transactionMock->method('getStatus')->willReturn(Transaction::CANCELLED);

        $result = ManualCaptureHelper::isCancellableStatus($this->transactionMock);

        $this->assertFalse($result);
    }

    /**
     * Test isCancellableStatus returns false for declined status
     */
    public function testIsCancellableStatusReturnsFalseForDeclinedStatus(): void
    {
        $this->transactionMock->method('getStatus')->willReturn(Transaction::DECLINED);

        $result = ManualCaptureHelper::isCancellableStatus($this->transactionMock);

        $this->assertFalse($result);
    }

    /**
     * Test various transaction statuses against cancellable logic
     */
    public function testVariousTransactionStatusesForCancellation(): void
    {
        $testCases = [
            [Transaction::COMPLETED, true, 'Completed should be cancellable'],
            [Transaction::UNCLEARED, true, 'Uncleared should be cancellable'],
            [Transaction::SHIPPED, false, 'Shipped should not be cancellable'],
            [Transaction::CANCELLED, false, 'Cancelled should not be cancellable'],
            [Transaction::DECLINED, false, 'Declined should not be cancellable'],
            [Transaction::EXPIRED, false, 'Expired should not be cancellable'],
            [Transaction::VOID, false, 'Void should not be cancellable']
        ];

        foreach ($testCases as [$status, $expectedResult, $message]) {
            $transactionMock = $this->createMock(TransactionResponse::class);
            $transactionMock->method('getStatus')->willReturn($status);

            $result = ManualCaptureHelper::isCancellableStatus($transactionMock);

            $this->assertEquals($expectedResult, $result, $message);
        }
    }

    /**
     * Test manual capture detection with various capture types
     */
    public function testManualCaptureDetectionWithVariousCaptureTypes(): void
    {
        $testCases = [
            ['manual', true, 'Manual capture should be detected'],
            ['auto', false, 'Auto capture should not be detected as manual'],
            ['direct', false, 'Direct capture should not be detected as manual'],
            ['redirect', false, 'Redirect capture should not be detected as manual'],
            ['', false, 'Empty capture should not be detected as manual']
        ];

        foreach ($testCases as [$captureType, $expectedResult, $message]) {
            $paymentDetailsMock = $this->createMock(PaymentDetails::class);
            $paymentDetailsMock->method('getCapture')->willReturn($captureType);

            $transactionMock = $this->createMock(TransactionResponse::class);
            $transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);

            $result = ManualCaptureHelper::isManualCaptureTransaction($transactionMock);

            $this->assertEquals($expectedResult, $result, $message);
        }
    }

    /**
     * Test combination of manual capture detection and cancellable status
     */
    public function testManualCaptureAndCancellableStatusCombination(): void
    {
        $testCases = [
            ['manual', Transaction::COMPLETED, true, 'Manual capture + completed should be cancellable'],
            ['manual', Transaction::UNCLEARED, true, 'Manual capture + uncleared should be cancellable'],
            ['manual', Transaction::SHIPPED, false, 'Manual capture + shipped should not be cancellable'],
            ['auto', Transaction::COMPLETED, false, 'Auto capture + completed should not be manually cancellable'],
            ['auto', Transaction::SHIPPED, false, 'Auto capture + shipped should not be cancellable']
        ];

        foreach ($testCases as [$captureType, $status, $expectedCancellable, $message]) {
            $paymentDetailsMock = $this->createMock(PaymentDetails::class);
            $paymentDetailsMock->method('getCapture')->willReturn($captureType);

            $transactionMock = $this->createMock(TransactionResponse::class);
            $transactionMock->method('getPaymentDetails')->willReturn($paymentDetailsMock);
            $transactionMock->method('getStatus')->willReturn($status);

            $isManualCapture = ManualCaptureHelper::isManualCaptureTransaction($transactionMock);
            $isCancellable = ManualCaptureHelper::isCancellableStatus($transactionMock);

            // For manual capture cancellation, both conditions must be true
            $canCancelManualCapture = $isManualCapture && $isCancellable;

            $this->assertEquals($expectedCancellable, $canCancelManualCapture, $message);
        }
    }

    /**
     * Test ensureAuthorizedStatusExists method doesn't throw exceptions
     */
    public function testEnsureAuthorizedStatusExistsDoesNotThrowExceptions(): void
    {
        // This test ensures the method runs without throwing exceptions
        $this->expectNotToPerformAssertions();

        try {
            ManualCaptureHelper::ensureAuthorizedStatusExists();
        } catch (Exception $exception) {
            $this->fail('ensureAuthorizedStatusExists should not throw exceptions: ' . $exception->getMessage());
        }
    }
}
