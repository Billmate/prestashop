/**
 * Created by Boxedsolutions on 2017-03-20.
 */

window.method = null;
window.address_selected = null;
window.latestScroll = null;
window.previousSelectedMethod = null;
var BillmateIframe = new function(){
    var self = this;
    var childWindow = null;
    this.updatePsCheckout = function(){
        // When address in checkout updates;
        var data = {};
        data['action'] = 'updateCheckout';
        data['ajax'] = 1;
        jQuery.ajax({
            url : billmate_checkout_url,
            data: data,
            type: 'POST',
            success: function(response){
                var result = JSON.parse(response);
                if (result.success) {
                    if(result.hasOwnProperty("update_checkout") && result.update_checkout === true)
                        self.updateCheckout();


                }
            }
        });
    }
    this.updateAddress = function (data) {
        // When address in checkout updates;
        data['action'] = 'setAddress';
        data['ajax'] = 1;
        jQuery.ajax({
            url : billmate_checkout_url,
            data: data,
            type: 'POST',
            success: function(response){

                window.address_selected = true;
            }
        });

    };
    this.updateShippingMethod = function(method){
        jQuery.ajax({
            url: UPDATE_SHIPPING_METHOD_URL,
            data: {'shipping_method': method},
            type: 'POST',
            success: function (response) {
                var result = JSON.parse(response);
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
    this.updatePaymentMethod = function(data){
        if(window.method != data.method) {
            data['action'] = 'setPaymentMethod';
            data['ajax'] = 1;
            jQuery.ajax({
                url: billmate_checkout_url,
                data: data,
                type: 'POST',
                success: function (response) {
                    var result = JSON.parse(response);
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
        data['action'] = 'validateOrder';
        data['ajax'] = 1;
        jQuery.ajax({
            url : billmate_checkout_url,
            data: data,
            type: 'POST',
            success: function(response){
                var result = JSON.parse(response);
                if(result.success){
                    location.href=result.redirect;
                }
            }
        });

    };

    this.initListeners = function () {
        jQuery(document).ready(function(){

            console.log('initEventListeners');
            window.addEventListener("message",self.handleEvent);
        });

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

                    if(window.method == null || window.method == json.data.method) {
                        jQuery('#checkoutdiv').removeClass('loading');
                    }
                    break;
                case 'payment_method_selected':
                    if (window.address_selected !== null) {
                        self.updatePaymentMethod(json.data);

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

window.b_iframe = BillmateIframe;
window.b_iframe.initListeners();
jQuery(document).ready(function(){
    jQuery(document).ajaxStart(function(){
        jQuery('#checkoutdiv').addClass('loading');
        jQuery("#checkoutdiv.loading .billmateoverlay").height(jQuery("#checkoutdiv").height());

    })

    jQuery(document).ajaxComplete(function(){
        jQuery('#checkoutdiv').removeClass('loading');

    })

    $("#button_order_cart").attr("href", billmate_checkout_url);
    $("#layer_cart .layer_cart_cart a.button-medium").attr("href", billmate_checkout_url);
    $("#order p.cart_navigation a.standard-checkout").attr("href", billmate_checkout_url);
    if(window.location.href == billmate_checkout_url) {
        $('body').attr('id', 'order');
    }
    $('body').on('click','.delivery_option_radio',function(e){
        e.preventDefault();
        var selectedMethod = e.target.value;
        if(selectedMethod != window.previousSelectedMethod){
            window.previousSelectedMethod = selectedMethod;
            var url = billmate_checkout_url
            var delivery_option = $('.delivery_option_radio:checked').val();
            var address_id = $('.delivery_option_radio:checked').data('id_address');
            var values = {};
            values['delivery_option['+address_id+']'] = delivery_option;
            values['action'] = 'setShipping';
            values['ajax'] = 1
            jQuery.ajax({
                url: url,
                data: values,
                success: function(response){
                    var result = JSON.parse(response);
                    console.log(result);
                    if(result.hasOwnProperty("update_checkout") && result.update_checkout === true){
                        window.b_iframe.updateCheckout();
                    }
                }
            })
        }
    })

});
function deleteProductFromSummary(id){
    console.log('product removed');

    window.b_iframe.updatePsCheckout();
}

