<div class="row">
    <div class="col-lg-12">
        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
            {foreach from=$payment_options key=key item=paymentOption}
                {assign var="options" value=$paymentOption->getGatewaySettings()}
                {assign var="name" value=$paymentOption->getUniqueName()}
                {assign var="active" value=$options["MULTISAFEPAY_GATEWAY_`$name`"]}
                <div class="panel panel-default">
                    <div class="panel-heading" role="tab" id="multisafepay-heading-{$key}">
                        <h4 class="panel-title">
                            <a role="button" data-toggle="collapse" data-parent="#accordion" href="#multisafepay-collapse-{$key}" aria-expanded="true" aria-controls="multisafepay-collapse-{$key}" class="collapsed">
                                <span class="status{if ($active == 1)} active{/if}"></span>
                                <span class="title">{$paymentOption->name}</span>
                            </a>
                        </h4>
                    </div>
                    <div id="multisafepay-collapse-{$key}" class="panel-collapse collapse" role="tabpanel" aria-labelledby="multisafepay-heading-{$key}">
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {$paymentOption->name}
                                </label>
                                <div class="col-lg-9">
                                    <span class="multisafepay-payment-methods-list-switch switch prestashop-switch fixed-width-lg">
                                        <input type="radio" class="" name="MULTISAFEPAY_GATEWAY_{$name}" id="MULTISAFEPAY_GATEWAY_{$name}_on" value="1" {if ($active == 1)}checked="checked"{/if} >
                                        <label for="MULTISAFEPAY_GATEWAY_{$name}_on">{l s='Enabled' mod='multisafepay'}</label>
                                        <input type="radio" class="" name="MULTISAFEPAY_GATEWAY_{$name}" id="MULTISAFEPAY_GATEWAY_{$name}_off" value="0" {if (empty($active))}checked="checked"{/if}>
                                        <label for="MULTISAFEPAY_GATEWAY_{$name}_off">{l s='Disabled' mod='multisafepay'}</label>
                                        <a class="slide-button btn"></a>
                                    </span>
                                </div>
                            </div>
                            {if $name === 'GENERIC'}
                                <div class="form-group">
                                    <label class="control-label col-lg-3">
                                        {l s='Gateway code' mod='multisafepay'}
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="text" name="MULTISAFEPAY_GATEWAY_CODE_{$name}" id="MULTISAFEPAY_GATEWAY_CODE_{$name}" value="{$options["MULTISAFEPAY_GATEWAY_CODE_`$name`"]}" class="">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-lg-3">
                                        {l s='Gateway Image Icon' mod='multisafepay'}
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="text" name="MULTISAFEPAY_GATEWAY_IMAGE_{$name}" id="MULTISAFEPAY_GATEWAY_IMAGE_{$name}" value="{$options["MULTISAFEPAY_GATEWAY_IMAGE_`$name`"]}" class="">
                                    </div>
                                </div>
                            {/if}
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='Title' mod='multisafepay'}
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="MULTISAFEPAY_TITLE_{$name}" id="MULTISAFEPAY_TITLE_{$name}" value="{$options["MULTISAFEPAY_TITLE_`$name`"]}" class="">
                                    <p class="help-block">{l s='The title will be shown to the customer at the checkout page' mod='multisafepay'}.</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='Description' mod='multisafepay'}
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="MULTISAFEPAY_DESCRIPTION_{$name}" id="MULTISAFEPAY_DESCRIPTION_{$name}" value="{$options["MULTISAFEPAY_DESCRIPTION_`$name`"]}" class="">
                                    <p class="help-block">{l s='The description will be shown to the customer at the checkout page' mod='multisafepay'}.</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='Minimum amount' mod='multisafepay'}
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="MULTISAFEPAY_MIN_AMOUNT_{$name}" id="MULTISAFEPAY_MIN_AMOUNT_{$name}" value="{$options["MULTISAFEPAY_MIN_AMOUNT_`$name`"]}" class="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='Maximum amount' mod='multisafepay'}
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="MULTISAFEPAY_MAX_AMOUNT_{$name}" id="MULTISAFEPAY_MAX_AMOUNT_{$name}" value="{$options["MULTISAFEPAY_MAX_AMOUNT_`$name`"]}" class="">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='Select countries' mod='multisafepay'}
                                </label>
                                <div class="col-lg-9">
                                    <select name="MULTISAFEPAY_COUNTRIES_{$name}[]" id="MULTISAFEPAY_COUNTRIES_{$name}[]"  multiple class="chosen">
                                        {foreach $countries as $country}
                                            <option value="{$country.id_country}" {if {$country.id_country|in_array:$options["MULTISAFEPAY_COUNTRIES_$name"]}} selected {/if}>{$country.name}</option>
                                        {/foreach}
                                    </select>
                                    <p class="help-block">{l s='Leave blank to support all countries' mod='multisafepay'}.</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='Select currencies' mod='multisafepay'}
                                </label>
                                <div class="col-lg-9">
                                    <select name="MULTISAFEPAY_CURRENCIES_{$name}[]" id="MULTISAFEPAY_CURRENCIES_{$name}[]"  multiple class="chosen">
                                        {foreach $currencies as $currency}
                                            <option value="{$currency.id}" {if {$currency.id|in_array:$options["MULTISAFEPAY_CURRENCIES_$name"]}} selected {/if}>{$currency.name}</option>
                                        {/foreach}
                                    </select>
                                    <p class="help-block">{l s='Leave blank to support all currencies' mod='multisafepay'}.</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='Select customer groups' mod='multisafepay'}
                                </label>
                                <div class="col-lg-9">
                                    <select name="MULTISAFEPAY_CUSTOMER_GROUPS_{$name}[]" id="MULTISAFEPAY_CUSTOMER_GROUPS_{$name}[]"  multiple class="chosen">
                                        {foreach $customer_groups as $customer_group}
                                            <option value="{$customer_group.id_group}" {if {$customer_group.id_group|in_array:$options["MULTISAFEPAY_CURRENCIES_$name"]}} selected {/if}>{$customer_group.name}</option>
                                        {/foreach}
                                    </select>
                                    <p class="help-block">{l s='Leave blank to support all customer groups' mod='multisafepay'}.</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    {l s='Sort order' mod='multisafepay'}
                                </label>
                                <div class="col-lg-9">
                                    <input type="text" name="MULTISAFEPAY_SORT_ORDER_{$name}" id="MULTISAFEPAY_SORT_ORDER_{$name}" value="{$options["MULTISAFEPAY_SORT_ORDER_`$name`"]}" class="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>
