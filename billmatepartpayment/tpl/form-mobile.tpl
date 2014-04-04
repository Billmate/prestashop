<div  style="margin: 0% 3%;">
{capture name=path}{l s='Billmate Partpayment' mod='billmatepartpayment'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<h2>{l s='Order summary' mod='billmatepartpayment'}</h2>
<style type="text/css">
#right_column{
	display:none
}

.pagediv{
	width:100%;
	border-top:1px solid grey;
	border-bottom: 1px solid grey;
	text-align:center;
}
.pagediv .footerrow span{
	width:50%;
}
.myclass_link{
	color:grey;
	font-weight: 400!important;
	font-size: 12px;
}
.pagediv .descr{
	font-size:0.9em;
}
.pagediv .totalsumma{
	font-size: 1.5em;
}

.pagediv .labeltext{
	width:80%!important;
}
.ui-checkbox .ui-btn-text{
	font-weight:normal!important;
}

.ui-fullsize .ui-btn-inner, .ui-fullsize .ui-btn-inner {
	font-size: 14px!important;
}
.ui-icon-checkbox-on{
	background-position: -722px -1px!important;
}
.ui-icon-checkbox-off{
	background-position: -762px -1px!important;
} 
.ui-btn-active{
	border: 1px solid #ccc!important;
	font-weight: bold!important;
	color: #222!important;
	text-shadow: 0 1px 0 #fff!important;
	background-image: linear-gradient( #fff,#f1f1f1 )!important;
}

</style>
{if isset($error)}<div style="background-color: #FAE2E3;border: 1px solid #EC9B9B;line-height: 20px;margin: 0 0 10px;padding: 10px 15px;">{$error}</div>{/if}
{if isset($nbProducts) && $nbProducts <= 0} <p class="warning">{l s='Your shopping cart is empty.'}</p>
{else}
					 {if $country->iso_code == 'NL' && $payment_type == 'account'}
  <img src="./img/warning.jpg" style="width:100%" alt="{l s='Warning' mod='billmatepartpayment'}"/>
  {/if}
  <h3>{l s='Billmate Partpayment' mod='billmatepartpayment'}</h3>
  <form action="javascript://" method="post" class="billmate">
	<div class="pagediv">
	<p>
      <img src="{$smarty.const._MODULE_DIR_}billmatepartpayment/bm_delbetalning_m.png" alt="{l s='billmatepartpayment' mod='billmatepartpayment'}" style="float:left; margin: 0px 10px 5px 0px;" />
      <br/>
    </p>
	<div>
		<div class="totalsumma">{l s='TOTAL' mod='billmatepartpayment'}: <span id="amount" class="price">{displayPrice price=$total_fee}</span></div>
	</div>	
    <select name="paymentAccount">
      {foreach from=$accountPrice item=val key=k}
	  <option value="{$k}">{$val.month}  månaders delbetalning - {displayPrice price=$val.price} per månad</option>
      {/foreach}
    </select>
    <br />
    <p>
      <label for="billmate_pno">{l s='Personal Number:' mod='billmatepartpayment'}</label>
      <input type="text" name="billmate_pno" id="billmate_pno" value="" required /> 
      <br /><br/>
	<p>
		<label>
		<input type="checkbox" checked="checked" value="" id="confirm_my_age" name="confirm_my_age" required />
			{$customer_email}
		</label>
	</p>
    </p>
    <p class="cart_navigation" style="display:block!important">
      <input type="button" name="submit" id="billmate_submit" value="{l s='I confirm my order' mod='billmatepartpayment'}" class="exclusive_large hideOnSubmit" />
    </p>
	</div>
	<div class="footerrow">
		<span style"float:left">
			<a href="{$link->getPageLink('order.php', true)}?step=3" class="ui-link myclass_link" style="float:left">{l s='Cancel payment' mod='billmatepartpayment'}</a>
		</span>
		<span style="float:right">
			<a id="terms-delbetalning" style="cursor:pointer!important" class="ui-link myclass_link">{l s='Conditions of payment' mod='billmatepartpayment'}</a>
		</span>
	</div>
  </form>
  <script type="text/javascript">
  
    $(document).ready(function()
    {
      $('#billmate_link_terms_condition').attr('href', 'Javascript:void(0)');
      $('#billmate_link_terms_condition').click(function(){
	  $("#billmate_terms_condition").show();
      });

      $('#billmate_link_germany').attr('href', 'Javascript:void(0)');
      $('#billmate_link_germany').click(function(){
	  $("#billmate_consent_de").show();
      });
  });
function closeIframe(id)
{
    $('#'+id).hide();
}
</script>
{/if}
<link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}billmateinvoice/style.css" />
<script src="{$smarty.const._MODULE_DIR_}billmateinvoice/js/billmatepopup.js"></script>

<script type="text/javascript">
var success = "{$ajaxurl.this_path_ssl}payment.php?type={$payment_type}";
var ajaxurl = "{$ajaxurl.this_path_ssl}";

{if $opc|default:FALSE}
var carrierurl = "{$link->getPageLink("order-opc", true)}";
{else}
var carrierurl = "{$link->getPageLink("order", true)}";
{/if} 


var eid = "{$eid}";
var emptypersonerror = "{l s='PNO/SSN missing' mod='billmatepartpayment'}";
var checkbox_required = "{l s='Please check the checkbox for confirm this e-mail address is correct and can be used for invoicing.' mod='billmatepartpayment'}";
var loadingWindowTitle = '{l s='Processing....' mod='billmatepartpayment'}';
var windowtitlebillmate= "{l s='Pay by invoice can be made only to the address listed in the National Register. Would you like to make the purchase with address:' mod='billmatepartpayment'}";

   {literal}
    $.getScript("https://billmate.se/billmate/base.js", function(){
		    $("#terms-delbetalning").Terms("villkor_delbetalning",{eid: eid,effectiverate:34});
    });

    function getData( param ){
		modalWin.ShowMessage('',40,250,loadingWindowTitle);
        $('div.error').remove();
        jQuery.post( ajaxurl+param, jQuery('.billmate').serializeArray(), function(json){
            eval('var response = '+ json );
            if( response.success ){
            	if( typeof response.redirect != 'undefined' ){
            		window.location.href= response.redirect;
            	} else {
					if( typeof response.action != 'undefined' ) {
						$.post(response.carrierurl,response.action, function(){
							getData( '&geturl=yes' );
						});
					} else {
						getData( '&geturl=yes' );
					}
                }
            } else {
				if( typeof response.popup != 'undefined' && response.popup){
					modalWin.ShowMessage(response.content,470,250,windowtitlebillmate);
					$( "input[type='button']" ).button();
				}else{
					modalWin.HideModalPopUp();
					$('<div class="error">'+response.content+'</div>').insertAfter($('.breadcrumb').first());
				}
            }
        });
    }
    jQuery(document).ready(function(){
        jQuery('#billmate_submit').click(function(){
			$(this).parent().attr('class','ui-btn ui-shadow ui-btn-corner-all ui-btn-up-c');
            if( $.trim( $('#billmate_pno').val()) == '' ){
                alert(emptypersonerror);
                return;
            }
			if(document.getElementById('confirm_my_age').checked ){
				getData( '' );
			}else{
				alert(checkbox_required);
			}
        });
		$(document).ajaxStart(function() { jQuery('#billmate_submit').hide(); }).ajaxStop(function() { jQuery('#billmate_submit').show(); }); 
    });
    {/literal}
</script>
</div>