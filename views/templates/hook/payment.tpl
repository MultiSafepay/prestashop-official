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

<div class="row">
	<div class="col-xs-12 col-md-6">
		<p class="payment_module" id="multisafepay_payment_button">
			{if $cart->getOrderTotal() < 2}
				<a href="">
					<img src="{$domain|cat:$payment_button|escape:'html':'UTF-8'}" alt="{l s='Pay with my payment module' mod='multisafepay'}" />
					{l s='Minimum amount required in order to pay with my payment module:' mod='multisafepay'} {convertPrice price=2}
				</a>
			{else}
				<a href="{$link->getModuleLink('multisafepay', 'redirect', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Pay with my payment module' mod='multisafepay'}">
					<img src="{$module_dir|escape:'htmlall':'UTF-8'}/logo.png" alt="{l s='Pay with my payment module' mod='multisafepay'}" width="32" height="32" />
					{l s='Pay with my payment module' mod='multisafepay'}
				</a>
			{/if}
		</p>
	</div>
</div>
