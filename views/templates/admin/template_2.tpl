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
		<img src="{$module_dir|escape:'html':'UTF-8'}views/img/template_2_logo.png" class="col-xs-6 col-md-3 text-center" id="payment-logo" />
		<div class="col-xs-6 col-md-6 text-center text-muted">
			{l s='My Payment Module and PrestaShop have partnered to provide the easiest way for you to accurately calculate and file sales tax.' mod='multisafepay'}
		</div>
		<div class="col-xs-12 col-md-3 text-center">
			<a href="#" onclick="javascript:return false;" class="btn btn-primary" id="create-account-btn">{l s='Create an account' mod='multisafepay'}</a><br />
			{l s='Already have one?' mod='multisafepay'}<a href="#" onclick="javascript:return false;"> {l s='Log in' mod='multisafepay'}</a>
		</div>
	</div>

	<hr />

	<div class="multisafepay-content">
		<div class="row">
			<div class="col-md-5">
				<h5>{l s='Benefits of using my payment module' mod='multisafepay'}</h5>
				<ul class="ul-spaced">
					<li>
						<strong>{l s='It is fast and easy' mod='multisafepay'}:</strong>
						{l s='It is pre-integrated with PrestaShop, so you can configure it with a few clicks.' mod='multisafepay'}
					</li>

					<li>
						<strong>{l s='It is global' mod='multisafepay'}:</strong>
						{l s='Accept payments in XX currencies from XXX markets around the world.' mod='multisafepay'}
					</li>

					<li>
						<strong>{l s='It is trusted' mod='multisafepay'}:</strong>
						{l s='Industry-leading fraud an buyer protections keep you and your customers safe.' mod='multisafepay'}
					</li>

					<li>
						<strong>{l s='It is cost-effective' mod='multisafepay'}:</strong>
						{l s='There are no setup fees or long-term contracts. You only pay a low transaction fee.' mod='multisafepay'}
					</li>
				</ul>
			</div>

			<div class="col-md-2">
				<h5>{l s='Pricing' mod='multisafepay'}</h5>
				<dl class="list-unstyled">
					<dt>{l s='Payment Standard' mod='multisafepay'}</dt>
					<dd>{l s='No monthly fee' mod='multisafepay'}</dd>
					<dt>{l s='Payment Express' mod='multisafepay'}</dt>
					<dd>{l s='No monthly fee' mod='multisafepay'}</dd>
					<dt>{l s='Payment Pro' mod='multisafepay'}</dt>
					<dd>{l s='$5 per month' mod='multisafepay'}</dd>
				</dl>
				<a href="#" onclick="javascript:return false;">(Detailed pricing here)</a>
			</div>

			<div class="col-md-5">
				<h5>{l s='How does it work?' mod='multisafepay'}</h5>
				<iframe src="//player.vimeo.com/video/75405291" width="335" height="188" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
			</div>
		</div>

		<hr />

		<div class="row">
			<div class="col-md-12">
				<p class="text-muted">{l s='My Payment Module accepts more than 80 localized payment methods around the world' mod='multisafepay'}</p>

				<div class="row">
					<img src="{$module_dir|escape:'html':'UTF-8'}views/img/template_2_cards.png" class="col-md-3" id="payment-logo" />
					<div class="col-md-9 text-center">
						<h6>{l s='For more information, call 888-888-1234' mod='multisafepay'} {l s='or' mod='multisafepay'} <a href="mailto:contact@prestashop.com">contact@prestashop.com</a></h6>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="panel">
	<p class="text-muted">
		<i class="icon icon-info-circle"></i> {l s='In order to create a secure account with My Payment Module, please complete the fields in the settings panel below:' mod='multisafepay'}
		{l s='By clicking the "Save" button you are creating secure connection details to your store.' mod='multisafepay'}
		{l s='My Payment Module signup only begins when you client on "Activate your account" in the registration panel below.' mod='multisafepay'}
		{l s='If you already have an account you can create a new shop within your account.' mod='multisafepay'}
	</p>
	<p>
		<a href="#" onclick="javascript:return false;"><i class="icon icon-file"></i> Link to the documentation</a>
	</p>
</div>
