<div class="row">
    <div class="col-xs-12">
        <div class="payment_module">
            <style>
                div.payment_module a.cardpay {
                    background: url("{$smarty.const._MODULE_DIR_}{$icon}") 15px 15px no-repeat #fbfbfb;

                }
                div.payment_module a.cardpay:after{
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
                div.payment_module a.cardpay:hover,
                div.payment_module a.cardpay:visited,
                div.payment_module a.cardpay:active{
                    text-decoration: none;
                }
                div.payment_module .error{
                    clear:both;
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

            <a  class="cardpay" href="{$controller|escape:'url'}" id="cardpay">{$name|escape:'html'}
            </a>


        </div>
    </div>
</div>

<script type="text/javascript" src="{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js"></script>
<script type="text/javascript">
    function getPayment(method){
        if(typeof submitAccount == 'function')
            submitAccount();
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
        return false;
    }
    if(!billmatepopupLoaded){
        var script = document.createElement('script');
        script.setAttribute('src','{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js');
        script.setAttribute('type','text/javascript');
        document.getElementsByTagName('head')[0].appendChild(script);
    }

    var version = "1.7"
    var PARTPAYMENT_EID = "{$eid}";
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

    var windowtitlebillmate = "{l s='Pay by invoice can be made only to the address listed in the National Register. Would you make the purchase with address:' mod='billmategateway'}";
    jQuery(document.body).on('click', '#billmate_button', function () {
        var method = $(this).data('method');
        if ($('form.billmate'+method).length > 1)
            var form = $('form.realbillmate'+method).serializeArray();
        else
            var form = $('form.billmate' + method).serializeArray();
        modalWin.HideModalPopUp();

        if(!billmateprocessing) {

            getData('&geturl=yes', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, method);
        }
    });
    if($('#pno').length){
        $('.pno_container').hide();
        if($('#pno_billmatepartpay')){
            $('#pno').on('change',function(e){
                $('#pno_billmatepartpay').val(e.target.value);
            });
        }
        if($('#pno_billmateinvoice')){
            $('#pno').on('change',function(e){
                $('#pno_billmateinvoice').val(e.target.value);
            });
        }
        if($('#pno_billmateinvoiceservice')){
            $('#pno').on('change',function(e){
                $('#pno_billmateinvoiceservice').val(e.target.value);
            });
        }

    }
    $('#billmatecardpay').click(function(e) {
        e.preventDefault();
        getPayment('cardpay');
        return false;
    });
    $('#billmatebankpay').click(function(e) {
        e.preventDefault();
        getPayment('bankpay');
        return false;
    });
    $('#billmateinvoice').click(function (e) {
        $('a#billmateinvoice').css('padding-bottom','10px');
        $('a#billmatepartpay').css('padding-bottom','34px');
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
        $('a#billmateinvoiceservice').css('padding-bottom','10px');
        $('a#billmatepartpay').css('padding-bottom','34px');
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
        $('a#billmateinvoice').css('padding-bottom','34px');
        $('a#billmatepartpay').css('padding-bottom','10px');
        $('#billmateinvoice-fields').hide();
        $('#billmateinvoiceservice-fields').hide();
        $('#billmatepartpay-fields').show();
        if ($('#pno').length > 0) {
            $('#pno_billmatepartpay').val($('#pno').val());
            $('#billmatepartpay-fields .pno_container').hide();
        }
        e.preventDefault();
    })
    $('#billmateinvoiceSubmit').click(function (e) {
        if ($('#pno').length > 0) {
            $("#pno_billmateinvoice").val($('#pno').val());
        }
        if($('form.billmateinvoice').length > 1) {
            var form = $('form.realbillmateinvoice').serializeArray();
        } else {
            var form = $('form.billmateinvoice').serializeArray();
        }
        if ($.trim($('#pno_billmateinvoice').val()) == '') {
            alert(emptypersonerror);
            if($checkoutButton)
                $checkoutButton.disabled = false;
            return;
        }
        if ($('#agree_with_terms_billmateinvoice').prop('checked') == true) {
            var data = '';
            if($('#invoice_address').prop('checked') == true)
                data = '&invoice_address=true';
            if(!billmateprocessing)
                getData(data, form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'invoice');
        } else {
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();
    })
    $('#billmateinvoiceserviceSubmit').click(function (e) {
        if($('form.billmateinvoiceservice').length > 1) {
            var form = $('form.realbillmateinvoiceservice').serializeArray();
        } else {
            var form = $('form.billmateinvoiceservice').serializeArray();
        }
        if ($.trim($('#pno_billmateinvoiceservice').val()) == '') {
            alert(emptypersonerror);
            if($checkoutButton)
                $checkoutButton.disabled = false;
            return;
        }
        if ($('#agree_with_terms_billmateinvoiceservice').prop('checked') == true) {
            if(!billmateprocessing)
                getData('', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'invoiceservice');
        } else {
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();
    })

    $('#billmatepartpaySubmit').click(function (e) {
        if ($('#pno').length > 0) {
            $("#pno_billmatepartpay").val($('#pno').val());
        }
        if($('form.billmatepartpay').length > 1){
            var form = $('form.realbillmatepartpay').serializeArray();
        } else {
            var form = $('form.billmatepartpay').serializeArray();
        }
        if ($.trim($('#pno_billmatepartpay').val()) == '') {
            alert(emptypersonerror);
            if($checkoutButton)
                $checkoutButton.disabled = false;
            return;
        }
        if ($('#agree_with_terms_billmatepartpay').prop('checked') == true) {
            if(!billmateprocessing)
                getData('', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'partpay');
        } else {
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();

    })
    if($('input[name="id_payment_method"]').length) {
        $(document).on('click', 'input[name="id_payment_method"]', function (element) {

            $('.payment-form').hide();
            var value = element.target.value;

            if ($('#payment_' + value).parents('.item,.alternate_item').hasClass('fields')) {


                $('#payment_' + value).parents('.item,.alternate_item').children('.payment_description').children('.payment-form').show();
                //$checkoutbtn = $('.confirm_button')[1].onclick;
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
                //method = method.replace('-fields','');

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
                        //$('#' + methodName + 'Submit').click();
                        submitAccount($('#' + methodName + 'Submit'));

                        e.preventDefault();
                    }
                } else {
                    if ($checkoutbtn != null) {
                        //$checkoutbtn = $('.confirm_button')[1].onclick;
                        $('.confirm_button')[$('.confirm_button').length - 1].onclick = $checkoutbtn
                    }
                }
            }

        });
    }

</script>