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

<p class="payment_module">
	<a href="{$moduleurl}" title="{l s='Pay by invoice' mod='billmateinvoice'}">
		<img src="{$smarty.const._MODULE_DIR_}billmateinvoice/bm_faktura_l.png" alt="{l s='Pay by invoice' mod='billmateinvoice'}" style="float:left;" />
		{l s='Pay by invoice' mod='billmateinvoice'} {if $invoiceFee != 0}<br/> {l s="Handling fee is added by " mod="billmateinvoice"} {displayPrice price=$invoiceFee}{/if}
		<br style="clear:both;" />
	</a>
</p>
