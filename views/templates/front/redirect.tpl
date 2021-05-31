{*
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
*}

<div>
	<h3>{l s='Redirect your customer' mod='multisafepay'}:</h3>
	<ul class="alert alert-info">
			<li>{l s='This action should be used to redirect your customer to the website of your payment processor' mod='multisafepay'}.</li>
	</ul>

	<div class="alert alert-warning">
		{l s='You can redirect your customer with an error message' mod='multisafepay'}:
		<a href="{$link->getModuleLink('multisafepay', 'redirect', ['action' => 'error'], true)|escape:'htmlall':'UTF-8'}" title="{l s='Look at the error' mod='multisafepay'}">
			<strong>{l s='Look at the error message' mod='multisafepay'}</strong>
		</a>
	</div>

	<div class="alert alert-success">
		{l s='You can also redirect your customer to the confirmation page' mod='multisafepay'}:
		<a href="{$link->getModuleLink('multisafepay', 'confirmation', ['cart_id' => $cart_id, 'secure_key' => $secure_key], true)|escape:'htmlall':'UTF-8'}" title="{l s='Confirm' mod='multisafepay'}">
			<strong>{l s='Go to the confirmation page' mod='multisafepay'}</strong>
		</a>
	</div>
</div>
