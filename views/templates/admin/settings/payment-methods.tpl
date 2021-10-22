<div class="row">
    <div class="col-lg-12">
        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
            {foreach from=$payment_options key=key item=paymentOption}
                {assign var="options" value=$paymentOption->getGatewaySettings()}
                {assign var="active" value=$paymentOption->isActive()}
                {assign var="name" value=$paymentOption->getUniqueName()}
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="multisafepay-heading-{$key}">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#multisafepay-collapse-{$key}" aria-expanded="true" aria-controls="multisafepay-collapse-{$key}" class="collapsed">
                                <span class="status{if ($active == 1)} active{/if}"></span>
                                <span class="title">{$paymentOption->getName()}</span>
                            </a>
                        </h4>
                    </div>
                    <div id="multisafepay-collapse-{$key}" class="panel-collapse collapse" role="tabpanel" aria-labelledby="multisafepay-heading-{$key}">
                        <div class="panel-body">
                            {foreach from=$options key=optionId item=option}
                                {if $option['type'] == 'switch'}
                                    <div class="form-group">
                                        <label class="control-label col-lg-3">
                                            {$option['name']}
                                        </label>
                                        <div class="col-lg-9">
                                            <span class="multisafepay-payment-methods-list-switch switch prestashop-switch fixed-width-lg">
                                                <input type="radio" class="" name="{$optionId}" id="{$optionId}_on" value="1" {if ($option['value'] == 1)}checked="checked"{/if} >
                                                <label for="{$optionId}_on">{l s='Enabled' mod='multisafepayofficial'}</label>
                                                <input type="radio" class="" name="{$optionId}" id="{$optionId}_off" value="0" {if (empty($option['value']))}checked="checked"{/if}>
                                                <label for="{$optionId}_off">{l s='Disabled' mod='multisafepayofficial'}</label>
                                                <a class="slide-button btn"></a>
                                            </span>
                                            {if isset($option['helperText'])}
                                                <p class="help-block">{$option['helperText']}</p>
                                            {/if}
                                        </div>
                                    </div>
                                {/if}
                                {if $option['type'] == 'text'}
                                    <div class="form-group">
                                        <label class="control-label col-lg-3">
                                            {$option['name']}
                                        </label>
                                        <div class="col-lg-9">
                                            <input type="text" name="{$optionId}" id="{$optionId}" value="{$option['value']}" class="">
                                            {if isset($option['helperText'])}
                                                <p class="help-block">{$option['helperText']}</p>
                                            {/if}
                                        </div>
                                    </div>
                                {/if}
                                {if $option['type'] == 'multi-select'}
                                    <div class="form-group">
                                        <label class="control-label col-lg-3">
                                            {$option['name']}
                                        </label>
                                        <div class="col-lg-9">
                                            <select name="{$optionId}[]" id="{$optionId}[]"  multiple class="chosen">
                                                {foreach $option['options'] as $multiSelectOption}
                                                    <option value="{$multiSelectOption['id']}" {if {$multiSelectOption['id']|in_array:$option['value']}} selected {/if}>{$multiSelectOption['name']}</option>
                                                {/foreach}
                                            </select>
                                            {if isset($option['helperText'])}
                                                <p class="help-block">{$option['helperText']}</p>
                                            {/if}
                                        </div>
                                    </div>
                                {/if}
                            {/foreach}
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>
