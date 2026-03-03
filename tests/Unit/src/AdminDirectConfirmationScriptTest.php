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

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit checks for direct payment confirmation popup implementation.
 */
class AdminDirectConfirmationScriptTest extends TestCase
{
    /**
     * @var string
     */
    private $modulePath;

    /**
     * Prepare module path for fixture-like file assertions.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->modulePath = dirname(__DIR__, 3);
    }

    /**
     * Verify template contains the localized confirmation text container.
     */
    public function testPaymentMethodsTemplateContainsDirectConfirmationTextContainer(): void
    {
        $templatePath = $this->modulePath . '/views/templates/admin/settings/payment-methods.tpl';
        $content = file_get_contents($templatePath);

        self::assertIsString($content);
        self::assertStringContainsString('id="multisafepay-direct-payment-confirmation-text"', $content);
        self::assertStringContainsString('data-title="{l s=\'Direct payment activation confirmation\' mod=\'multisafepayofficial\'}"', $content);
        self::assertStringContainsString('%payment_method% Direct', $content);
    }

    /**
     * Verify safeguard is scoped to Google Pay Direct and Apple Pay Direct only.
     */
    public function testAdminScriptTargetsOnlyGooglePayAndApplePayDirectToggles(): void
    {
        $adminScriptPath = $this->modulePath . '/views/js/admin.js';
        $content = file_get_contents($adminScriptPath);
        $matches = [];
        $toggleGatewaysDefinitionPattern = "/const\\s+toggleGateways\\s*=\\s*\\[([^\\]]*)\\];/";

        self::assertIsString($content);
        self::assertSame(1, preg_match($toggleGatewaysDefinitionPattern, $content, $matches));
        self::assertArrayHasKey(1, $matches);
        self::assertStringContainsString("'GOOGLEPAY'", $matches[1]);
        self::assertStringContainsString("'APPLEPAY'", $matches[1]);
        self::assertStringNotContainsString("'BANKTRANS'", $matches[1]);
    }

    /**
     * Verify script contains confirmation prompt and rollback flow on cancellation.
     */
    public function testAdminScriptContainsConfirmationAndRollbackFlow(): void
    {
        $adminScriptPath = $this->modulePath . '/views/js/admin.js';
        $content = file_get_contents($adminScriptPath);

        self::assertIsString($content);
        self::assertStringContainsString("window.confirm(confirmationText.title + '\\n\\n' + confirmationMessage)", $content);
        self::assertStringContainsString("if (!confirmation)", $content);
        self::assertStringContainsString("rollbackField.checked = true;", $content);
        self::assertStringContainsString("trigger('click').trigger('change');", $content);
    }
}
