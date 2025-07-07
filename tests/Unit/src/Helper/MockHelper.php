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

use MultisafepayOfficial;
use MultiSafepay\PrestaShop\Services\SdkService;
use MultiSafepay\Sdk;
use PHPUnit\Framework\MockObject\MockObject;

trait MockHelper
{
    /**
     * Creates a mock of MultisafepayOfficial with common setup
     */
    protected function createMockMultisafepayOfficial(): MockObject
    {
        $mock = $this->createMock(MultisafepayOfficial::class);
        $mock->method('l')->willReturn('');
        return $mock;
    }

    /**
     * Creates a mock of SdkService with SDK chain properly configured
     */
    protected function createMockSdkService(): MockObject
    {
        $mock = $this->createMock(SdkService::class);
        $mockSdk = $this->createMock(Sdk::class);
        $mock->method('getSdk')->willReturn($mockSdk);
        return $mock;
    }

    /**
     * Creates a complete mock SDK with common managers
     */
    protected function createMockSdkWithManagers(): MockObject
    {
        $mockSdk = $this->createMock(Sdk::class);
        
        // Add common managers
        $mockApiTokenManager = $this->createMock(\MultiSafepay\Api\ApiTokenManager::class);
        $mockSdk->method('getApiTokenManager')->willReturn($mockApiTokenManager);
        
        $mockTokenManager = $this->createMock(\MultiSafepay\Api\TokenManager::class);
        $mockSdk->method('getTokenManager')->willReturn($mockTokenManager);
        
        return $mockSdk;
    }

    /**
     * Creates a mock SdkService with fully configured SDK and managers
     */
    protected function createMockSdkServiceWithManagers(): MockObject
    {
        $mock = $this->createMock(SdkService::class);
        $mockSdk = $this->createMockSdkWithManagers();
        $mock->method('getSdk')->willReturn($mockSdk);
        return $mock;
    }

    /**
     * Creates a generic mock factory object
     */
    protected function createMockFactory(): MockObject
    {
        return $this->createMock('stdClass');
    }
}
