{extends file=$layout}

{block name='content'}
    <p>{l s='An error occured during your payment.' mod='multisafepayofficial'}</p>
    <ul>
        <li>{$error_message|escape:'html':'UTF-8'}</li>
    </ul>
    <p>
        <a class="btn btn-primary button button-small" href="{$link->getPageLink('order.php', true, null, ['step' => 3])|escape:'htmlall':'UTF-8'}" title="{l s='Back to your shopping cart' mod='multisafepayofficial'}">
            <span><i class="material-icons">arrow_back</i> {l s='Back to your shopping cart' mod='multisafepayofficial'}</span>
        </a>
    </p>
{/block}
