<h3>{l s='Billmate Cardpay Payment' mod='billmatecardpay'}</h3>
<br/>

<form action="{$gatewayurl}" method="post" class="billmate" id="billmateform" style="display:none">
	<input type="hidden" name="order_id" value="{$order_id}" />
	<input type="hidden" name="amount" value="{$amount}" />
	<input type="hidden" name="merchant_id" value="{$merchant_id}" />
	<input type="hidden" name="currency" value="{$currency}" />
	<input type="hidden" name="language" value="{$language}" />
	<input type="hidden" name="accept_url" value="{$accept_url}" />
	<input type="hidden" name="pay_method" value="{$pay_method}" />
	<input type="hidden" name="prompt_name_entry" value="{$prompt_name_entry}" />
	<input type="hidden" name="return_method" value="{$return_method}" />
	<input type="hidden" name="callback_url" value="{$callback_url}" />
	<input type="hidden" name="do_3d_secure" value="{$do_3d_secure}" />
	<input type="hidden" name="cancel_url" value="{$cancel_url}" />
	<input type="hidden" name="capture_now" value="{$capture_now}" />
	<input type="hidden" name="mac" value="{$mac}" />
	<p>
		<img src="{$smarty.const._MODULE_DIR_}billmatecardpay/bm_kort_l.png" alt="{l s='Billmate Cardpay Payment' mod='billmatecardpay'}" style="float:left; margin: 0px 10px 5px 0px;" />
		{l s='You have chosen the Billmate Cardpay method.' mod='billmatecardpay'}
		<br/><br />
		{l s='The total amount of your order is' mod='billmatecardpay'}
		<span id="amount_{$currencies.0.id_currency}" class="price">{convertPrice price=$total}.</span>
		{if $use_taxes == 1}
		    {l s='(tax incl.)' mod='billmatecardpay'}
		{/if}
	</p>
	<p>
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='billmatecardpay'}.</b>
	</p>
	<p class="cart_navigation">
		<a href="{$link->getPageLink('order', true)}?step=3" class="button_large">{l s='Other payment methods' mod='billmatecardpay'}</a>
		<input type="button" value="{l s='I confirm my order' mod='billmatecardpay'}" class="exclusive_large" id="billmate_submit" onclick="document.getElementById('billmateform').submit();"/>
	</p>
</form>
<h2>{l s='Redirecting to gateway website' mod='billmatecardpay'}..... </h2>
<script type="text/javascript">
	$(document).ready(function(){ldelim}
		document.getElementById('billmateform').submit();
	{rdelim});
</script>
