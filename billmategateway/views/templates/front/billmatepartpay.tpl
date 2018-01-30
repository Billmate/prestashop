<style>
    #spanMessage .billmate-loader {
        background: url("{$smarty.const._MODULE_DIR_}billmategateway/views/img/ajax-loader.gif") 15px 15px no-repeat #fbfbfb;
        z-index: 10000;
        height: 100px;
        width: 100px;
        margin-left: 45%
    }

    @media screen and (max-width: 768px) {
        #facebox img {
            width: 90% !important;
        }

        #facebox {
            width: 80% !important;
            right: 10% !important;
            left: 10% !important;
        }
    }

    #divFrameParent * {
        text-align: center !important;
        font-size: 1em;
        font-family: tahoma !important;
    }

    #divFrameParent .checkout-heading {
        color: #000000 !important;
        font-weight: bold !important;
        font-size: 13px !important;
        margin-bottom: 15px !important;
        padding: 8px !important;
    }

    #divFrameParent .button:hover {
        background: #0B6187 !important;
    }

    #divFrameParent .button {
        background-color: #1DA9E7 !important;
        background: #1DA9E7 !important;
        border: 0 none !important;
        border-radius: 8px !important;
        box-shadow: 2px 2px 2px 1px #EAEAEA !important;
        color: #FFFFFF !important;
        cursor: pointer !important;
        font-family: arial !important;
        font-size: 14px !important;
        font-weight: bold !important;
        padding: 3px 17px !important;
    }

    div.payment_module {
        border: 1px solid #d6d4d4;
        background: #fbfbfb;
        margin-bottom: 10px;
        -moz-border-radius: 4px;
        -webkit-border-radius: 4px;
        border-radius: 4px;
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
        padding: 33px 40px 34px 170px;
        letter-spacing: -1px;
        position: relative;

    }

    div.payment_module a.{$type} {
        background: url("{$smarty.const._MODULE_DIR_}{$icon}") 15px 15px no-repeat #fbfbfb;

    }

    div.payment_module a.{$type}:after {
        display: block;
        /* content: "\f054"; */
        position: absolute;
        right: 15px;
        margin-top: -11px;
        top: 50%;
        /* font-family: "FontAwesome"; */
        font-size: 25px;
        height: 22px;
        width: 14px;
        color: #777;
    }

    div.payment_module a.{$type}:hover,
    div.payment_module a.{$type}:visited,
    div.payment_module a.{$type}:active {
        text-decoration: none;
    }

    div.payment_module .error {
        clear: both;
    }
    img[src*="billmate"]{
        float:left;
        clear:both;
    }
    .payment-option > label > span {
        float: left;
    }

    #terms, #terms-partpay {
        cursor: pointer !important;
        font-size: inherit;
        display: inherit;
        border: none;
        padding: inherit;
        text-decoration: underline;
    }
</style>
<span class="{$type}"
      id="{$type}">{$name}  {l s='from' mod='billmategateway'} {$monthly_cost.monthlycost} {l s='/ month' mod='billmategateway'}
</span>
<div style="" id="{$type}-fields" class="payment-form">
    <form action="javascript://" class="{$type|escape:'html'}">

        <div style="" id="error_{$type}"></div>

        <div class="form-group">
            <select name="paymentAccount" class="form-control" style="width: 300px;">
                {foreach $pClasses as $pclass}
                    <option value="{$pclass.paymentplanid}">{$pclass.description} {$pclass.monthlycost}
                        / {l s='month' mod='billmategateway'}</option>
                {/foreach}
            </select>
        </div>
        
        <div class="form-group">
            <label for="pno_{$type|escape:'html'}">{l s='Social Security Number / Corporate Registration Number' mod='billmategateway'}</label>
            <div class="input-group">    
                <input style="width: 200px;" class="form-control" id="pno_{$type|escape:'html'}" name="pno_{$type|escape:'html'}" type="text" placeholder="xxxxxx-xxxx" />
            </div>
        </div>
               
        <div class="form-group">
            <div class="input-group">
                <input class="form-check-input" type="checkbox" checked="checked" id="agree_with_terms_{$type|escape:'html'}"
                       name="agree_with_terms_{$type|escape:'html'}"/>
                       {$agreements nofilter}
            </div>
        </div>
        
        <div style="padding:10px; padding-top:0px;">
            <button type="submit" style="margin-bottom:10px;" class="btn btn-default button button-medium pull-right"
                    id="{$type|escape:'html'}Submit" value=""><span>{l s='Proceed' mod='billmategateway'}</span>
            </button>
        </div>
        <div class="form-group billmatepartpay-submit-info-wrapper"></div>
    </form>
