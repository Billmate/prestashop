{capture name=path}{l s='Billmate Invoice' mod='billmateinvoice'}{/capture}
<div id="order_area">
<h2>{l s='Order summation' mod='billmateinvoice'}</h2>
{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
<style type="text/css">
.bsmall{
	font-size:1.1em;
}
.billmate *{
color: #7a7a7a!important;
}
.billmate{
text-align:center;
line-height:1.5em;
border-top:1px solid grey;
}
.blarge{
font-size:1.5em;
}
.bnormal{
font-size:1.3em;
}
.billbutton{
	font-weight:bold;
	color:#56AADB!important;

}
.billfooter {
display: block!important;
border-top: 1px solid grey!important;
padding-top: 13px!important;
}
/*.error {
font-size: 1.6em;
color: #000;
background-color: #FAD7D7;
padding: 1px 16px 23px;
border-radius: 6px;
}*/
#pno{ margin:auto!important;display:block!important;text-align:center!important;	 }
#billmate_submit{ width:26em!important; }
@media only screen and (min-width: 500px){
	#pno{ width:330px!important; }
	#billmate_submit{ width:26em!important; }
}
#billmate_submit {
	text-align: center;
}
</style>
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
		{if $fee != 0}<span id="amount">{l s=' This includes the invoice cost' mod='billmateinvoice'} {displayPrice price=$fee}</span>{/if}
		{if $use_taxes == 1}
		    ({l s='tax' mod='billmateinvoice'} {l s='incl.' mod='billmateinvoice'})<br/>
		{/if}
	</p>
	<p class="bnormal">
	    <b>{l s='Please fill following fields for complete order' mod='billmateinvoice'}</b>
    </p>
	<p class="blarge">
	    <label for="pno">{l s='Personal Number' mod='billmateinvoice'}</label>
	    <input type="text" value="" id="pno" name="pno" style="border:1px solid #D3D3D3;padding:0.2em;" required  />
	</p>
	<p class="bsmall">
	    <input type="checkbox" checked="checked" value="" id="confirm_my_age" name="confirm_my_age" required style="margin:0px" />
	    <label for="phone">{l s='My email %1$s is accurate and can be used for invoicing.' sprintf=[$customer_email] mod='billmateinvoice'}</label>
	</p>
	<p>
		<input type="button" name="submit" value="{l s='I confirm my order' mod='billmateinvoice'}" class="exclusive_large blarge" id="billmate_submit"/>
	</p>
	<p class="cart_navigation billfooter">
		<a href="{$previouslink}" class="billbutton blarge" style="float:left;line-height:1em;">{l s='Other payment methods' mod='billmateinvoice'}</a>
		<a id="terms" class="billbutton blarge" style="cursor:pointer!important;float:right">{l s='Terms of invoice' mod='billmateinvoice'}</a><script type="text/javascript">$.getScript("https://billmate.se/billmate/base.js", function(){ldelim}
		$("#terms").Terms("villkor",{ldelim}invoicefee: {$fee}{rdelim});
{rdelim});</script>
	</p>
</form>
<link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}billmateinvoice/style.css" />
<script src="{$smarty.const._MODULE_DIR_}billmateinvoice/js/billmatepopup.js"></script>
<script id="version" type="text/template">{$ps_version}</script>
<script type="text/javascript">
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
		var version =  $('#version').html();
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
		setTimeout(function(){
			if(typeof $.uniform == 'object')	$.uniform.restore();
		},5000);
        jQuery('#billmate_submit').click(function(){
            if($.trim( $('#pno').val()) == '' ){
                alert(emptypersonerror);
                return;
            }
			if($('#confirm_my_age').prop('checked') == true){
				getData( '' );
			}else{
				alert(checkbox_required);
			}
        });
    });
    {/literal}
</script>
</div>
