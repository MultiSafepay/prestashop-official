<form action="{$action}" id="multisafepay-form-{$gateway}" method="POST" class="additional-information">
    {foreach from=$inputs item=inputField}
        {if $inputField.type == 'hidden'}
            <input type="hidden" name="{$inputField['name']}" value="{$inputField.value}"/>
        {/if}
        {if $inputField.type == 'text'}
            <div class="form-group row">
                <div class="col-md-12">
                    <input type="text" name="{$inputField.name}" placeholder="{$inputField.placeholder}" value="{$inputField.value}" class="form-control" />
                </div>
            </div>
        {/if}
        {if $inputField.type == 'date'}
            <div class="form-group row">
                <div class="col-md-12">
                    <input type="date" name="{$inputField.name}" placeholder="{$inputField.placeholder}" value="{$inputField.value}" class="form-control" required pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}"/>
                </div>
            </div>
        {/if}
        {if $inputField.type == 'select'}
            <div class="form-group row">
                <div class="col-md-12">
                    {if isset($inputField.label)}
                        <label for="{$inputField.name}">{$inputField.label}</label>
                    {/if}
                    <select class="form-control form-control-select" name="{$inputField.name}" required>
                        <option value="" disabled selected>{$inputField.placeholder}</option>
                        {foreach from=$inputField.options item=option}
                            <option value="{$option.value}">{$option.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/if}
        {if $inputField.type == 'checkbox'}
            <div class="form-group row">
                <div class="col-md-12">
                    <label class="left">
                        <input type="checkbox" class="form-check-input" name="{$inputField.name}" > {$inputField.label}
                    </label>
                    {if isset($inputField.helperText)}
                        <p class="help-block">{$inputField.helperText}</p>
                    {/if}
                </div>
            </div>
        {/if}
    {/foreach}
</form>
