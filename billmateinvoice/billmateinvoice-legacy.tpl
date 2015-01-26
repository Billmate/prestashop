<p class="payment_module">
    <a href="{$moduleurl}" title="{l s='Pay by invoice' mod='billmateinvoice'}">
        <img src="{$smarty.const._MODULE_DIR_}billmateinvoice/bm_faktura_l.png" alt="{l s='Pay by invoice' mod='billmateinvoice'}" />
        {l s='Pay by invoice' mod='billmateinvoice'} {if $invoiceFee != 0} ({displayPrice price=$invoiceFee} {l s=' invoice fee is added to your order' mod='billmateinvoice'} ) {/if}

    </a>
</p>