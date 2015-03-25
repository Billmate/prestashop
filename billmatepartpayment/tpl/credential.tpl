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
<form action="{$billmatepartpaymentFormCredential}" method="POST">
	<fieldset class="billmate-blockSmall L">
		<legend><img src="{$module_dir}img/icon-mode.gif" alt="" /> {l s='Activation Settings' mod='billmatepartpayment'}</legend>
		<h4>{l s='Set the mode of your module' mod='billmatepartpayment'}</h4>
		<input type="radio" id="billmate_mod-beta" name="billmate_mod" {if $billmate_mod == 1}checked='checked'{/if} value="beta" /> <label for="billmate_mod-beta">{l s='Test' mod='billmatepartpayment'}</label>
		<input type="radio" id="billmate_mod-live" name="billmate_mod" {if $billmate_mod == 0}checked='checked'{/if} value="live" /> <label for="billmate_mod-live">{l s='Live' mod='billmatepartpayment'}</label>
        {if $show_activate == true}
        <p>
            <h4>{l s='Automatic order activation in Billmate Online on order status update' mod='billmatebank'}</h4>
            <input type="radio" id="billmate_activation_on" name="billmate_activation" {if $billmate_activation == 1}checked="checked"{/if} value="1"/> <label for="billmate_activation">{l s='Enabled' mod='billmatebank'}</label>
            <input type="radio" id="billmate_activation_off" name="billmate_activation" {if $billmate_activation == 0}checked="checked"{/if} value="0"/> <label for="billmate_activation">{l s='Disabled' mod='billmatebank'}</label>

        </p>
        <p></p>
        <h4 id="activate_title" {if $billmate_activation == 0} style="display:none;" {/if}>{$status_activate.label}</h4>
        <div class="input-row">
            <select {if $billmate_activation == 0} style="display:none;" {/if} {if isset($status_activate.name)}name="{$status_activate.name}"{/if} {if isset($status_activate.id)}id="{$status_activate.id}"{/if} multiple="multiple">
                {html_options options=$status_activate.options selected=$status_activate.value}
            </select>
        </div>
        {/if}
    </fieldset>
	<fieldset class="billmate-blockSmall R">
		<legend><img src="{$module_dir}img/icon-modules.gif" alt="" /> {l s='Payment Options' mod='billmatepartpayment'}</legend>
        <input type="hidden" name="submitBillmate" value="1"/>
        <p>
            <input type="checkbox" id="billmate_active_partpayment" name="billmate_active_partpayment" {if $billmate_active_partpayment == 1}checked='checked'{/if} value="1" /> <label for="billmate_active_partpayment">{l s='Activate Billmate Part Payment.' mod='billmatepartpayment'}</label><br/>
            <img src="{$module_dir}img/billmate_account.png" alt=""/>
		</p>
		</fieldset>
	<div class="clear"></div>	
	<fieldset>
	<legend><img src="{$module_dir}img/icon-countries.gif" alt="" />{$billmatepartpaymentCredentialTitle}</legend>
		<h4>{$billmatepartpaymentCredentialText}</h4>
		<ul class="billmate_list_click_country" style="margin-bottom:0px">
			{foreach from=$credentialInputVar key=name item=c}
			<li class="billmate_flag_{$name}"><img src="{$countryNames[$name].flag}" alt=""/> {$name|lower|capitalize}</li>
			{/foreach}
		</ul>
		<ul class="billmate_list_country">
			{foreach from=$credentialInputVar key=country_name item=country}
			<li class="billmate_form_{$country_name}">
				<fieldset>
					<p class="title"><img src="{$module_dir}img/flag_{$country_name}.png" alt="" />{$country_name|lower|capitalize}</p>
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
		<small class="footnote">{$billmatepartpaymentCredentialFootText}</small>
	</fieldset>
	<div class="center pspace"><input type="submit" class="button" value="{l s='Save | Update' mod='billmatepartpayment'}" /></div>
</form>
<h4>{l s='PCI Classes' mod='billmatepartpayment'}</h4>
<table class="table double-bottom-space" cellpadding="0" cellspacing="0" width="100%">
	<tr>
		<th>{l s='Id' mod='billmatepartpayment'}</th><th>{l s='Eid' mod='billmatepartpayment'}</th>
		<th>{l s='Country' mod='billmatepartpayment'}</th><th>{l s='Description' mod='billmatepartpayment'}</th>
		<th>{l s='Start fee' mod='billmatepartpayment'}</th><th>{l s='Expire' mod='billmatepartpayment'}</th>
		<th>{l s='Invoice fee' mod='billmatepartpayment'}</th>
		<th>{l s='Interest' mod='billmatepartpayment'}</th><th>{l s='Minimum amount' mod='billmatepartpayment'}</th>
        <th>{l s='Maximum amount' mod='billmatepartpayment'}</th>
	</tr>
{foreach from=$billmate_pclass item=pclass key=k}
	<tr {if $k % 2 == 0}class="alt_row"{/if}>
		<td>{$pclass.id}</td>
		<td>{$pclass.eid}</td>
		<td>{$countryCodes[$pclass.country]}</td>
		<td>{$pclass.description}</td>
		<td>{$pclass.startfee}</td>
		<td>{$pclass.expire}</td>
		<td>{$pclass.invoicefee}</td>
		<td>{$pclass.interestrate}</td>
		<td>{$pclass.minamount}</td>
		<td>{$pclass.maxamount}</td>
	</tr>
{/foreach}
</table>

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
