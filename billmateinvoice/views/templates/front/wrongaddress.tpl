<form action="javascript://">
	<span style="font-size: 1.3em;line-height: 1.4em;">
		{$firstname} {$lastname}
		<br/>{$address}
		<br/>{$zipcode}
		<br/>{$city}
		<br/>{$country}
	</span>
	<div style="margin-top:1em">
		<input data-theme="b" type="button" value="{l s='Yes, make purchase with this address' mod='billmateinvoice'}" onclick="modalWin.HideModalPopUp(); getData('&geturl=yes');" class="billmate_button"/>
	</div>
	<div>
		<a href="{$previouslink}" class="linktag" onclick="modalWin.HideModalPopUp();">{l s='I want to specify a different number or change payment method' mod='billmateinvoice'}</a>
	</div>
</form>