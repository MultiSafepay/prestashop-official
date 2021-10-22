{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='Saved payment details' mod='multisafepayofficial'}
{/block}

{block name='notifications'}
    {if isset($success)}
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="{l s='Close' mod='multisafepayofficial'}"><span aria-hidden="true">&times;</span></button>
            {$success}
        </div>
    {/if}
    {if isset($error)}
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="{l s='Close' mod='multisafepayofficial'}"><span aria-hidden="true">&times;</span></button>
            {$error}
        </div>
    {/if}
{/block}

{block name='page_content'}
    {if $tokens|count}
    <div class="table-responsive">
        <table class="table table-condensed table-bordered">
            <thead>
                <tr>
                    <th>{l s='Type' mod='multisafepayofficial'}</th>
                    <th>{l s='Card number' mod='multisafepayofficial'}</th>
                    <th>{l s='Expiry Date' mod='multisafepayofficial'}</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$tokens item=token}
                    <form method="POST" action="{$action}">
                        <input type="hidden" name="submitRemoveToken" value="1" />
                        <input type="hidden" name="tokenId" value="{$token.tokenId}">
                        <tr>
                            <th>
                                {$token.paymentOptionName}
                            </th>
                            <td>
                                {$token.display}
                            </td>
                            <td>
                                {$token.expiryDate}
                            </td>
                            <td style="text-align:center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">&#xE888;</i> <span class="hidden-sm hidden-xs">{l s='Remove' mod='multisafepayofficial'}</span>
                                </button>
                            </td>
                        </tr>
                    </form>
                {/foreach}
            </tbody>
        </table>
    </div>
    {else}
    <p>{l s='You have not saved any payment details yet.' mod='multisafepayofficial'}</p>
    {/if}
{/block}
