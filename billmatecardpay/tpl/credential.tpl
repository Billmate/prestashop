<style type="text/css">
.billmate-blockSmall.R{
	min-height:143.625px!important;
}
</style>

<form action="{$billmatecardpayFormCredential}" method="POST">
	<fieldset class="billmate-blockSmall L">
		<legend><img src="{$module_dir}img/icon-mode.gif" alt="" /> {l s='Activation Settings' mod='billmatecardpay'}</legend>
		<h4>{l s='Set the mode of your module' mod='billmatecardpay'}</h4>
		<input type="radio" id="billmate_mod-beta" name="billmate_mod" {if $billmate_mod == 1}checked='checked'{/if} value="beta" /> <label for="billmate_mod-beta">{l s='Test' mod='billmatecardpay'}</label>
		<input type="radio" id="billmate_mod-live" name="billmate_mod" {if $billmate_mod == 0}checked='checked'{/if} value="live" /> <label for="billmate_mod-live">{l s='Live' mod='billmatecardpay'}</label>
		<br/>
		<h3>{l s='Optional Settings' mod='billmatecardpay'}</h3>
		<p>
		<input type="checkbox" id="billmate_prompt_name" name="billmate_prompt_name" {if $billmate_prompt_name == 'YES'}checked='checked'{/if} value="YES" /> <label for="billmate_prompt_name">{l s='Enable Prompt Name' mod='billmatecardpay'}</label>
		</p><p>
		<input type="checkbox" id="billmate_3dsecure" name="billmate_3dsecure" {if $billmate_3dsecure == 'YES'}checked='checked'{/if} value="YES" /> <label for="billmate_3dsecure">{l s='Enable 3D Secure (Recommended for fraud protection)' mod='billmatecardpay'}</label>
		</p>
        {if $show_activate == true}
        <p>
            <h4>{l s='Automatic order activation in Billmate Online on order status update' mod='billmatebank'}</h4>
            <input type="radio" id="billmate_activation_on" name="billmate_activation" {if $billmate_activation == 1}checked="checked"{/if} value="1"/> <label for="billmate_activation">{l s='Enabled' mod='billmatebank'}</label>
            <input type="radio" id="billmate_activation_off" name="billmate_activation" {if $billmate_activation == 0}checked="checked"{/if} value="0"/> <label for="billmate_activation">{l s='Disabled' mod='billmatebank'}</label>

        </p>
        <p></p>
        <h4 id="activate_title" {if $billmate_activation == 0} style="display:none;"{/if}>{$status_activate.label}</h4>
        <div class="input-row">

            <select {if $billmate_activation == 0} style="display:none;" {/if} {if isset($status_activate.name)}name="{$status_activate.name}"{/if} {if isset($status_activate.id)}id="{$status_activate.id}"{/if} multiple="multiple">
                {html_options options=$status_activate.options selected=$status_activate.value}
            </select>
        </div>
        {/if}
	</fieldset>
	<fieldset class="billmate-blockSmall R">
		<legend><img src="{$module_dir}img/icon-modules.gif" alt="" /> {l s='Payment Options' mod='billmatecardpay'}</legend>
		<input type="hidden" name="submitBillmate" value="1"/>
		<p><input type="checkbox" id="billmate_active_cardpay" name="billmate_active_cardpay" {if $billmate_active_cardpay == 1}checked='checked'{/if} value="1" /> <label for="billmate_active_cardpay">{l s='Billmate Cardpay' mod='billmatecardpay'}</label><br>
		<small><img src="{$smarty.const._MODULE_DIR_}billmatecardpay/bm_kort_l.png"/></small></p>

		<h4 style="margin: 1em 0; margin-top: 0;">{l s='Set the mode authentication' mod='billmatecardpay'}</h4>
		<span style="display:block"><input type="radio" id="billmate_authmod-sale" name="billmate_authmod" {if $billmate_authmod == 'sale'}checked='checked'{/if} value="sale" /> <label for="billmate_authmod-sale">{l s='Sale' mod='billmatecardpay'}</label></span>
		<span style="display:block"><input type="radio" id="billmate_authmod-authorization" name="billmate_authmod" {if $billmate_authmod == 'auth'}checked='checked'{/if} value="auth" /> <label for="billmate_authmod-authorization">{l s='Authorization' mod='billmatecardpay'}</label></span>
		</fieldset>
	<div class="clear"></div>	
	<fieldset>
	<legend><img src="{$module_dir}img/icon-countries.gif" alt="" /> {$billmatecardpayCredentialTitle}</legend>
		<h4>{$billmatecardpayCredentialText}</h4>
		<ul class="billmate_list_click_country" style="margin-bottom:0px">
			{foreach from=$credentialInputVar key=name item=c}
			<li class="billmate_flag_{$name}"><img src="{$countryNames[$name].flag}" alt=""/>{$name|lower|capitalize}</li>
			{/foreach}
		</ul>
		<ul class="billmate_list_country">
			{foreach from=$credentialInputVar key=country_name item=country}
			<li class="billmate_form_{$country_name}">
				<fieldset>
					<p class="title"><img src="{$countryNames[$name].flag}" alt=""/>{$country_name|lower|capitalize}</p>
					<div class="fieldset-wrap">						
						{foreach from=$country item=input}
						{if $input.type == 'text'}
						<div id="billmateInput{$input.name}" class="input-row">
							<span>{$input.label}</span>
							<input type="{$input.type}" name="{$input.name}" id="{$input.name}" value="{$input.value}" />{$input.desc}
						</div>
						{elseif $input.type == 'hidden'}
							<input type="{$input.type}" name="{$input.name}" id="{$input.name}" value="{$input.value}" />
						{elseif $input.type == 'select'}
							<div class="input-row">
								<span>{$input.label}</span>
								<select {if isset($input.id)}id="{$input.id}"{/if} {if isset($input.name)}name="{$input.name}"{/if}>
									<option>{l s='Choose' mod='billmatebank'}</option>
									{html_options options=$input.options selected=$input.value}
								</select>
							</div>
						{/if}
						{/foreach}
					</div>
				</fieldset>
			</li>
			{/foreach}
		</ul>
		<small class="footnote">{$billmatecardpayCredentialFootText}</small>
	</fieldset>
	<div class="center pspace"><input type="submit" class="button" value="{l s='Save | Update' mod='billmatecardpay'}" /></div>
</form>

<script type="text/javascript">
    var activated = new Array();
	var i = 0;
	{foreach from=$activateCountry item=a}
		activated[i] = "{$a}";
		i++;
	{/foreach}

	function in_array(array, p_val) {
	    var l = array.length;
	    for(var i = 0; i < l; i++) {
	        if(array[i] == p_val) {
	            rowid = i;
	            return true;
	        }
	    }
	    return false;
	}
	
	$(document).ready(
	    function()
	    {
            var length = $("#activationSelect option").length;
            length = length > 19 ? 20 : length
            $('#activationSelect').attr('size',length);
            $('#billmate_activation_on').click(function(){
                $('#activationSelect').show();
                $('#activate_title').show();
            });
            $('#billmate_activation_off').click(function(){
                $('#activationSelect').hide();
                $('#activate_title').hide();

            })
            $('li[class^="billmate_form_"]').hide();
            $("li[class^='billmate_form']").each(
                function()
                {
                var country = $(this).attr('class').replace('billmate_form_', '');
                if (in_array(activated, country))
                {
                    $('.billmate_form_'+country).show();
                    $('.billmate_form_'+country).append('<input type="hidden" name="activate'+country+'" value="on" id="billmate_activate'+country+'"/>');
                }
                }
            );
	    }
	);
</script>
