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
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

{capture name=path}{l s='Shipping' mod='billmateinvoice'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<div id="order_area"  style="margin: 0% 3%;">
<h2>{l s='Order summation' mod='billmateinvoice'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
<style type="text/css">
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
<h3>{l s='Billmate Invoice Payment' mod='billmateinvoice'}</h3>
<form action="javascript://" method="post" class="billmate">
	<div class="pagediv">
		<input type="hidden" name="confirm" value="1" />
		<p>
			<img src="{$smarty.const._MODULE_DIR_}billmateinvoice/bm_faktura_m.png" alt="{l s='Billmate Invoice Payment' mod='billmateinvoice'}" style="float:left; margin: 0px 10px 5px 0px;" />
		</p>
		<div>
			<div class="totalsumma">{l s='TOTAL' mod='billmateinvoice'}: <span id="amount_{$currencies.0.id_currency}" class="price">{convertPrice price=$total}.</span></div>
			<span class="descr">{l s='Including Invoice fee' mod='billmateinvoice'} {displayPrice price=$fee} )</span>
		</div>
		<p class="clear"></p>
		<p class="">
			<label for="pno">{l s='Personal Number' mod='billmateinvoice'}</label>
			<input type="text" class="pno_input" value="" id="pno" name="pno" required  />
		</p>
		<p>

			<label>
				<input type="checkbox" checked="checked" value="" id="confirm_my_age" name="confirm_my_age" required style="margin:0px" />
				{l s='My email %1$s is accurate and can be used for invoicing.' sprintf=[$customer_email] mod='billmateinvoice'}
			</label>
		</p>
		<p class="cart_navigation" style="display:block!important">
			<input type="button" name="submit" value="{l s='I confirm my order' mod='billmateinvoice'}" class="exclusive_large" id="billmate_submit"/>
		</p>
	</div>
	<div class="footerrow">
		<span>
			<a href="{$previouslink}" class="ui-link myclass_link" style=float:left;"">{l s='Cancel payment' mod='billmateinvoice'}</a>
		</span>
		<span style="float:right">
			<a id="terms" style="cursor:pointer!important" class="ui-link myclass_link">{l s='Terms of invoice' mod='billmateinvoice'}</a><script type="text/javascript">$.getScript("https://billmate.se/billmate/base.js", function(){ldelim}
				$("#terms").Terms("villkor",{ldelim}invoicefee: {$fee}{rdelim});
			{rdelim});</script>
		</span>
	</div>
</form>
<link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}billmateinvoice/style.css" />
<script src="{$smarty.const._MODULE_DIR_}billmateinvoice/js/billmatepopup.js"></script>

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

    {literal}
	$(function() {
		$(document).ajaxStart(function() { jQuery('#billmate_submit').hide(); }).ajaxStop(function() { jQuery('#billmate_submit').show(); }); 
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
						$.post(carrierurl,response.action, function(){
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
					$('<div class="error">'+response.content+'</div>').insertBefore($('#order_area').first());
				}
            }
        });
		
		
			
    }
    jQuery(document).ready(function(){
        jQuery('#billmate_submit').click(function(){
			$(this).parent().attr('class','ui-btn ui-shadow ui-btn-corner-all ui-btn-up-c');
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
