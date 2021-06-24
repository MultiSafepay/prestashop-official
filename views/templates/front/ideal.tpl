<form action="{$action}" id="multisafepay-form" method="POST" class="additional-information">
    <div class="form-group row">
        <div class="col-md-12">
            <select class="form-control form-control-select" name="issuer_id" id="msp-ideal-issuer" required>
                <option value="">{$select_bank}</option>
                {foreach from=$issuers item=issuer}
                    <option value="{$issuer.code}">{$issuer.description}</option>
                {/foreach}
            </select>
        </div>
    </div>
    {foreach from=$inputs item=input}
        <input type="hidden" name="{$input.name}" value="{$input.value}"/>
    {/foreach}
</form>
