
{if $accountActive && $monthly_amount != 0}
<p class="payment_module">
	<a href="{$var.this_path_ssl}payment.php?type=account">
		<img src="{$smarty.const._MODULE_DIR_}billmatepartpayment/bm_delbetalning_l.png" alt="Billmate" style="width:115px"/>
		{l s='Billmate account - Pay from' mod='billmatecardpay'} {displayPrice price=$monthly_amount} {l s='per month' mod='billmatecardpay'}.
	</a>
</p>
{/if}
{if $cardpayActive}
<p class="payment_module">
	<a href="{$var.this_path_ssl}payment.php?type=cardpay">
		<img src="{$smarty.const._MODULE_DIR_}billmatepartpayment/bm_delbetalning_l.png" alt="Billmate" style="width:115px"/>{* _{$iso_code} *}
		{l s='Billmate cardpay - Pay within 14 days' mod='billmatecardpay'}
	</a>
</p>
{/if}
{if isset($special) && $specialActive}
<p class="payment_module">
	<a href="{$var.this_path_ssl}payment.php?type=special">
		<img src="{$smarty.const._MODULE_DIR_}billmatepartpayment/bm_delbetalning_l.png" alt="Billmate" style="width:115px"/>
		{l s='Billmate - ' mod='billmatecardpay'}{$special}
	</a>
</p>
{/if}
