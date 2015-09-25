{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}
<table class="left" style="margin-right: 5px; border:1px solid black;" width="100%">
    <tr style="line-height:5px;">
        <td>&nbsp;</td>
        <td class="gray" style="text-align: right; font-weight: bold">{l s='Invoicefee tax incl.' pdf='true' mod='invoice'}</td>
        <td class="white" style="width: 17%; text-align: right; margin-right:5px">
                {displayPrice currency=$order->id_currency price=$invoiceFeeIncl}

        </td>
    </tr>
    <tr style="line-height: 5px;">
        <td>&nbsp;</td>
        <td class="gray" style="text-align: right; font-weight: bold">{l s='Invoicefee tax ' pdf='true' mod='invoice'}</td>
        <td class="white" style="width: 17%; text-align: right; margin-right:5px">
            {displayPrice currency=$order->id_currency price=$invoiceFeeTax}

        </td>
    </tr>
</table>