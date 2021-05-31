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

<div class="panel">
	<div class="row multisafepay-header">
		<img src="{$module_dir|escape:'html':'UTF-8'}views/img/template_1_logo.png" class="col-xs-6 col-md-4 text-center" id="payment-logo" />
		<div class="col-xs-6 col-md-4 text-center">
			<h4>{l s='Online payment processing' mod='multisafepay'}</h4>
			<h4>{l s='Fast - Secure - Reliable' mod='multisafepay'}</h4>
		</div>
		<div class="col-xs-12 col-md-4 text-center">
			<a href="#" onclick="javascript:return false;" class="btn btn-primary" id="create-account-btn">{l s='Create an account now!' mod='multisafepay'}</a><br />
			{l s='Already have an account?' mod='multisafepay'}<a href="#" onclick="javascript:return false;"> {l s='Log in' mod='multisafepay'}</a>
		</div>
	</div>

	<hr />

	<div class="multisafepay-content">
		<div class="row">
			<div class="col-md-6">
				<h5>{l s='My payment module offers the following benefits' mod='multisafepay'}</h5>
				<dl>
					<dt>&middot; {l s='Increase customer payment options' mod='multisafepay'}</dt>
					<dd>{l s='Visa®, Mastercard®, Diners Club®, American Express®, Discover®, Network and CJB®, plus debit, gift cards and more.' mod='multisafepay'}</dd>

					<dt>&middot; {l s='Help to improve cash flow' mod='multisafepay'}</dt>
					<dd>{l s='Receive funds quickly from the bank of your choice.' mod='multisafepay'}</dd>

					<dt>&middot; {l s='Enhanced security' mod='multisafepay'}</dt>
					<dd>{l s='Multiple firewalls, encryption protocols and fraud protection.' mod='multisafepay'}</dd>

					<dt>&middot; {l s='One-source solution' mod='multisafepay'}</dt>
					<dd>{l s='Conveniance of one invoice, one set of reports and one 24/7 customer service contact.' mod='multisafepay'}</dd>
				</dl>
			</div>

			<div class="col-md-6">
				<h5>{l s='FREE My Payment Module Glocal Gateway (Value of 400$)' mod='multisafepay'}</h5>
				<ul>
					<li>{l s='Simple, secure and reliable solution to process online payments' mod='multisafepay'}</li>
					<li>{l s='Virtual terminal' mod='multisafepay'}</li>
					<li>{l s='Reccuring billing' mod='multisafepay'}</li>
					<li>{l s='24/7/365 customer support' mod='multisafepay'}</li>
					<li>{l s='Ability to perform full or patial refunds' mod='multisafepay'}</li>
				</ul>
				<br />
				<em class="text-muted small">
					* {l s='New merchant account required and subject to credit card approval.' mod='multisafepay'}
					{l s='The free My Payment Module Global Gateway will be accessed through log in information provided via email within 48 hours.' mod='multisafepay'}
					{l s='Monthly fees for My Payment Module Global Gateway will apply.' mod='multisafepay'}
				</em>
			</div>
		</div>

		<hr />

		<div class="row">
			<div class="col-md-12">
				<h4>{l s='Accept payments in the United States using all major credit cards' mod='multisafepay'}</h4>

				<div class="row">
					<img src="{$module_dir|escape:'html':'UTF-8'}views/img/template_1_cards.png" class="col-md-6" id="payment-logo" />
					<div class="col-md-6">
						<h6 class="text-branded">{l s='For transactions in US Dollars (USD) only' mod='multisafepay'}</h6>
						<p class="text-branded">{l s='Call 888-888-1234 if you have any questions or need more information!' mod='multisafepay'}</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
