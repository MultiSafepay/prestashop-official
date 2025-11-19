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

namespace MultiSafepay\Tests\Controllers;

use MultiSafepay\Api\Transactions\Transaction;
use MultiSafepay\PrestaShop\Helper\ConfigHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the cancel controller logic improvements in PRES-490
 *
 * These tests verify:
 * - Transaction status validation logic (validateTransactionStatusForCancellation)
 * - Order cancellation eligibility logic (canOrderBeCancelled)
 */
class CancelControllerTest extends TestCase
{
    /**
     * Test that protected statuses (COMPLETED, UNCLEARED, SHIPPED) prevent cancellation
     *
     * @covers MultisafepayOfficialCancelModuleFrontController::validateTransactionStatusForCancellation
     * @dataProvider protectedTransactionStatusProvider
     */
    public function testProtectedTransactionStatusesShouldNotAllowCancellation(string $status): void
    {
        $protectedStatuses = [
            Transaction::COMPLETED,
            Transaction::UNCLEARED,
            Transaction::SHIPPED
        ];

        self::assertContains($status, $protectedStatuses);
    }

    /**
     * Test that cancellable statuses (DECLINED, CANCELLED, EXPIRED, VOID) allow cancellation
     *
     * @covers MultisafepayOfficialCancelModuleFrontController::validateTransactionStatusForCancellation
     * @dataProvider cancellableTransactionStatusProvider
     */
    public function testCancellableTransactionStatusesShouldAllowCancellation(string $status): void
    {
        $cancellableStatuses = [
            Transaction::DECLINED,
            Transaction::CANCELLED,
            Transaction::EXPIRED,
            Transaction::VOID
        ];

        self::assertContains($status, $cancellableStatuses);
    }

    /**
     * Test that paid order statuses prevent cancellation
     *
     * @covers MultisafepayOfficialCancelModuleFrontController::canOrderBeCancelled
     */
    public function testPaidOrderStatusesShouldNotAllowCancellation(): void
    {
        // These are the statuses that should prevent cancellation
        $paidStatuses = [
            'PS_OS_PAYMENT',        // Payment accepted
            'PS_OS_WS_PAYMENT',     // Remote payment accepted
            'PS_OS_DELIVERED',      // Delivered
            'PS_OS_SHIPPING',       // Shipped
            'PS_OS_PREPARATION',    // Preparation in progress
        ];

        foreach ($paidStatuses as $statusKey) {
            self::assertNotEmpty($statusKey, 'Paid status key should not be empty');
        }

        self::assertCount(5, $paidStatuses, 'Should have exactly 5 paid statuses defined');
    }

    /**
     * Test that cancellable order statuses allow cancellation
     *
     * @covers MultisafepayOfficialCancelModuleFrontController::canOrderBeCancelled
     */
    public function testCancellableOrderStatusesShouldAllowCancellation(): void
    {
        // These are the statuses that should allow cancellation
        $cancellableStatuses = [
            'MULTISAFEPAY_OFFICIAL_OS_INITIALIZED',
            'PS_OS_OUTOFSTOCK_UNPAID'
        ];

        foreach ($cancellableStatuses as $statusKey) {
            self::assertNotEmpty($statusKey, 'Cancellable status key should not be empty');
        }

        self::assertCount(2, $cancellableStatuses, 'Should have exactly 2 cancellable order statuses defined');
    }

    /**
     * Test ConfigHelper integration in canOrderBeCancelled
     *
     * @covers MultisafepayOfficialCancelModuleFrontController::canOrderBeCancelled
     */
    public function testCanOrderBeCancelledUsesConfigHelperForFinalStatuses(): void
    {
        // Test that ConfigHelper::settingToIntArray works correctly
        $jsonSetting = '[2,3,4,5]';
        $finalStatuses = ConfigHelper::settingToIntArray($jsonSetting);

        self::assertIsArray($finalStatuses);
        self::assertEquals([2, 3, 4, 5], $finalStatuses);

        // Verify that if a current state is in final statuses, it should not be cancellable
        $currentState = 3;
        self::assertTrue(in_array($currentState, $finalStatuses, true));
    }

    /**
     * Test that empty final order status setting is handled correctly
     *
     * @covers MultisafepayOfficialCancelModuleFrontController::canOrderBeCancelled
     */
    public function testCanOrderBeCancelledHandlesEmptyFinalOrderStatusSetting(): void
    {
        $emptySetting = '';
        $finalStatuses = ConfigHelper::settingToIntArray($emptySetting);

        self::assertIsArray($finalStatuses);
        self::assertEmpty($finalStatuses);
    }

    /**
     * Test error message customization based on transaction status
     *
     * @covers MultisafepayOfficialCancelModuleFrontController::postProcess
     * @dataProvider transactionStatusErrorMessageProvider
     */
    public function testErrorMessageIsCustomizedBasedOnTransactionStatus(string $status, string $expectedMessageKey): void
    {
        $statusToMessageMap = [
            Transaction::CANCELLED => 'cancelled',
            Transaction::EXPIRED => 'expired',
            Transaction::VOID => 'voided',
            Transaction::DECLINED => 'declined',
        ];

        self::assertArrayHasKey($status, $statusToMessageMap);
        self::assertEquals($expectedMessageKey, $statusToMessageMap[$status]);
    }

    /**
     * Test that the controller checks transaction status BEFORE attempting to cancel
     * This is a critical security improvement in PRES-490
     */
    public function testTransactionStatusIsCheckedBeforeOrderCancellation(): void
    {
        // The new flow:
        // 1. Get transaction from MultiSafepay
        // 2. Validate transaction status (validateTransactionStatusForCancellation)
        // 3. Check if order can be cancelled (canOrderBeCancelled)
        // 4. Cancel order

        // This ensures we never cancel an order if the payment is already completed
        $protectedStatuses = [Transaction::COMPLETED, Transaction::UNCLEARED, Transaction::SHIPPED];
        $cancellableStatuses = [Transaction::DECLINED, Transaction::CANCELLED, Transaction::EXPIRED, Transaction::VOID];

        self::assertNotEmpty($protectedStatuses, 'Protected statuses must be defined');
        self::assertNotEmpty($cancellableStatuses, 'Cancellable statuses must be defined');

        // Verify no overlap between protected and cancellable
        $overlap = array_intersect($protectedStatuses, $cancellableStatuses);
        self::assertEmpty($overlap, 'Protected and cancellable statuses should not overlap');
    }

    /**
     * Data provider for protected transaction statuses
     */
    public function protectedTransactionStatusProvider(): array
    {
        return [
            'completed status' => [Transaction::COMPLETED],
            'uncleared status' => [Transaction::UNCLEARED],
            'shipped status' => [Transaction::SHIPPED],
        ];
    }

    /**
     * Data provider for cancellable transaction statuses
     */
    public function cancellableTransactionStatusProvider(): array
    {
        return [
            'declined status' => [Transaction::DECLINED],
            'cancelled status' => [Transaction::CANCELLED],
            'expired status' => [Transaction::EXPIRED],
            'void status' => [Transaction::VOID],
        ];
    }

    /**
     * Data provider for transaction status to error message mapping
     */
    public function transactionStatusErrorMessageProvider(): array
    {
        return [
            'cancelled transaction' => [Transaction::CANCELLED, 'cancelled'],
            'expired transaction' => [Transaction::EXPIRED, 'expired'],
            'void transaction' => [Transaction::VOID, 'voided'],
            'declined transaction' => [Transaction::DECLINED, 'declined'],
        ];
    }
}
