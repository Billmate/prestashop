<style type="text/css">
.billmate-blockSmall.R{
	height:143.625px!important;
}
</style>
<form action="{$billmatecardpayFormCredential}" method="POST">
	<fieldset class="billmate-blockSmall L">
		<legend><img src="{$module_dir}img/icon-mode.gif" alt="" /> {l s='Activation Settings' mod='billmatebank'}</legend>
		<h4>{l s='Set the mode of your module' mod='billmatebank'}</h4>
		<input type="radio" id="billmate_mod-beta" name="billmate_mod" {if $billmate_mod == 1}checked='checked'{/if} value="beta" /> <label for="billmate_mod-beta">{l s='Test' mod='billmatebank'}</label>
		<input type="radio" id="billmate_mod-live" name="billmate_mod" {if $billmate_mod == 0}checked='checked'{/if} value="live" /> <label for="billmate_mod-live">{l s='Live' mod='billmatebank'}</label>
	</fieldset>
	<fieldset class="billmate-blockSmall R">
		<legend><img src="{$module_dir}img/icon-modules.gif" alt="" /> {l s='Payment Options' mod='billmatebank'}</legend>
		<input type="hidden" name="submitBillmate" value="1"/>
		<p><input type="checkbox" id="billmate_active_cardpay" name="billmate_active_cardpay" {if $billmate_active_cardpay == 1}checked='checked'{/if} value="1" /> <label for="billmate_active_cardpay">{l s='Billmate Bank' mod='billmatebank'}</label><br>
		<small><img src="{$smarty.const._MODULE_DIR_}billmatebank/billmate_bank_l.png"/></small></p>

		</fieldset>
	<div class="clear"></div>	
	<fieldset>
	<legend><img src="{$module_dir}img/icon-countries.gif" alt="" /> {$billmatebankCredentialTitle}</legend>
		<h4>{$billmatebankCredentialText}</h4>
		<ul class="billmate_list_click_country">
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
									{foreach from=$input.option item=option}
									<option value="{$option}">{$option}</option>
									{/foreach}
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
	<div class="center pspace"><input type="submit" class="button" value="{l s='Save | Update' mod='billmatebank'}" /></div>
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
