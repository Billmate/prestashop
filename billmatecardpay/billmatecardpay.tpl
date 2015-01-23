
<style>
	p.payment_module a.billmatecard {
		background: url("{$smarty.const._MODULE_DIR_}billmatecardpay/bm_kort_l.png") 15px 15px no-repeat #fbfbfb;
		padding-left: 180px;
	}
	p.payment_module a.billmatecard:after{
		display: block;
		content: "\f054";
		position: absolute;
		right: 15px;
		margin-top: -11px;
		top: 50%;
		font-family: "FontAwesome";
		font-size: 25px;
		height: 22px;
		width: 14px;
		color: #777;
	}
</style>

<p class="payment_module">
	<a href="{$moduleurl}" title="{l s='Pay with billmate cardpay' mod='billmatecardpay'}" class="billmatecard">
		{l s='Pay with Visa & Mastercard' mod='billmatecardpay'}
		<br style="clear:both;" />
	</a>
</p>
