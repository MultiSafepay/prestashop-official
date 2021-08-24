{extends file=$layout}

{block name='content'}
    <p>{l s='An error occured during your payment.' mod='multisafepay'}</p>
    <ul>
        <li>{$error_message}</li>
    </ul>
    <p>
        <a class="btn btn-primary button button-small" href="{$link->getPageLink('order.php', true, null, ['step' => 3])|escape:'htmlall':'UTF-8'}" title="{l s='Back to your shopping cart' mod='multisafepay'}">
            <span><i class="material-icons">arrow_back</i> {l s='Back to your shopping cart' mod='multisafepay'}</span>
        </a>
    </p>
{/block}
