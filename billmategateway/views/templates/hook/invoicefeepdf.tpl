<table>
<tr style="line-height:5px;">
    <td style="text-align: right; font-weight: bold">{l s='Invoicefee tax incl.' pdf='true' mod='billmategateway'}</td>
    <td style="width: 17%; text-align: right;">
            {displayPrice currency=$order->id_currency price=$invoiceFeeIncl}

    </td>
</tr><tr style="line-height: 5px;">
    <td style="text-align: right; font-weight: bold">{l s='Invoicefee tax ' pdf='true' mod='billmategateway'}</td>
    <td style="width: 17%; text-align: right;">
        {displayPrice currency=$order->id_currency price=$invoiceFeeTax}

    </td>
</tr>
</table>