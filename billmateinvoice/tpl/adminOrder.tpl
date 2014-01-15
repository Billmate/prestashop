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
<script type="text/javascript">
$(document).ready(function(){
$('.container-command-top-spacing fieldset:first a, .container-command-top-spacing fieldset:first form').remove();
$('.container-command-top-spacing fieldset a:first, .container-command-top-spacing fieldset form:first').remove();

$('#id_order_state').prepend("<option>{l s='Choose a new order status' mod='billmateinvoice'}</option>");
$('#id_order_state').children(':first').attr('selected', 'selected');
});

{if isset($shipped_state)}
    $('#id_order_state').children('option').each(function(){
    if ($(this).val() == '{$shipped_state}')
       $(this).remove();
    });
{/if}
</script>
<br/>
<fieldset {if $version == 0} style="width:400px"{/if}>
  <legend><img src="../modules/billmateinvoice/logo.gif" />{l s='Payment information from Billmate' mod='billmateinvoice'}</legend>
{if (isset($denied) && isset($wasPending)) || isset($pending)}
<p>{l s='This order was made using Billmate’s payment methods. This order is marked as pending and is under manual review by Billmate’s fraud prevention team.' mod='billmateinvoice'}</p>
{else}
<p>{l s='This order was made using Billmate’s payment methods.' mod='billmateinvoice'}</p>
{/if}
{if !isset($pending) && !isset($denied) && !isset($invoiceLink)}
<p>{l s='An Invoice will be created at Billmate once you change the order status to "Shipped". You can not revert this change, the consumer will receive this invoice. Dependent on your settings, you can print the invoice and include it with the parcel, or Billmate will send it via email' mod='billmateinvoice'}</p>
{/if}
{if !isset($denied) && !isset($invoiceLink)}
<p>{l s='Change the status to "Cancel" and the order will also be canceled at Billmate. You can not revert this change, once this is done Billmate will not send an invoice to the consumer.' mod='billmateinvoice'}</p>
{/if}
  {if isset($error)}
  <p style="color:red">{$error}</p>
  {/if}
  {if isset($message) && !isset($invoiceLink)}
  <p style="color:green">{$message}</p>
  {/if}
  {if isset($invoiceLink)}
  <p style="color:green">{l s='An invoice has been created at Billmate. Dependent on your settings, Billmate will send the invoice via email to the consumer.' mod='billmateinvoice'}<br/>
{l s='If this is not the case and you wish to print the invoice and send it with the parcel, click the link below:' mod='billmateinvoice'}</p>
  Invoice : <a style="color:blue" href="{$invoiceLink}">{l s='Invoice' mod='billmateinvoice'}</a>
  <p>The link to this invoices is valid for 30 days</p>
  {/if}
</fieldset>
