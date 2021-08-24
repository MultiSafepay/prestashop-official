<form action="{$action}" id="multisafepay-form-{$gateway}" method="POST" class="additional-information">
    {if array_key_exists('text', $inputs)}
        {foreach from=$inputs.text item=text}
            <div class="form-group row">
                <div class="col-md-12">
                    <input type="text" name="{$text.name}" placeholder="{$text.placeholder}" value="{$text.value}" class="form-control" />
                </div>
            </div>
        {/foreach}
    {/if}
    {if array_key_exists('date', $inputs)}
        {foreach from=$inputs.date item=date}
            <div class="form-group row">
                <div class="col-md-12">
                    <input type="text" id="{$date.name}" name="{$date.name}" placeholder="{$date.placeholder}" value="{$date.value}" class="form-control" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"/>
                </div>
            </div>
        {/foreach}
    {/if}
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
