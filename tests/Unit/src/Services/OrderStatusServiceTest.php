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

use PHPUnit\Framework\TestCase;
use MultiSafepay\PrestaShop\Services\OrderStatusService;

class OrderStatusServiceTest extends TestCase
{

    /**
     * @covers \MultiSafepay\PrestaShop\Services\OrderStatusService::getMultiSafepayOrderStatuses
     */
    public function testGetMultiSafepayOrderStatuses()
    {
        $output = (new OrderStatusService())->getMultiSafepayOrderStatuses();
        $this->assertIsArray($output);
        $this->assertArrayHasKey('initialized', $output);
        $this->assertArrayHasKey('uncleared', $output);
        $this->assertArrayHasKey('partial_refunded', $output);
        $this->assertArrayHasKey('chargeback', $output);
        $this->assertArrayHasKey('awaiting_bank_transfer_payment', $output);
        foreach ($output as $value) {
            $this->assertArrayHasKey('name', $value);
            $this->assertIsString($value['name']);
            $this->assertArrayHasKey('send_mail', $value);
            $this->assertIsBool($value['send_mail']);
            $this->assertArrayHasKey('color', $value);
            $this->assertIsString($value['color']);
            $this->assertArrayHasKey('invoice', $value);
            $this->assertIsBool($value['invoice']);
            $this->assertArrayHasKey('template', $value);
            $this->assertIsString($value['template']);
            $this->assertArrayHasKey('paid', $value);
            $this->assertIsBool($value['paid']);
            $this->assertArrayHasKey('logable', $value);
            $this->assertIsBool($value['logable']);
        }
    }
}
