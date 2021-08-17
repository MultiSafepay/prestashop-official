{extends file="helpers/form/form.tpl"}

{block name="input_row"}
    {if $input.section === 'multisafepay-support' || $input.section === 'multisafepay-payment-methods'}
        <div class="{$input.section}{if $input.type == 'hidden'} hide{/if}"{if $input.name == 'id_state'} id="contains_states"{if !$contains_states} style="display:none;"{/if}{/if}{if $input.name == 'dni'} id="dni_required"{if !$dni_required} style="display:none;"{/if}{/if}{if isset($tabs) && isset($input.tab)} data-tab-id="{$input.tab}"{/if}>
            {block name="field"}{/block}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="field"}
    {if isset($input.html_content)}
        {$input.html_content}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}



