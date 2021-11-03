{assign var="gateway" value=$paymentOption->getGatewayCode()|escape:'html':'UTF-8'|lower}
{assign var="inputs" value=$paymentOption->getInputFields()}
{assign var="tokenization" value=$paymentOption->allowTokenization()}
<form action="{$action|escape:'htmlall':'UTF-8'}" id="multisafepay-form-{$gateway|escape:'html':'UTF-8'}" method="POST" class="additional-information{if $tokenization} multisafepay-tokenization{/if}">
    {foreach from=$inputs item=inputField}
        {if $inputField.type == 'hidden'}
            <input type="hidden" name="{$inputField['name']|escape:'html':'UTF-8'}" value="{$inputField.value|escape:'html':'UTF-8'}"/>
        {/if}
        {if $inputField.type == 'text'}
            <div class="form-group row{if isset($inputField.class)} {$inputField.class|escape:'html':'UTF-8'}{/if}">
                {if isset($inputField.label)}
                    <label for="{$inputField.name|escape:'html':'UTF-8'}">{$inputField.label|escape:'html':'UTF-8'}</label>
                {/if}
                <div class="col-md-12">
                    <input type="text" name="{$inputField.name|escape:'html':'UTF-8'}" placeholder="{$inputField.placeholder|escape:'html':'UTF-8'}" value="{$inputField.value|escape:'html':'UTF-8'}" class="form-control" />
                </div>
            </div>
        {/if}
        {if $inputField.type == 'date'}
            <div class="form-group row{if isset($inputField.class)} {$inputField.class|escape:'html':'UTF-8'}{/if}">
                {if isset($inputField.label)}
                    <label for="{$inputField.name|escape:'html':'UTF-8'}">{$inputField.label|escape:'html':'UTF-8'}</label>
                {/if}
                <div class="col-md-12">
                    <input type="date" name="{$inputField.name|escape:'html':'UTF-8'}" placeholder="{$inputField.placeholder|escape:'html':'UTF-8'}" value="{$inputField.value|escape:'html':'UTF-8'}" class="form-control" required pattern="[0-9]{literal}{4}{/literal}-[0-9]{literal}{2}{/literal}-[0-9]{literal}{2}{/literal}" />
                </div>
            </div>
        {/if}
        {if $inputField.type == 'select'}
            <div class="form-group row{if isset($inputField.class)} {$inputField.class|escape:'html':'UTF-8'}{/if}">
                <div class="col-md-12">
                    {if isset($inputField.label)}
                        <label for="{$inputField.name|escape:'html':'UTF-8'}">{$inputField.label|escape:'html':'UTF-8'}</label>
                    {/if}
                    <select class="form-control form-control-select" name="{$inputField.name|escape:'html':'UTF-8'}" required>
                        <option value="" disabled selected>{$inputField.placeholder|escape:'html':'UTF-8'}</option>
                        {foreach from=$inputField.options item=option}
                            <option value="{$option.value|escape:'html':'UTF-8'}">{$option.name|escape:'html':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/if}
        {if $inputField.type == 'radio'}
            <div class="form-group row{if isset($inputField.class)} {$inputField.class|escape:'html':'UTF-8'}{/if}">
                {if isset($inputField.label)}
                    <label for="{$inputField.name|escape:'html':'UTF-8'}">{$inputField.label|escape:'html':'UTF-8'}</label>
                {/if}
                <div class="col-md-12">
                    {foreach from=$inputField.options item=option}
                        <div class="radio">
                            <label>
                                <input type="radio" name="{$inputField.name|escape:'html':'UTF-8'}" value="{$option.value|escape:'html':'UTF-8'}"{if $option@first} checked{/if} />
                                {$option.name|escape:'html':'UTF-8'}
                            </label>
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}
        {if $inputField.type == 'checkbox'}
            <div class="form-group row checkbox{if isset($inputField.class)} {$inputField.class|escape:'html':'UTF-8'}{/if}">
                <div class="col-md-12">
                    <label class="left">
                        <input type="checkbox" name="{$inputField.name|escape:'html':'UTF-8'}" >
                        {if isset($inputField.label)}
                            {$inputField.label|escape:'html':'UTF-8'}
                        {/if}
                    </label>
                </div>
            </div>
        {/if}
    {/foreach}
</form>