</div>
<script type="text/javascript" src="{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js"></script>
<script type="text/javascript">
    function billmatePartpay() {
        var ajaxurl = "{$link->getModuleLink('billmategateway', 'billmateapi', ['ajax'=> 0], true)}";
        ajaxurl = ajaxurl.replace(/&amp;/g, '&');
        window.checkoutButton = $('#payment-confirmation button[type="submut"]').onclick;
        function getPayment(method) {
            if (typeof submitAccount == 'function')
                submitAccount();
            $.ajax({
                url: ajaxurl,
                data: 'method=' + method,
                success: function (response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        location.href = result.redirect;
                    } else {
                        alert(result.content);
                    }
                }
            })
            return false;
        }
        var PARTPAYMENT_EID = "{$eid}";
        window.PARTPAYMENT_EID = PARTPAYMENT_EID;
        if (!billmatepopupLoaded) {
            var script = document.createElement('script');
            script.setAttribute('src', '{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js');
            script.setAttribute('type', 'text/javascript');
            document.getElementsByTagName('head')[0].appendChild(script);
        }
        function addTerms() {
            jQuery(document).Terms('villkor', {ldelim}invoicefee: 0{rdelim}, '#terms');
            jQuery(document).Terms('villkor_delbetalning', {ldelim}eid: PARTPAYMENT_EID, effectiverate: 34{rdelim}, '#terms-partpay');
        }

        if (!jQuery.fn.Terms) {
            jQuery.getScript('https://billmate.se/billmate/base_jquery.js', function () {ldelim}addTerms(){rdelim});
        }
        var version = "1.7";


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
        jQuery(document.body).on('click', '#billmate_button', function (e) {
            if($('#billmatepartpay').is(':visible')) {
                e.preventDefault();

                var method = 'partpay';
                if ($('form.billmate' + method).length > 1)
                    var form = $('form.realbillmate' + method).serializeArray();
                else
                    var form = $('form.billmate' + method).serializeArray();
                modalWin.HideModalPopUp();

                if (!billmateprocessing) {
                    getData('&geturl=yes', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, method);

                }
                return false;
            }
        });
        if ($('#pno').length) {
            $('.pno_container').hide();
            if ($('#pno_billmatepartpay')) {
                $('#pno').on('change', function (e) {
                    $('#pno_billmatepartpay').val(e.target.value);
                });
            }
            if ($('#pno_billmateinvoice')) {
                $('#pno').on('change', function (e) {
                    $('#pno_billmateinvoice').val(e.target.value);
                });
            }
            if ($('#pno_billmateinvoiceservice')) {
                $('#pno').on('change', function (e) {
                    $('#pno_billmateinvoiceservice').val(e.target.value);
                });
            }

        }
        $('#billmatecardpay').click(function (e) {
            e.preventDefault();
            getPayment('cardpay');
            return false;
        });
        $('#billmatebankpay').click(function (e) {
            e.preventDefault();
            getPayment('bankpay');
            return false;
        });
        $('#billmateinvoice').click(function (e) {
            $('a#billmateinvoice').css('padding-bottom', '10px');
            $('a#billmatepartpay').css('padding-bottom', '34px');
            $('#billmatepartpay-fields').hide();
            $('#billmateinvoiceservice-fields').hide();
            $('#billmateinvoice-fields').show();
            if ($('#pno').length > 0) {
                $('#pno_billmateinvoice').val($('#pno').val());
                $('#billmateinvoice-fields .pno_container').hide();
            }
            e.preventDefault();
        })

        $('#billmateinvoiceservice').click(function (e) {
            $('a#billmateinvoiceservice').css('padding-bottom', '10px');
            $('a#billmatepartpay').css('padding-bottom', '34px');
            $('#billmatepartpay-fields').hide();
            $('#billmateinvoice-fields').hide()
            $('#billmateinvoiceservice-fields').show();
            if ($('#pno').length > 0) {
                $('#pno_billmateinvoiceservice').val($('#pno').val());
                $('#billmateinvoiceservice-fields .pno_container').hide();

            }
            e.preventDefault();
        })
        $('#billmatepartpay').click(function (e) {
            $('a#billmateinvoice').css('padding-bottom', '34px');
            $('a#billmatepartpay').css('padding-bottom', '10px');
            $('#billmateinvoice-fields').hide();
            $('#billmateinvoiceservice-fields').hide();
            $('#billmatepartpay-fields').show();
            if ($('#pno').length > 0) {
                $('#pno_billmatepartpay').val($('#pno').val());
                $('#billmatepartpay-fields .pno_container').hide();
            }
            e.preventDefault();
        })

        $('#billmatepartpaySubmit').click(function (e) {

            if (    $('.billmatepartpay-submit-info-wrapper').is(':visible')
                    && $('.js-alert-payment-conditions').length > 0
                    && $(document).find('input[name="conditions_to_approve[terms-and-conditions]"]').length > 0
            ) {
                if ($(document).find('input[name="conditions_to_approve[terms-and-conditions]"]').is(':checked') == false) {
                    /** Customer need to approve store terms */
                    var orderSubmitPaymentConditionElement = $('.js-alert-payment-conditions').clone();
                    $('.billmatepartpay-submit-info-wrapper').html(orderSubmitPaymentConditionElement.html());
                    $('.billmatepartpay-submit-info-wrapper').attr('class', 'billmatepartpay-submit-info-wrapper alert alert-danger mt-2');
                    return false;
                }
            }

            if ($('#pno').length > 0) {
                $("#pno_billmatepartpay").val($('#pno').val());
            }
            if ($('form.billmatepartpay').length > 1) {
                var form = $('form.realbillmatepartpay').serializeArray();
            } else {
                var form = $('form.billmatepartpay').serializeArray();
            }
            if ($.trim($('#pno_billmatepartpay').val()) == '') {
                alert(emptypersonerror);
                if ($checkoutButton)
                    $checkoutButton.disabled = false;
                return;
            }
            if ($('#agree_with_terms_billmatepartpay').prop('checked') == true) {
                if (!billmateprocessing)
                    getData('', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'partpay');
            } else {
                alert($('<textarea/>').html(checkbox_required).text());
            }
            e.preventDefault();
        })
        if ($('input[name="id_payment_method"]').length) {
            $(document).on('click', 'input[name="id_payment_method"]', function (element) {

                $('.payment-form').hide();
                var value = element.target.value;

                if ($('#payment_' + value).parents('.item,.alternate_item').hasClass('fields')) {


                    $('#payment_' + value).parents('.item,.alternate_item').children('.payment_description').children('.payment-form').show();
                    var method = $('#payment_' + value).parents('.item,.alternate_item').children('.payment_description').children('.payment-form').attr('id');
                    var methodName = method.replace('-fields', '');
                    if ($('#pno').length > 0) {
                        $('#pno_' + methodName).val($('#pno').val());
                    }
                    $('.confirm_button')[$('.confirm_button').length - 1].onclick = function (e) {

                        submitAccount($('#' + methodName + 'Submit'));


                        e.preventDefault()
                    }

                } else if ($('#' + value).parent('.payment_module').children('.payment-form')) {
                    var el = $('#' + value).parent('.payment_module').children('.payment-form');
                    var method = el.attr('id');

                    if (typeof method != 'undefined') {
                        var methodName = method.replace('-fields', '');

                        if (!$('#payment_' + value).parents('.item,.alternate_item').hasClass('fields'))
                            $('#payment_' + value).parents('.item,.alternate_item').addClass('fields');

                        $('#' + value).parent('.payment_module').children('.payment-form').appendTo($('.cssback.' + methodName).parents('.item,.alternate_item').children('.payment_description'));
                        $('.cssback.' + methodName).parents('.item,.alternate_item').children('.payment_description').children('.payment-form').children('.' + methodName).addClass('real' + methodName);
                        $('#' + value).parent('.payment_module').children('.payment-form').remove(methodName);
                        $('#' + method).show();
                        if ($('#pno').length > 0) {
                            $('#pno_' + methodName).val($('#pno').val());
                        }
                        $('#' + methodName + 'Submit').hide();
                        $checkoutbtn = $('.confirm_button')[$('.confirm_button').length - 1].onclick;
                        $('.confirm_button')[$('.confirm_button').length - 1].onclick = function (e) {
                            submitAccount($('#' + methodName + 'Submit'));

                            e.preventDefault();
                        }
                    } else {
                        if ($checkoutbtn != null) {
                            $('.confirm_button')[$('.confirm_button').length - 1].onclick = $checkoutbtn
                        }
                    }
                }

            });
        }
    }

    if (document.readyState != 'loading') billmatePartpay();
    /* modern browsers */
    else if (document.addEventListener) document.addEventListener('DOMContentLoaded', billmatePartpay);
    /* IE <= 8 */
    else document.attachEvent('onreadystatechange', function () {
            if (document.readyState == 'complete') billmatePartpay();
        });
</script>
