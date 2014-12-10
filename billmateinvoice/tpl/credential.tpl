{*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @version  Release: $Revision: 14011 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<form action="{$billmateinvoiceFormCredential}" method="POST">
	<fieldset class="billmate-blockSmall L">
		<legend><img src="{$module_dir}img/icon-mode.gif" alt="" /> {l s='Activation Settings' mod='billmateinvoice'}</legend>
		<h4>{l s='Set the mode of your module' mod='billmateinvoice'}</h4>
		<input type="radio" id="billmate_mod-beta" name="billmate_mod" {if $billmate_mod == 1}checked='checked'{/if} value="beta" /> <label for="billmate_mod-beta">{l s='Test' mod='billmateinvoice'}</label>
		<input type="radio" id="billmate_mod-live" name="billmate_mod" {if $billmate_mod == 0}checked='checked'{/if} value="live" /> <label for="billmate_mod-live">{l s='Live' mod='billmateinvoice'}</label>
	</fieldset>
	<fieldset class="billmate-blockSmall R">
		<legend><img src="{$module_dir}img/icon-modules.gif" alt="" /> {l s='Payment Options' mod='billmateinvoice'}</legend>
		<input type="hidden" name="submitBillmate" value="1"/>
		<p><input type="checkbox" id="billmate_active_invoice" name="billmate_active_invoice" {if $billmate_active_invoice == 1}checked='checked'{/if} value="1" /> <label for="billmate_active_invoice">{l s='Billmate Invoice' mod='billmateinvoice'}</label><br>
		<img src="{$smarty.const._MODULE_DIR_}billmateinvoice/bm_faktura_l.png" /></p>
	</fieldset>
	<div class="clear"></div>	
	<fieldset>
	<legend><img src="{$module_dir}img/icon-countries.gif" alt="" /> {$billmateinvoiceCredentialTitle}</legend>
		<h4>{$billmateinvoiceCredentialText}</h4>
		<ul class="billmate_list_click_country" style="margin-bottom:0px">
			{foreach from=$credentialInputVar key=name item=c}
			<li class="billmate_flag_{$name}"><img src="{$countryNames[$name].flag}" alt=""/> {$name|lower|capitalize|replace:'_':' '}</li>
			{/foreach}
		</ul>
		<ul class="billmate_list_country">
			{foreach from=$credentialInputVar key=country_name item=country}
			<li class="billmate_form_{$country_name}">
				<fieldset>
					<p class="title"><img src="{$module_dir}img/flag_{$country_name}.png" alt="" />{$country_name|lower|capitalize|replace:'_':' '}</p>
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
		<small class="footnote">{$billmateinvoiceCredentialFootText}</small>
	</fieldset>
	<div class="center pspace"><input type="submit" class="button" value="{l s='Save | Update' mod='billmateinvoice'}" /></div>
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
