<h3>{l s='Billmate Bank Payment' mod='billmatebank'}</h3>
<br/>

<form action="{$gatewayurl}" method="post" class="billmate" id="billmateform" style="display:none">
	<input type="hidden" name="order_id" value="{$order_id}" />
	<input type="hidden" name="amount" value="{$amount}" />
	<input type="hidden" name="merchant_id" value="{$merchant_id}" />
	<input type="hidden" name="currency" value="{$currency}" />
	<input type="hidden" name="accept_url" value="{$accept_url}" />
	<input type="hidden" name="pay_method" value="{$pay_method}" />
	<input type="hidden" name="return_method" value="{$return_method}" />
	<input type="hidden" name="callback_url" value="{$callback_url}" />
	<input type="hidden" name="cancel_url" value="{$cancel_url}" />
	<input type="hidden" name="capture_now" value="{$capture_now}" />
	<input type="hidden" name="mac" value="{$mac}" />
	<p>
		<img src="{$smarty.const._MODULE_DIR_}billmatebank/bm_kort_l.png" alt="{l s='Billmate Bank Payment' mod='billmatebank'}" style="float:left; margin: 0px 10px 5px 0px;" />
		{l s='You have chosen the Billmate Bank method.' mod='billmatebank'}
		<br/><br />
		{l s='The total amount of your order is' mod='billmatebank'}
		<span id="amount_{$currencies.0.id_currency}" class="price">{convertPrice price=$total}.</span>
		{if $use_taxes == 1}
		    {l s='(tax incl.)' mod='billmatebank'}
		{/if}
	</p>
	<p>
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='billmatebank'}.</b>
	</p>
	<p class="cart_navigation">
		<a href="{$link->getPageLink('order', true)}?step=3" class="button_large">{l s='Other payment methods' mod='billmatebank'}</a>
		<input type="button" value="{l s='I confirm my order' mod='billmatebank'}" class="exclusive_large" id="billmate_submit" onclick="document.getElementById('billmateform').submit();"/>
	</p>
</form>
<h2>{l s='Redirecting to gateway website' mod='billmatebank'}..... </h2>
<script type="text/javascript">
	$(document).ready(function(){ldelim}
		document.getElementById('billmateform').submit();
	{rdelim});
</script>
