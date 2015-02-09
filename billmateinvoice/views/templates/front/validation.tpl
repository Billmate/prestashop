{capture name=path}{l s='Billmate Invoice' mod='billmateinvoice'}{/capture}
<div id="order_area">
<h2>{l s='Order summation' mod='billmateinvoice'}</h2>
{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s='Billmate Invoice Payment' mod='billmateinvoice'}</h3>
{* $link->getModuleLink('billmateinvoice', 'getaddress', [], true) *}
<form action="javascript://" method="post" class="billmate">
	<input type="hidden" name="confirm" value="1" />
	<p>
		<img src="{$smarty.const._MODULE_DIR_}billmateinvoice/bm_faktura_m.png" alt="{l s='Billmate Invoice Payment' mod='billmateinvoice'}" style="margin: 0px 10px 5px 0px;" />
	</p>
	<p class="blarge" style="padding-bottom:10px">
		{l s='The total amount of your order is' mod='billmateinvoice'}<span id="amount_{$currencies.0.id_currency}"> {convertPrice price=$total}.</span>
	</p>
	<p class="bnormal">
		{if $fee != 0}<span id="amount">{l s=' This includes the invoice cost' mod='billmateinvoice'} {displayPrice price=$fee} {if $use_taxes == 1}
            ({l s='tax' mod='billmateinvoice'} {l s='incl.' mod='billmateinvoice'}).<br/>
        {/if}</span>{/if}
	</p>
	<p class="bnormal">
	    <b>{l s='Please fill following fields to complete your order' mod='billmateinvoice'}</b>
    </p>
	<p class="blarge">
	    <label for="pno">{l s='Personal Number / Organization Number' mod='billmateinvoice'}</label>
	    <input type="text" value="" id="billmate_pno" name="pno" style="border:1px solid #D3D3D3;padding:0.2em;" required  />
	</p>
	<p class="bsmall">
        <label for="confirm"><input type="checkbox" checked="checked" value="" id="confirm_my_age" class="comparator" name="confirm_my_age" required />
	    {l s='My email %1$s is accurate and can be used for invoicing.' sprintf=[$customer_email] mod='billmateinvoice'}
            <br/> <a id="terms" class="terms" style="cursor:pointer!important;">{l s='I confirm the terms for invoice payment' mod='billmateinvoice'}</a></label>
	</p>
	<p>
		<input type="button" name="submit" value="{l s='I confirm my order' mod='billmateinvoice'}" style="width:26em!important" class="exclusive_large blarge" id="billmate_submit"/>
	</p>
	<p class="cart_navigation billfooter">
		<a href="{$previouslink}" class="billbutton blarge underline" style="float:left;line-height:1em;">{l s='Other payment methods' mod='billmateinvoice'}</a>
		<a id="terms" class="billbutton blarge terms underline" style="cursor:pointer!important;float:right">{l s='Terms of invoice' mod='billmateinvoice'}</a><script type="text/javascript">$.getScript("https://billmate.se/billmate/base.js", function(){ldelim}
		$(".terms").Terms("villkor",{ldelim}invoicefee: {$fee}{rdelim});
{rdelim});</script>
	</p>
</form>
<link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}billmateinvoice/style.css" />
<script src="{$smarty.const._MODULE_DIR_}billmateinvoice/js/billmatepopup.js"></script>

<script type="text/javascript">
var version = "{$ps_version}"
var ajaxurl = "{$link->getModuleLink('billmateinvoice', 'getaddress', ['ajax'=> 0], true)}";
var emptypersonerror = "{l s='PNO/SSN missing' mod='billmateinvoice'}";
var checkbox_required = "{l s='Please check the checkbox for confirm this e-mail address is correct and can be used for invoicing.' mod='billmateinvoice'}";
{if $opc|default:false }
 var carrierurl = "{$link->getPageLink("order-opc", true)}";
{else}
var carrierurl = "{$link->getPageLink("order", true)}";
{/if}
var loadingWindowTitle = '{l s='Processing....' mod='billmateinvoice'}';
var windowtitlebillmate= "{l s='Pay by invoice can be made only to the address listed in the National Register. Would you make the purchase with address:' mod='billmateinvoice'}";
if( document.getElementById('center_column') != null ){
	document.getElementById('center_column').className = 'grid_9';
}
versionCompare = function(left, right) {
	if (typeof left + typeof right != 'stringstring')
		return false;

	var a = left.split('.')
			,   b = right.split('.')
			,   i = 0, len = Math.max(a.length, b.length);

	for (; i < len; i++) {
		if ((a[i] && !b[i] && parseInt(a[i]) > 0) || (parseInt(a[i]) > parseInt(b[i]))) {
			return 1;
		} else if ((b[i] && !a[i] && parseInt(b[i]) > 0) || (parseInt(a[i]) < parseInt(b[i]))) {
			return -1;
		}
	}

	return 0;
}
$('#right_column').remove();
    {literal}
	$(function() {
		$(document).ajaxStart(function() { jQuery('#billmate_submit').hide(); }).ajaxStop(function() { jQuery('#billmate_submit').show(); }); 
		$('#right_column').remove();
		if( document.getElementById('center_column') != null ){
			document.getElementById('center_column').className = 'grid_9';
		}
	});
    function getData( param ){

		ShowMessage('',loadingWindowTitle);
		if(versionCompare(version,'1.6') == 1){
			$('div.alert-danger').remove();
		} else {
			$('div.error').remove();
		}
        jQuery.post( ajaxurl+param, jQuery('.billmate').serializeArray(), function(json){
            eval('var response = '+ json );
            if( response.success ){
            	if( typeof response.redirect != 'undefined' ){
            		window.location.href= response.redirect;
            	} else {
					if( typeof response.action != 'undefined' ) {
						$.post(carrierurl,response.action, function(){
							getData( '&geturl=yes' );
						});
					} else {
						getData( '&geturl=yes' );
					}
                }
            } else {
				if( typeof response.popup != 'undefined' && response.popup){
					ShowMessage(response.content,windowtitlebillmate);
				}else{
					modalWin.HideModalPopUp();
					if(versionCompare(version,'1.6') == 1){
						$('<div class="alert alert-danger">'+response.content+'</div>').insertBefore($('#order_area').first());
					} else {
						$('<div class="error">'+response.content+'</div>').insertBefore($('#order_area').first());
					}
				}
            }
        });
		
		
			
    }
    jQuery(document).ready(function(){

        jQuery('#billmate_submit').click(function(){
            if($.trim( $('#billmate_pno').val()) == '' ){
                alert(emptypersonerror);
                return;
            }
			if($('#confirm_my_age').prop('checked') == true){
				getData( '' );
			}else{
				alert($('<textarea/>').html(checkbox_required).text());
			}
        });
    });
    {/literal}
</script>
</div>
