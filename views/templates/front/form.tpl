<form action="{$action}" id="multisafepay-form" method="POST" class="additional-information">
    {if array_key_exists('issuers', $inputs)}
        <div class="form-group row">
            <div class="col-md-12">
                <select class="form-control form-control-select" name="issuer_id" id="msp-ideal-issuer" required>
                    <option value="">Choose your bank</option>
                    {foreach from=$inputs.issuers item=issuer}
                        <option value="{$issuer.code}">{$issuer.description}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/if}
    {if array_key_exists('hidden', $inputs)}
        {foreach from=$inputs.hidden item=hidden}
            <input type="hidden" name="{$hidden.name}" value="{$hidden.value}"/>
        {/foreach}
    {/if}
</form>
