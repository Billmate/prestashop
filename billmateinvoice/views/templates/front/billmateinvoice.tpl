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
<style>
p.payment_module a.billmateinvoice {
	background: url("{$smarty.const._MODULE_DIR_}billmateinvoice/bm_faktura_l.png") 15px 15px no-repeat #fbfbfb;
	padding-left: 180px;
}
p.payment_module a.billmateinvoice:after{
	display: block;
	content: "\f054";
	position: absolute;
	right: 15px;
	margin-top: -11px;
	top: 50%;
	font-family: "FontAwesome";
	font-size: 25px;
	height: 22px;
	width: 14px;
	color: #777;
}
</style>
<p class="payment_module">
	<a href="{$moduleurl}" title="{l s='Pay by invoice' mod='billmateinvoice'}" class="billmateinvoice">
		{l s='Pay by invoice' mod='billmateinvoice'} {if $invoiceFee != 0} <span>({displayPrice price=$invoiceFee} {$invoicefeestring} )</span> {/if}
		<br style="clear:both;" />
	</a>
</p>
