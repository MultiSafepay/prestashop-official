<form action="{$action}" id="multisafepay-form" method="POST" class="additional-information">
    {if array_key_exists('select', $inputs)}
        {foreach from=$inputs.select item=select}
            <div class="form-group row">
                <div class="col-md-12">
                    <select class="form-control form-control-select" name="{$select.name}" id="multisafepay-ideal-issuer" required>
                        <option value="">{l s=$select.placeholder mod='multisafepay'}</option>
                        {foreach from=$select.options item=option}
                            <option value="{$option.value}">{$option.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/foreach}
    {/if}
    {if array_key_exists('hidden', $inputs)}
        {foreach from=$inputs.hidden item=hidden}
            <input type="hidden" name="{$hidden.name}" value="{$hidden.value}"/>
        {/foreach}
    {/if}
</form>
