{if $accountActive && $monthly_amount != 0}
    <p class="payment_module">
        <a href="{$moduleurl}">
            <img src="{$smarty.const._MODULE_DIR_}billmatepartpayment/bm_delbetalning_l.png" alt="Billmate"/>
            {l s='Part pay from ' mod='billmatepartpayment'} {displayPrice price=$monthly_amount} {l s='per month' mod='billmatepartpayment'}.
        </a>
    </p>
{/if}