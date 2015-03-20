{foreach $methods as $method}
    <p class="payment_module">
        {if $method.type != 'billmateinvoice' && $method.type != 'billmatepartpay'}
            <a href="{$method.controller}"><img src="{$smarty.const._MODULE_DIR_}{$method.icon}"/>{$method.name}</a>
        {else}
            <a href="{$method.controller}" id="{$method.type}"><img src="{$smarty.const._MODULE_DIR_}{$method.icon}"/>{$method.name} {if $method.type == 'billmatepartpay'} - {l s='Pay from'} {displayPrice price=$method.monthly_cost.monthlycost} {else} - {displayPrice price=$method.invoiceFee.fee_incl_tax}  {l s='invoice fee is added to the order sum' mod='billmategateway'}{/if}</a>
            <div style="display:none;" id="{$method.type}-fields">
                <form action="javascript://" class="{$method.type}">
                    {if $method.type == 'billmatepartpay'}
                        <select name="paymentAccount">
                            {foreach $method.pClasses as $pclass}
                                <option value="{$pclass.paymentplanid}">{$pclass.description} {displayPrice price=$pclass.monthlycost} / {l s='month' mod='billmategateway'}</option>
                            {/foreach}
                        </select>
                    {/if}
                    <div class="pno_container">
                        <label for="pno_{$method.type}">{l s='Personal / Corporate number:' mod='billmategateway'}</label>
                        <input id="pno_{$method.type}" name="pno_{$method.type}" type="text"/>
                    </div>
                    <div class="agreements">
                        <input type="checkbox" checked="checked" id="agree_with_terms_{$method.type}" name="agree_with_terms_{$method.type}"/>
                        <label for="agree_with_terms_{$method.type}">{$method.agreements}</label>
                        <button id="{$method.type}Submit">{l s='Proceed' mod='billmategateway'}</button>

                    </div>
                </form>
            </div>
        {/if}
    </p>
{/foreach}
<script type="text/javascript" src="{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js"></script>
<script type="text/javascript">
    var version = "{$ps_version}"
    var ajaxurl = "{$link->getModuleLink('billmategateway', 'billmateapi', ['ajax'=> 0], true)}";
    var emptypersonerror = "{l s='PNO/SSN missing' mod='billmategateway'}";
    var checkbox_required = "{l s='Please check the checkbox for confirm this e-mail address is correct and can be used for invoicing.' mod='billmategateway'}";
    var carrierurl;
    {if $opc|default:false }
    carrierurl = "{$link->getPageLink("order-opc", true)}";
    {else}
    carrierurl = "{$link->getPageLink("order", true)}";
    {/if}
    var loadingWindowTitle = '{l s='Processing....' mod='billmategateway'}';
    var windowtitlebillmate= "{l s='Pay by invoice can be made only to the address listed in the National Register. Would you make the purchase with address:' mod='billmategateway'}";
    jQuery(document.body).on('click','#billmate_button',function() {
        var method = $(this).data('method');
        var form = $('.billmate'+method).serializeArray();
        modalWin.HideModalPopUp();
        getData('&geturl=yes', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate,method);
    });
    $('#billmateinvoice').click(function(e){
        $('#billmatepartpay-fields').hide();
        $('#billmateinvoice-fields').show();
        e.preventDefault();
    })
    $('#billmatepartpay').click(function(e){
        $('#billmateinvoice-fields').hide();
        $('#billmatepartpay-fields').show();
        e.preventDefault();
    })
    $('#billmateinvoiceSubmit').click(function(e){
        var form = $('.billmateinvoice').serializeArray();

        if($.trim( $('#pno_billmateinvoice').val()) == '' ){
            alert(emptypersonerror);
            return;
        }
        if($('#agree_with_terms_billmateinvoice').prop('checked') == true){
            getData('', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate,'invoice');
        }else{
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();
    })

    $('#billmatepartpaySubmit').click(function(e){
        var form = $('.billmatepartpay').serializeArray();

        if($.trim( $('#pno_billmatepartpay').val()) == '' ){
            alert(emptypersonerror);
            return;
        }
        if($('#agree_with_terms_billmatepartpay').prop('checked') == true){
            getData('', form,  version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate,'partpay');
        }else{
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();

    })
</script>
