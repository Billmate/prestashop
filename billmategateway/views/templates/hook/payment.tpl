{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}
<style>
    {if $template == 'new'}
    div.payment_module {
        border: 1px solid #d6d4d4;
        background: #fbfbfb;
    }
    div.payment_module a {
        display: block;
        /*border: 1px solid #d6d4d4;*/
        -moz-border-radius: 4px;
        -webkit-border-radius: 4px;
        border-radius: 4px;
        font-size: 17px;
        line-height: 23px;
        color: #333;
        font-weight: bold;
        padding: 33px 40px 34px 99px;
        letter-spacing: -1px;
        position: relative;
    }
    {else}
    div.payment_module {
        margin: 0;
    }
    div.payment_module a {
        display:block;
    }
    {/if}
</style>
{foreach $methods as $method}
    <div class="payment_module">
    <style>
        div.payment_module a.{$method.type} {
            background: url("{$smarty.const._MODULE_DIR_}{$method.icon}") 15px 15px no-repeat #fbfbfb;
            padding-left: 180px;
        }
        div.payment_module a.{$method.type}:after{
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
        #terms,#terms-partpay{
            cursor: pointer!important;
            font-size: inherit;
            display: inherit;
            border: none;
            padding: inherit;
            text-decoration: underline;
        }
    </style>
    {if $method.type != 'billmateinvoice' && $method.type != 'billmatepartpay'}
        <a {if $template == 'new'} class="{$method.type}"{/if} href="{$method.controller}" onclick="getPayment('{$method.method}'); return false;">{if $template == 'legacy'}<img src="{$smarty.const._MODULE_DIR_}{$method.icon}"/>{/if}{$method.name|escape:'html'}</a>
    {else}
        <a {if $template == 'new'} class="{$method.type}"{/if} href="{$method.controller|escape:'url'}" id="{$method.type|escape:'html'}">{if $template == 'legacy'}<img src="{$smarty.const._MODULE_DIR_}{$method.icon}"/>{/if}{$method.name|escape:'html'} {if $method.type == 'billmatepartpay'} - {l s='Pay from' mod='billmategateway'} {convertPrice|regex_replace:'/[.,]0+/':'' price=$method.monthly_cost.monthlycost} {elseif $method.invoiceFee.fee > 0} - {convertPrice|regex_replace:'/[.,]0+/':'' price=$method.invoiceFee.fee_incl_tax}  {l s='invoice fee is added to the order sum' mod='billmategateway'}{/if}
        </a>
        <div style="display:none;" id="{$method.type}-fields">
            <form action="javascript://" class="{$method.type|escape:'html'}">
                <div style="padding:10px;" id="error_{$method.type}"></div>
                {if $method.type == 'billmatepartpay'}
                    <div class="accountcontainer">
                        <label style="display:block; padding:10px; {if $template == 'legacy'}clear:both;{/if}">{l s='Choose the payment option that suites you best:' mod='billmategateway'}</label>
                        <select name="paymentAccount" style="margin-left:10px;">
                            {foreach $method.pClasses as $pclass}
                                <option value="{$pclass.paymentplanid}">{$pclass.description} {displayPrice price=$pclass.monthlycost}
                                    / {l s='month' mod='billmategateway'}</option>
                            {/foreach}
                        </select>
                    </div>
                {/if}
                <div class="pno_container" style="padding:10px">
                    <label for="pno_{$method.type|escape:'html'}" style="display:block; {if $template == 'legacy'}clear:both;{/if}">{l s='Personal / Corporate number:' mod='billmategateway'}</label>
                    <input id="pno_{$method.type|escape:'html'}" name="pno_{$method.type|escape:'html'}" type="text"/>
                </div>
                <div class="agreements" style="padding:10px">
                    <input type="checkbox" checked="checked" id="agree_with_terms_{$method.type|escape:'html'}"
                           name="agree_with_terms_{$method.type|escape:'html'}"/>
                    <label for="terms_{$method.type|escape:'html'}">{$method.agreements|escape:'quotes'}</label>
                </div>
                <div style="padding:10px"><input type="button" class="exclusive button" id="{$method.type|escape:'html'}Submit" value="{l s='Proceed' mod='billmategateway'}"/></div>
            </form>
        </div>
    {/if}
    </div>
{/foreach}
<script type="text/javascript" src="{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js"></script>
<script type="text/javascript">

    var version = "{$ps_version|escape:'html'}"
    var PARTPAYMENT_EID = "{$eid}";
    var ajaxurl = "{$link->getModuleLink('billmategateway', 'billmateapi', ['ajax'=> 0], true)}";
    function getPayment(method){
        $.ajax({
                url: ajaxurl,
                data: 'method='+method,
                success: function(response){
                    var result = JSON.parse(response);
                    if(result.success){
                        location.href = result.redirect;
                    } else {
                        alert(result.content);
                    }
                }
                })
    }
    var emptypersonerror = "{l s='PNO/SSN missing' mod='billmategateway'}";
    var checkbox_required = "{l s='Please check the checkbox for confirm this e-mail address is correct and can be used for invoicing.' mod='billmategateway'}";
    var carrierurl;
    {if $opc|default:false }
    carrierurl = "{$link->getPageLink("order-opc", true)}";
    {else}
    carrierurl = "{$link->getPageLink("order", true)}";
    {/if}
    var loadingWindowTitle = '{l s='Processing....' mod='billmategateway'}';
    var windowtitlebillmate = "{l s='Pay by invoice can be made only to the address listed in the National Register. Would you make the purchase with address:' mod='billmategateway'}";
    jQuery(document.body).on('click', '#billmate_button', function () {
        var method = $(this).data('method');
        var form = $('.billmate' + method).serializeArray();
        modalWin.HideModalPopUp();
        getData('&geturl=yes', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, method);
    });
    $('#billmateinvoice').click(function (e) {
        $('#billmatepartpay-fields').hide();
        $('#billmateinvoice-fields').show();
        e.preventDefault();
    })
    $('#billmatepartpay').click(function (e) {
        $('#billmateinvoice-fields').hide();
        $('#billmatepartpay-fields').show();
        e.preventDefault();
    })
    $('#billmateinvoiceSubmit').click(function (e) {
        var form = $('.billmateinvoice').serializeArray();

        if ($.trim($('#pno_billmateinvoice').val()) == '') {
            alert(emptypersonerror);
            return;
        }
        if ($('#agree_with_terms_billmateinvoice').prop('checked') == true) {
            getData('', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'invoice');
        } else {
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();
    })

    $('#billmatepartpaySubmit').click(function (e) {
        var form = $('.billmatepartpay').serializeArray();

        if ($.trim($('#pno_billmatepartpay').val()) == '') {
            alert(emptypersonerror);
            return;
        }
        if ($('#agree_with_terms_billmatepartpay').prop('checked') == true) {
            getData('', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'partpay');
        } else {
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();

    })
</script>
