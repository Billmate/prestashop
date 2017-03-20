/**
 * Created by Boxedsolutions on 2017-03-20.
 */

window.method = null;
window.address_selected = null;
window.latestScroll = null;
var BillmateIframe = new function(){
    var self = this;
    var childWindow = null;
    this.updateAddress = function (data) {
        // When address in checkout updates;

        jQuery.ajax({
            url : UPDATE_ADDRESS_URL,
            data: data,
            type: 'POST',
            success: function(response){


                jQuery('#shipping-container').html(response);
                if(jQuery('input[name="estimate_method"]:checked').length != 1){
                    jQuery('input[name="estimate_method"]:first').click();
                }

                window.address_selected = true;
            }
        });

    };
    this.updatePaymentMethod = function(data){
        if(window.method != data.method) {
            jQuery.ajax({
                url: UPDATE_PAYMENT_METHOD_URL,
                data: data,
                type: 'POST',
                success: function (response) {
                    var result = response.evalJSON();
                    if (result.success) {
                        if(result.hasOwnProperty("update_checkout") && result.update_checkout === true)
                            self.updateCheckout();
                        if(data.method == 8 || data.method == 16)
                            self.updateCheckout();

                        window.method = data.method;

                    }
                }
            });
        }

    };
    this.updateShippingMethod = function(){

    }
    this.createOrder = function(data){
        // Create Order
        jQuery.ajax({
            url : CREATE_ORDER_URL,
            data: data,
            type: 'POST',
            success: function(response){
                var result = response.evalJSON();
                location.href=result.url;
            }
        });

    };
    this.updateTotals = function(){
        jQuery.ajax({
            url : UPDATE_TOTALS_URL,
            type: 'POST',
            success: function(response){
                jQuery('#billmate-totals').html(response);

            }
        });
    };
    this.initListeners = function () {
        document.observe('dom:loaded',function () {
            console.log('initEventListeners');
            window.addEventListener("message",self.handleEvent);



        })
    }
    this.handleEvent = function(event){
        console.log(event);
        if(event.origin == "https://checkout.billmate.se") {
            try {
                var json = JSON.parse(event.data);
            } catch (e) {
                return;
            }
            self.childWindow = json.source;
            console.log(json);
            switch (json.event) {
                case 'address_selected':
                    self.updateAddress(json.data);
                    self.updatePaymentMethod(json.data);
                    self.updateTotals();
                    if(window.method == null || window.method == json.data.method) {
                        jQuery('#checkoutdiv').removeClass('loading');
                    }
                    break;
                case 'payment_method_selected':
                    if (window.address_selected !== null) {
                        self.updatePaymentMethod(json.data);
                        self.updateTotals();
                        if(window.method == json.data.method) {
                            jQuery('#checkoutdiv').removeClass('loading');
                        }
                    }
                    break;
                case 'checkout_success':
                    self.createOrder(json.data);
                    break;
                case 'content_height':
                    $('checkout').height = json.data;
                    break;
                case 'content_scroll_position':
                    console.log('Scroll position'+json.data);
                    window.latestScroll = jQuery(document).find( "#checkout" ).offset().top + json.data;
                    jQuery('html, body').animate({scrollTop: jQuery(document).find( "#checkout" ).offset().top + json.data}, 400);
                    break;
                case 'checkout_loaded':
                    jQuery('#checkoutdiv').removeClass('loading');
                    break;
                default:
                    console.log(event);
                    console.log('not implemented')
                    break;

            }
        }

    };

    this.updateCheckout = function(){
        console.log('update_checkout');
        var win = document.getElementById('checkout').contentWindow;
        win.postMessage(JSON.stringify({event: 'update_checkout'}),'*')
    }


};
jQuery(document).ready(function(){
    jQuery(document).ajaxStart(function(){
        jQuery('#checkoutdiv').addClass('loading');
        jQuery("#checkoutdiv.loading .billmateoverlay").height(jQuery("#checkoutdiv").height());

    })

})
var b_iframe = BillmateIframe;
b_iframe.initListeners();

