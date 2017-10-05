 <div class="row" style="display:none">
        <div class="col-xs-12">
            <div class="payment_module" id="billmate-bankpay">
                <style>
                    div.payment_module a.bankpay {
                        background: url("{$smarty.const._MODULE_DIR_}{$icon}") 15px 15px no-repeat #fbfbfb;

                    }
                    div.payment_module a.bankpay:after{
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
                    div.payment_module a.bankpay:hover,
                    div.payment_module a.bankpay:visited,
                    div.payment_module a.bankpay:active{
                        text-decoration: none;
                    }
                    div.payment_module .error{
                        clear:both;
                    }
                    img[src*="billmate"]{
                        float:left;
                        clear:both;
                    }
                    .payment-option > label > span {
                        float: left;
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
                    <a  class="bankpay" href="{$controller|escape:'url'}" id="billmatebankpay">{$name|escape:'html'}</a>
            </div>
        </div>
    </div>

 <script type="text/javascript" src="{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js"></script>
 <script type="text/javascript">
     function billmateBankpay() {
         var ajaxurl = "{$link->getModuleLink('billmategateway', 'billmateapi', ['ajax'=> 0], true)}";
         ajaxurl = ajaxurl.replace(/&amp;/g,'&');
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



         var version = "1.7"
         var PARTPAYMENT_EID = "{$eid}";

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



         $('#billmatebankpay').click(function (e) {
             e.preventDefault();
             getPayment('bankpay');
             return false;
         });


         if ($('input[name="id_payment_method"]').length) {
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
     }
     if (document.readyState!='loading') billmateBankpay();
     // modern browsers
     else if (document.addEventListener) document.addEventListener('DOMContentLoaded', billmateBankpay);
     // IE <= 8
     else document.attachEvent('onreadystatechange', function(){
             if (document.readyState=='complete') billmateBankpay();
         });

 </script>
