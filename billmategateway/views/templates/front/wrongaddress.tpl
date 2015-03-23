<form action="javascript://">
	<span style="font-size: 1.3em;line-height: 1.4em;">
		{$firstname} {$lastname}
        <br/>{$address}
        <br/>{$zipcode} {$city}
        <br/>{$country}
	</span>
    <div style="margin-top:1em">

        <input data-theme="b" type="button" id="billmate_button" data-method="{$method}" value="{l s='Yes, make purchase with this address' mod='billmategateway'}" class="billmate_button"/>
    </div>
    <div>
        <a href="{$previouslink}" class="linktag" onclick="modalWin.HideModalPopUp();">{l s='I want to specify a different number or change payment method' mod='billmategateway'}</a>
    </div>
</form>

