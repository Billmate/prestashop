
<script type="text/javascript">
$(document).ready(function(){
$('.container-command-top-spacing fieldset:first a, .container-command-top-spacing fieldset:first form').remove();
$('.container-command-top-spacing fieldset a:first, .container-command-top-spacing fieldset form:first').remove();

$('#id_order_state').prepend("<option>{l s='Choose a new order status' mod='billmatecardpay'}</option>");
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
  <legend><img src="../modules/billmatecardpay/logo.gif" />{l s='Payment information from Billmate' mod='billmatecardpay'}</legend>
{if (isset($denied) && isset($wasPending)) || isset($pending)}
<p>{l s='This order was made using Billmate’s payment methods. This order is marked as pending and is under manual review by Billmate’s fraud prevention team.' mod='billmatecardpay'}</p>
{else}
<p>{l s='This order was made using Billmate’s payment methods.' mod='billmatecardpay'}</p>
{/if}
{if !isset($pending) && !isset($denied) && !isset($cardpayLink)}
<p>{l s='An Invoice will be created at Billmate once you change the order status to "Shipped". You can not revert this change, the consumer will receive this cardpay. Dependent on your settings, you can print the cardpay and include it with the parcel, or Billmate will send it via email' mod='billmatecardpay'}</p>
{/if}
{if !isset($denied) && !isset($cardpayLink)}
<p>{l s='Change the status to "Cancel" and the order will also be canceled at Billmate. You can not revert this change, once this is done Billmate will not send an cardpay to the consumer.' mod='billmatecardpay'}</p>
{/if}
  {if isset($error)}
  <p style="color:red">{$error}</p>
  {/if}
  {if isset($message) && !isset($cardpayLink)}
  <p style="color:green">{$message}</p>
  {/if}
  {if isset($cardpayLink)}
  <p style="color:green">{l s='An cardpay has been created at Billmate. Dependent on your settings, Billmate will send the cardpay via email to the consumer.' mod='billmatecardpay'}<br/>
{l s='If this is not the case and you wish to print the cardpay and send it with the parcel, click the link below:' mod='billmatecardpay'}</p>
  Invoice : <a style="color:blue" href="{$cardpayLink}">{l s='Invoice' mod='billmatecardpay'}</a>
  <p>The link to this cardpays is valid for 30 days</p>
  {/if}
</fieldset>
