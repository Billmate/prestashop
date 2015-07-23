{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}
<form action="javascript://">
	<span style="font-size: 1.3em;line-height: 1.4em;">
		{$firstname|escape:'html'} {$lastname|escape:'html'}
        <br/>{$address|escape:'html'}
        <br/>{$zipcode|escape:'html'} {$city|escape:'html'}
        <br/>{$country|escape:'html'}
	</span>

    <div style="margin-top:1em">

        <input data-theme="b" type="button" id="billmate_button" class="button" data-method="{$method|escape:'html'}"
               value="{l s='Yes, make purchase with this address' mod='billmategateway'}" class="billmate_button"/>
    </div>
    <div>
        <a href="{$previousLink|escape:'url'}" class="linktag"
           onclick="modalWin.HideModalPopUp();">{l s='I want to specify a different number or change payment method' mod='billmategateway'}</a>
    </div>
</form>

