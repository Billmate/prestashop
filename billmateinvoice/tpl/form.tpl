{capture name=path}{l s='Shipping' mod='billmateinvoice'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<div id="order_area">
<h2>{l s='Order summation' mod='billmateinvoice'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

<h3>{l s='Billmate Invoice Payment' mod='billmateinvoice'}</h3>

<form action="{$moduleurl}" method="post" class="billmate">
	<input type="hidden" name="confirm" value="1" />
	<p>
		<img src="{$smarty.const._MODULE_DIR_}billmateinvoice/bm_faktura_l.png" alt="{l s='Billmate Invoice Payment' mod='billmateinvoice'}" style="float:left; margin: 0px 10px 5px 0px;" />
		{l s='You have chosen the Billmate Invoice method.' mod='billmateinvoice'}
		<br/><br /><br/>
		{l s='The total amount of your order is' mod='billmateinvoice'}
		<span id="amount_{$currencies.0.id_currency}">{convertPrice price=$total}.</span>{if $fee != 0}<br/><span id="amount">{l s=' This includes the invoice cost' mod='billmateinvoice'} {displayPrice price=$fee}</span>{/if}
		{if $use_taxes == 1}
		    ({l s='tax' mod='billmateinvoice'} {l s='incl.' mod='billmateinvoice'})<br/>
		{/if}
	</p>
	<p class="clear"></p>
	<p class="text">
	    <b>{l s='Please fill following fields for complete order' mod='billmateinvoice'}</b>
    </p>
    <br/>	
	<p class="">
	    <label for="pno">{l s='Personal Number' mod='billmateinvoice'}</label>
	    <input type="text" value="" id="pno" name="pno" style="border:1px solid #D3D3D3" required  />
	</p>
	<p style="padding-top:14px">
	    <input type="checkbox" value="" checked="checked" id="confirm_my_age" name="confirm_my_age" required style="margin:0px" />
	    <label for="phone">{$customer_email}</label>
	</p>
	<p><br/>
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='billmateinvoice'}.</b> <a id="terms" style="cursor:pointer!important">{l s='Terms of invoice' mod='billmateinvoice'}</a><script type="text/javascript">$.getScript("https://billmate.se/billmate/base.js", function(){ldelim}
		$("#terms").Terms("villkor",{ldelim}invoicefee: {$fee}{rdelim});
{rdelim});</script>
	</p>
	<p class="cart_navigation" style="display:block!important">
		<a href="{$link->getPageLink('order.php', true)}?step=3" class="button_large" style="float:left">
			<input type="button" value="{l s='Other payment methods' mod='billmateinvoice'}" class="exclusive_large hideOnSubmit" />
		</a>
      <input type="button" name="submit" id="billmate_submit" value="{l s='I confirm my order' mod='billmateinvoice'}" class="exclusive_large hideOnSubmit" />
	</p>
</form>
<link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}billmateinvoice/style.css" />
<script src="{$smarty.const._MODULE_DIR_}billmateinvoice/js/billmatepopup.js"></script>

<script type="text/javascript">
var ajaxurl = "{$ajaxurl.this_path_ssl}validation.php?do=address&ajax=true";
var emptypersonerror = "{l s='PNO/SSN missing' mod='billmateinvoice'}";
var checkbox_required = "{l s='Please check the checkbox for confirm this e-mail address is correct and can be used for invoicing.' mod='billmateinvoice'}";

var loadingWindowTitle = '{l s='Processing....' mod='billmateinvoice'}';
var windowtitlebillmate= "{l s='You have entered the wrong address' mod='billmateinvoice'}";

    {literal}
	$(function() {
		$(document).ajaxStart(function() { jQuery('#billmate_submit').hide(); }).ajaxStop(function() { jQuery('#billmate_submit').show(); }); 
	});
    function getData( param ){
		modalWin.ShowMessage('',40,500,loadingWindowTitle);
        $('div.error').remove();
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
					modalWin.ShowMessage(response.content,310,500,windowtitlebillmate);
				}else{
					modalWin.HideModalPopUp();
					$('<div class="error">'+response.content+'</div>').insertAfter($('.breadcrumb').first());
				}
            }
        });
    }
    jQuery(document).ready(function(){
        jQuery('#billmate_submit').click(function(){
            if( $.trim( $('#pno').val()) == '' ){
                alert(emptypersonerror);
                return;
            }
			if(document.getElementById('confirm_my_age').checked ){
				getData( '' );
			}else{
				alert(checkbox_required);
			}
        });
    });
    {/literal}
</script>
</div>
