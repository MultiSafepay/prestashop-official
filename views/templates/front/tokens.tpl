{**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 *}

{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Saved payment details' mod='multisafepayofficial'}
{/block}

{block name='notifications'}
    {if isset($success)}
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="{l s='Close' mod='multisafepayofficial'}"><span aria-hidden="true">&times;</span></button>
            {$success|escape:'html':'UTF-8'}
        </div>
    {/if}
    {if isset($error)}
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="{l s='Close' mod='multisafepayofficial'}"><span aria-hidden="true">&times;</span></button>
            {$error|escape:'html':'UTF-8'}
        </div>
    {/if}
{/block}

{block name='page_content'}
    {if $tokens|count}
    <div class="table-responsive">
        <table class="table table-condensed table-bordered">
            <thead>
                <tr>
                    <th>{l s='Type' mod='multisafepayofficial'}</th>
                    <th>{l s='Card number' mod='multisafepayofficial'}</th>
                    <th>{l s='Expiry Date' mod='multisafepayofficial'}</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$tokens item=token}
                    <form method="POST" action="{$action|escape:'htmlall':'UTF-8'}">
                        <input type="hidden" name="submitRemoveToken" value="1" />
                        <input type="hidden" name="tokenId" value="{$token.tokenId|escape:'html':'UTF-8'}">
                        <tr>
                            <th>
                                {$token.paymentOptionName|escape:'html':'UTF-8'}
                            </th>
                            <td>
                                {$token.display|escape:'html':'UTF-8'}
                            </td>
                            <td>
                                {$token.expiryDate|escape:'html':'UTF-8'}
                            </td>
                            <td style="text-align:center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">&#xE888;</i> <span class="hidden-sm hidden-xs">{l s='Remove' mod='multisafepayofficial'}</span>
                                </button>
                            </td>
                        </tr>
                    </form>
                {/foreach}
            </tbody>
        </table>
    </div>
    {else}
    <p>{l s='You have not saved any payment details yet.' mod='multisafepayofficial'}</p>
    {/if}
{/block}
