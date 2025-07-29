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

<div class="row">
    <div class="col-lg-12">
        <div class="fields-rows" id="dragula-container">
            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
            {if empty($payment_options)}
                <p class="text-center w-100 no-payments">{$no_payments|escape:'htmlall':'UTF-8'}</p>
            {else}
                {foreach from=$payment_options key=key item=paymentOption}
                    {assign var="options" value=$paymentOption->getGatewaySettings()}
                    {assign var="active" value=$paymentOption->isActive()}
                    {assign var="name" value=$paymentOption->getUniqueName()}
                    <div class="panel panel-default multisafepay-panel-payment-option" id="multisafepay-panel-payment-option-{$key|escape:'html':'UTF-8'}">
                        <div class="panel-heading" role="tab" id="multisafepay-heading-{$key|escape:'html':'UTF-8'}">
                            <h4 class="panel-title">
                                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#multisafepay-collapse-{$key|escape:'html':'UTF-8'}" aria-expanded="true" aria-controls="multisafepay-collapse-{$key|escape:'html':'UTF-8'}" class="collapsed">
                                    <span class="drag-and-drop-control"></span>
                                    <span class="status{if ($active == 1)} active{/if}"></span>
                                    <span class="title">{$paymentOption->getName()|escape:'html':'UTF-8'}</span>
                                </a>
                            </h4>
                        </div>
                        <div id="multisafepay-collapse-{$key|escape:'html':'UTF-8'}" class="panel-collapse collapse" role="tabpanel" aria-labelledby="multisafepay-heading-{$key|escape:'html':'UTF-8'}">
                            <div class="panel-body">
                                {foreach from=$options key=optionId item=option}
                                    {if $option['type'] == 'switch'}
                                        <div class="form-group">
                                            <label class="control-label col-lg-3">
                                                {$option['name']|escape:'html':'UTF-8'}
                                            </label>
                                            <div class="col-lg-9">
                                                <span class="multisafepay-payment-methods-list-switch switch prestashop-switch fixed-width-lg">
                                                    <input type="radio" class="" name="{$optionId|escape:'html':'UTF-8'}" id="{$optionId|escape:'html':'UTF-8'}_on" value="1" {if ($option['value'] == 1)}checked="checked"{/if} >
                                                    <label for="{$optionId|escape:'html':'UTF-8'}_on">{l s='Enabled' mod='multisafepayofficial'}</label>
                                                    <input type="radio" class="" name="{$optionId|escape:'html':'UTF-8'}" id="{$optionId|escape:'html':'UTF-8'}_off" value="0" {if (empty($option['value']))}checked="checked"{/if}>
                                                    <label for="{$optionId|escape:'html':'UTF-8'}_off">{l s='Disabled' mod='multisafepayofficial'}</label>
                                                    <a class="slide-button btn"></a>
                                                </span>
                                                {if isset($option['helperText'])}
                                                    <p class="help-block">{$option['helperText']|escape:'html':'UTF-8'}</p>
                                                {/if}
                                            </div>
                                        </div>
                                    {/if}
                                    {if $option['type'] == 'text'}
                                        <div class="form-group">
                                            <label class="control-label col-lg-3">
                                                {$option['name']|escape:'html':'UTF-8'}
                                            </label>
                                            <div class="col-lg-9">
                                                <input type="text" name="{$optionId|escape:'html':'UTF-8'}" placeholder="{$option['name']|escape:'html':'UTF-8'}" id="{$optionId|escape:'html':'UTF-8'}" value="{$option['value']|escape:'html':'UTF-8'}" class="{if isset($option['class'])}{$option['class']|escape:'html':'UTF-8'}{/if}">
                                                {if isset($option['helperText'])}
                                                    <p class="help-block">{$option['helperText']|escape:'html':'UTF-8'}</p>
                                                {/if}
                                            </div>
                                        </div>
                                    {/if}
                                    {if $option['type'] == 'multi-select'}
                                        <div class="form-group">
                                            <label class="control-label col-lg-3">
                                                {$option['name']|escape:'html':'UTF-8'}
                                            </label>
                                            <div class="col-lg-9">
                                                <select name="{$optionId|escape:'html':'UTF-8'}[]" id="{$optionId|escape:'html':'UTF-8'}[]"  multiple class="chosen">
                                                    {foreach $option['options'] as $multiSelectOption}
                                                        <option value="{$multiSelectOption['id']|escape:'html':'UTF-8'}" {if {$multiSelectOption['id']|escape:'html':'UTF-8'|in_array:$option['value']}} selected {/if}>{$multiSelectOption['name']|escape:'html':'UTF-8'}</option>
                                                    {/foreach}
                                                </select>
                                                {if isset($option['helperText'])}
                                                    <p class="help-block">{$option['helperText']|escape:'html':'UTF-8'}</p>
                                                {/if}
                                            </div>
                                        </div>
                                    {/if}
                                    {if $option['type'] == 'file'}
                                        <div class="form-group">
                                            <label class="control-label col-lg-3">
                                                {$option['name']|escape:'html':'UTF-8'}
                                            </label>
                                            <div class="col-lg-9">
                                                {$option['render']|escape:'htmlall':'UTF-8'}
                                                {if isset($option['helperText'])}
                                                    <p class="help-block">{$option['helperText']|escape:'html':'UTF-8'}</p>
                                                {/if}
                                            </div>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    </div>
                {/foreach}
            {/if}
        </div>
        </div>
    </div>
</div>
