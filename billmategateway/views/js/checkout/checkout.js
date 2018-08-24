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
    var timerPostMessageUpdate;
    var timerPostMessageLock;

    this.updatePsCheckout = function(){
        /* When address in checkout updates; */
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


                }else {
                    location.reload();
                }
            }
        });
    }
    this.updateAddress = function (data) {
        if (typeof(window.previousSelectedMethod) == 'undefined' || window.previousSelectedMethod == null) {
            window.previousSelectedMethod = $(document).find('input[type="radio"][name^="delivery_option["]:checked').val();
        }

        that = this;
        /* When address in checkout updates; */
        data['action'] = 'setAddress';
        data['delivery_option'] = window.previousSelectedMethod;

        if (data.hasOwnProperty('Customer') && data.hasOwnProperty('billingAddress')) {
            data.Customer.Billing = data.billingAddress;
        }

        data['ajax'] = 1;
        jQuery.ajax({
            url : billmate_checkout_url,
            data: data,
            type: 'POST',
            success: function(response){
                try {
                    var result = JSON.parse(response);
                    if(result.success)
                    {
                        /* Show available shipping methods for saved address */
                        jQuery('#shippingdiv').html(result.carrier_block);
                        that.hideShippingElements();

                        if (jQuery(document).find('#shippingdiv input[type=radio][data-key="'+window.previousSelectedMethod+'"]').length > 0) {
                            jQuery(document).find('#shippingdiv input[type=radio]').closest('span').removeClass('checked');
                            jQuery(document).find('#shippingdiv input[type=radio][data-key="'+window.previousSelectedMethod+'"]').closest('span').addClass('checked');
                            jQuery(document).find('#shippingdiv input[type=radio]').attr('checked', false);
                            jQuery(document).find('#shippingdiv input[type=radio][data-key="'+window.previousSelectedMethod+'"]').attr('checked', true);
                        }

                        jQuery(window).trigger('resize');
                    }
                    window.address_selected = true;
                } catch (err) {
                    // Silent fail
                }
            }
        });

    };
    this.updateShippingMethod = function(shippingElementKey, reload = false) {
        var url = billmate_checkout_url;
        var delivery_option = $(document).find('input[type="radio"][name^="delivery_option["]:checked').val();
        if (shippingElementKey != null) {
            var delivery_option = shippingElementKey;
        }

        var elementName = $(document).find('input[type="radio"][name^="delivery_option["]:checked').attr('name');
        var address_id = parseInt(elementName.match(/[0-9]+/));

        window.previousSelectedMethod = delivery_option;

        if (jQuery(document).find('#shippingdiv input[type=radio][data-key="'+window.previousSelectedMethod+'"]').length > 0) {
            jQuery(document).find('#shippingdiv input[type=radio]').closest('span').removeClass('checked');
            jQuery(document).find('#shippingdiv input[type=radio][data-key="'+window.previousSelectedMethod+'"]').closest('span').addClass('checked');
        }

        var values = {};
        values['delivery_option['+address_id+']'] = delivery_option;
        values['action'] = 'setShipping';
        values['ajax'] = 1;


        jQuery.ajax({
            url: url,
            data: values,
            success: function(response){
                if (reload == true) {
                    location.reload();
                } else {
                    window.b_iframe.updateCheckout();
                }
            }
        });
    }
    this.updatePaymentMethod = function(data){
        return true;
    };
    this.createOrder = function(data){
        /* Create Order */
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
            window.addEventListener("message",self.handleEvent);
            if($('#billmate_summary').length) {

                if(typeof prestashop != 'undefined') {
                    prestashop.on('updatedCart', function (e) {
                        location.reload();
                    })
                }
            }
        });

    }
    this.handleEvent = function(event){
        if(event.origin == "https://checkout.billmate.se") {
            try {
                var json = JSON.parse(event.data);
            } catch (e) {
                return;
            }
            self.childWindow = json.source;
            switch (json.event) {

                case 'show_overlay':
                    if ($(document).find('#billmateCheckoutOverlay').length < 1) {
                        var $div = $('<div />').appendTo('body');
                        $div.attr('id', 'billmateCheckoutOverlay');
                    }
                    resizeBillmateCheckoutOverlay();
                    $(document).find("#billmateCheckoutOverlay").show();
                    break;

                case 'hide_overlay':
                    $(document).find("#billmateCheckoutOverlay").hide();
                    break;

                case 'go_to':
                    location.href = json.data;
                    break;

                case 'address_selected':
                    self.updateAddress(json.data);
                    self.updatePaymentMethod(json.data);

                    if(window.method == null || window.method == json.data.method) {
                        self.unlock();
                    }
                    break;
                case 'payment_method_selected':
                    if (window.address_selected !== null) {
                        self.updatePaymentMethod(json.data);

                        if(window.method == json.data.method) {
                            self.unlock();
                        }
                    }
                    break;
                case 'checkout_success':
                    self.createOrder(json.data);
                    break;
                case 'content_height':
                    $(document).find('#checkout').height(json.data);
                    break;
                case 'content_scroll_position':
                    window.latestScroll = jQuery(document).find( "#checkout" ).offset().top + json.data;
                    if (jQuery(document).scrollTop() > 0) {
                        jQuery('html, body').animate({scrollTop: jQuery(document).find( "#checkout" ).offset().top + json.data}, 400);
                    }
                    break;
                case 'checkout_loaded':
                    self.unlock();
                    break;
                default:
                    break;

            }
        }

    };


    this.checkoutPostMessage = function(message) {
        if(window.location.href == billmate_checkout_url) {
            var win = document.getElementById('checkout').contentWindow;
            win.postMessage(message,'*');
        }
    }

    this.updateCheckout = function(){
        this.lock();
        that = this;
        clearTimeout(this.timerPostMessageUpdate);
        var wait = setTimeout(function() {
            that.checkoutPostMessage('update');
        }, 400);
        this.timerPostMessageUpdate = wait;
    }

    this.lock = function() {
        that = this;
        var wait = setTimeout(function() {
            that.checkoutPostMessage('lock');
        }, 400);
        this.timerPostMessageLock = wait;
    }

    this.unlock = function() {
        that = this;
        var wait = setTimeout(function() {
            that.checkoutPostMessage('unlock');
        }, 400);
        this.timerPostMessageLock = wait;
    }

    this.hideShippingElements = function() {
        $(document).find('.container #shippingdiv .order_carrier_content p.carrier_title + div').has('textarea').hide();
        $(document).find('.container #shippingdiv .order_carrier_content hr').hide();
        $(document).find('.container #shippingdiv .order_carrier_content .box').hide();
        $(document).find('.container #shippingdiv .order_carrier_content .carrier_title').hide();
    }

};

function resizeBillmateCheckoutOverlay() {
    if ($(document).find('#billmateCheckoutOverlay').length > 0) {
        height = $(document).innerHeight();
        if ($(window).height() + $(window).scrollTop() > height) {
            height = $(window).height() + $(window).scrollTop();
        }
        width = $(document).innerWidth();
        $("#billmateCheckoutOverlay").height(height);
        $("#billmateCheckoutOverlay").width(width);
    }
}

$(window).resize(function () {
    resizeBillmateCheckoutOverlay();
});

$(document).resize(function () {
    resizeBillmateCheckoutOverlay();
});

$(document).scroll(function() {
    resizeBillmateCheckoutOverlay();
});

window.b_iframe = BillmateIframe;
window.b_iframe.initListeners();
window.tmpshippingvalue = $('#total_shipping').html();

var BillmateCart = new function () {
    this.updateProduct = function(type,id,qty){
        var self = this;
        var val = $('input[name=quantity_'+id+']').val();
        var newQty = val;
        var action = '';
        if(type == 'sub') {
            if (typeof(qty) === 'undefined' || !qty) {
                qty = 1;
                newQty = val - 1;
                action = '&op=down';
            }
            else if (qty < 0)
                qty = -qty;
        } else {
            if (typeof(qty) === 'undefined' || !qty) {
                qty = 1;
            }
        }

        var customizationId = 0;
        var productId = 0;
        var productAttributeId = 0;
        var id_address_delivery = 0;
        var ids = 0;

        ids = id.split('_');
        productId = parseInt(ids[0]);
        if (typeof(ids[1]) !== 'undefined')
            productAttributeId = parseInt(ids[1]);
        if (typeof(ids[2]) !== 'undefined')
            customizationId = parseInt(ids[2]);
        if (typeof(ids[3]) !== 'undefined')
            id_address_delivery = parseInt(ids[3]);

        if (newQty > 0 || $('#product_'+id+'_gift').length)
        {
            $.ajax({
                type: 'GET',
                url: baseUri,
                async: true,
                cache: false,
                dataType: 'json',
                data: 'controller=cart'
                +'&ajax=true'
                +'&add'
                +'&getproductprice'
                +'&summary'
                +'&id_product='+productId
                +'&ipa='+productAttributeId
                +'&id_address_delivery='+id_address_delivery
                +action
                + ((customizationId !== 0) ? '&id_customization='+customizationId : '')
                +'&qty='+qty
                +'&token='+static_token
                +'&allow_refresh=1',
                success: function(jsonData)
                {
                    if (jsonData.hasError)
                    {
                        var errors = '';
                        for(var error in jsonData.errors)
                            if(error !== 'indexOf')
                                errors += jsonData.errors[error] + "\n";
                        alert(errors);
                        $('input[name=quantity_'+ id +']').val($('input[name=quantity_'+ id +'_hidden]').val());
                    }
                    else
                    {


                        if (jsonData.hasOwnProperty('refresh')) {
                            location.reload();
                            return;
                        } else {
                            self.updateCart(jsonData.summary);
                            self.updateHookShoppingCart(jsonData.HOOK_SHOPPING_CART);
                            self.updateHookShoppingCartExtra(jsonData.HOOK_SHOPPING_CART_EXTRA);
                            window.b_iframe.updateCheckout();
                        }

                        if (newQty === 0) {
                            $('#product_'+id).hide();
                        }

                        if (typeof(getCarrierListAndUpdate) !== 'undefined') {
                            getCarrierListAndUpdate();
                        }
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    if (textStatus !== 'abort')
                        alert("TECHNICAL ERROR: unable to save update quantity \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
                }
            });

        }
        else
        {
            self.deleteProductFromSummary(id);
        }
    };

    this.deleteProductFromSummary = function(id){
        var self = this;
        var customizationId = 0;
        var productId = 0;
        var productAttributeId = 0;
        var id_address_delivery = 0;
        var ids = id.split('_');

        productId = parseInt(ids[0]);
        if (typeof(ids[1]) !== 'undefined')
            productAttributeId = parseInt(ids[1]);
        if (typeof(ids[2]) !== 'undefined')
            customizationId = parseInt(ids[2]);
        if (typeof(ids[3]) !== 'undefined')
            id_address_delivery = parseInt(ids[3]);
        $.ajax({
            type: 'GET',
            url: baseUri,
            async: true,
            cache: false,
            dataType: 'json',
            data: 'controller=cart'
            +'&ajax=true&delete&summary'
            +'&id_product='+productId
            +'&ipa='+productAttributeId
            +'&id_address_delivery='+id_address_delivery+ ( (customizationId !== 0) ? '&id_customization='+customizationId : '')
            +'&token=' + static_token
            +'&allow_refresh=1',
            success: function(jsonData)
            {
                if (jsonData.hasError)
                {
                    var errors = '';
                    for(var error in jsonData.errors)
                        /* IE6 bug fix */
                        if (error !== 'indexOf')
                            errors += jsonData.errors[error] + "\n";
                }
                else
                {
                    if (jsonData.refresh) {
                        location.reload();
                    }
                    if (parseInt(jsonData.summary.products.length) === 0)
                    {
                        if (typeof(orderProcess) === 'undefined' || orderProcess !== 'order-opc') {
                            document.location.href = document.location.href; /* redirection */
                        } else {
                            $('#center_column').children().each(function() {
                                if ($(this).attr('id') !== 'emptyCartWarning' && $(this).attr('class') !== 'breadcrumb' && $(this).attr('id') !== 'cart_title')
                                {
                                    $(this).fadeOut('slow', function () {
                                        $(this).remove();
                                    });
                                }
                            });
                            $('#summary_products_label').remove();
                            $('#emptyCartWarning').fadeIn('slow');
                        }
                    }
                    else
                    {
                        $('#product_'+ id).fadeOut('slow', function() {
                            $(this).remove();
                            if (!customizationId)
                                self.refreshOddRow();
                        });



                        var exist = false;
                        $.each(jsonData.summary.products,function(i,value){
                            if(value.id_product === productId && value.id_product_attribute === productAttributeId && value.id_address.delivery == id_address_delivery)
                                exist = true;
                        })


                        /* if all customization removed => delete product line */
                        if (!exist && customizationId)
                        {
                            $('#product_' + productId + '_' + productAttributeId + '_0_' + id_address_delivery).fadeOut('slow', function() {
                                $(this).remove();
                                self.refreshOddRow();
                            });

                        }
                    }
                    self.updateCart(jsonData.summary);
                    self.updateHookShoppingCart(jsonData.HOOK_SHOPPING_CART);
                    self.updateHookShoppingCartExtra(jsonData.HOOK_SHOPPING_CART_EXTRA);
                    if (typeof(getCarrierListAndUpdate) !== 'undefined')
                        getCarrierListAndUpdate();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                if (textStatus !== 'abort')
                    alert("TECHNICAL ERROR: unable to save update quantity \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
            }
        });
    };

    this.updateCart = function(json)
    {
        var i;
        var nbrProducts = 0;

        if (typeof json === 'undefined')
            return;

        $('.cart_quantity_input').val(0);

        product_list = {};
        for (i=0;i<json.products.length;i++)
            product_list[json.products[i].id_product+'_'+json.products[i].id_product_attribute+'_'+json.products[i].id_address_delivery] = json.products[i];

        if (!$('.multishipping-cart:visible').length)
        {
            for (i=0;i<json.gift_products.length;i++)
            {
                if (typeof(product_list[json.gift_products[i].id_product+'_'+json.gift_products[i].id_product_attribute+'_'+json.gift_products[i].id_address_delivery]) !== 'undefined')
                    product_list[json.gift_products[i].id_product+'_'+json.gift_products[i].id_product_attribute+'_'+json.gift_products[i].id_address_delivery].quantity -= json.gift_products[i].cart_quantity;
            }
        }
        else
        {
            for (i=0;i<json.gift_products.length;i++)
            {
                if (typeof(product_list[json.gift_products[i].id_product+'_'+json.gift_products[i].id_product_attribute+'_'+json.gift_products[i].id_address_delivery]) === 'undefined')
                    product_list[json.gift_products[i].id_product+'_'+json.gift_products[i].id_product_attribute+'_'+json.gift_products[i].id_address_delivery] = json.gift_products[i];
            }
        }

        for (i in product_list)
        {
            /* if reduction, we need to show it in the cart by showing the initial price above the current one */
            var reduction = product_list[i].reduction_applies;
            var reduction_type = product_list[i].reduction_type;
            var reduction_symbol = '';
            var initial_price_text = '';
            var initial_price = '';

            if (typeof(product_list[i].price_without_quantity_discount) !== 'undefined')
                initial_price = formatCurrency(product_list[i].price_without_quantity_discount, currencyFormat, currencySign, currencyBlank);

            var current_price = '';
            var product_total = '';
            var product_customization_total = '';

            if (priceDisplayMethod !== 0)
            {
                current_price = formatCurrency(product_list[i].price, currencyFormat, currencySign, currencyBlank);
                product_total = product_list[i].total;
                product_customization_total = product_list[i].total_customization;
            }
            else
            {
                current_price = formatCurrency(product_list[i].price_wt, currencyFormat, currencySign, currencyBlank);
                product_total = product_list[i].total_wt;
                product_customization_total = product_list[i].total_customization_wt;
            }

            var current_price_class ='price';
            var price_reduction = '';
            if (reduction && typeof(initial_price) !== 'undefined')
            {
                if (reduction_type == 'amount')
                    price_reduction = product_list[i].reduction_formatted;
                else
                {
                    var display_price = 0;
                    if (priceDisplayMethod !== 0)
                        display_price = product_list[i].price;
                    else
                        display_price = product_list[i].price_wt;

                    price_reduction = ps_round((product_list[i].price_without_quantity_discount - display_price)/product_list[i].price_without_quantity_discount * -100);
                    reduction_symbol = '%';
                }

                if (initial_price !== '' && product_list[i].price_without_quantity_discount > product_list[i].price)
                {
                    initial_price_text = '<li class="price-percent-reduction small">&nbsp;'+price_reduction+reduction_symbol+'&nbsp;</li><li class="old-price">' + initial_price + '</li>';
                    current_price_class += ' special-price';
                }
            }

            var key_for_blockcart = product_list[i].id_product + '_' + product_list[i].id_product_attribute + '_' + product_list[i].id_address_delivery;
            var key_for_blockcart_nocustom = product_list[i].id_product + '_' + product_list[i].id_product_attribute + '_' + ((product_list[i].id_customization && product_list[i].quantity_without_customization != product_list[i].quantity)? 'nocustom' : '0') + '_' + product_list[i].id_address_delivery;

            $('#product_price_' + key_for_blockcart).html('<li class="' + current_price_class + '">' + current_price + '</li>' + initial_price_text);
            if (typeof(product_list[i].customizationQuantityTotal) !== 'undefined' && product_list[i].customizationQuantityTotal > 0)
                $('#total_product_price_' + key_for_blockcart).html(formatCurrency(product_customization_total, currencyFormat, currencySign, currencyBlank));
            else
                $('#total_product_price_' + key_for_blockcart).html(formatCurrency(product_total, currencyFormat, currencySign, currencyBlank));
            if (product_list[i].quantity_without_customization != product_list[i].quantity)
                $('#total_product_price_' + key_for_blockcart_nocustom).html(formatCurrency(product_total, currencyFormat, currencySign, currencyBlank));

            $('input[name=quantity_' + key_for_blockcart_nocustom + ']').val(product_list[i].id_customization? product_list[i].quantity_without_customization : product_list[i].cart_quantity);
            $('input[name=quantity_' + key_for_blockcart_nocustom + '_hidden]').val(product_list[i].id_customization? product_list[i].quantity_without_customization : product_list[i].cart_quantity);
            if (typeof(product_list[i].customizationQuantityTotal) !== 'undefined' && product_list[i].customizationQuantityTotal > 0)
                $('#cart_quantity_custom_' + key_for_blockcart).html(product_list[i].customizationQuantityTotal);
            nbrProducts += parseInt(product_list[i].quantity);
        }

        /* Update discounts */
        if (json.discounts.length === 0)
        {
            $('.cart_discount').each(function(){$(this).remove();});
            $('.cart_total_voucher').remove();
        }
        else
        {
            if ($('.cart_discount').length === 0)
                location.reload();

            if (priceDisplayMethod !== 0)
                $('#total_discount').html(formatCurrency(json.total_discounts_tax_exc, currencyFormat, currencySign, currencyBlank));
            else
                $('#total_discount').html(formatCurrency(json.total_discounts, currencyFormat, currencySign, currencyBlank));

            $('.cart_discount').each(function(){
                var idElmt = $(this).attr('id').replace('cart_discount_','');
                var toDelete = true;

                for (i=0;i<json.discounts.length;i++)
                {
                    if (json.discounts[i].id_discount === idElmt)
                    {
                        if (json.discounts[i].value_real !== '!')
                        {
                            if (priceDisplayMethod !== 0)
                                $('#cart_discount_' + idElmt + ' td.cart_discount_price span.price-discount').html(formatCurrency(json.discounts[i].value_tax_exc * -1, currencyFormat, currencySign, currencyBlank));
                            else
                                $('#cart_discount_' + idElmt + ' td.cart_discount_price span.price-discount').html(formatCurrency(json.discounts[i].value_real * -1, currencyFormat, currencySign, currencyBlank));

                        }
                        toDelete = false;
                    }
                }
                if (toDelete)
                    $('#cart_discount_' + idElmt + ', #cart_total_voucher').fadeTo('fast', 0, function(){ $(this).remove(); });
            });
        }

        /* Block cart */
        if (priceDisplayMethod !== 0)
        {
            $('#cart_block_shipping_cost').html(formatCurrency(json.total_shipping_tax_exc, currencyFormat, currencySign, currencyBlank));
            $('#cart_block_wrapping_cost').html(formatCurrency(json.total_wrapping_tax_exc, currencyFormat, currencySign, currencyBlank));
            $('#cart_block_total').html(formatCurrency(json.total_price_without_tax, currencyFormat, currencySign, currencyBlank));
        } else {
            $('#cart_block_shipping_cost').html(formatCurrency(json.total_shipping, currencyFormat, currencySign, currencyBlank));
            $('#cart_block_wrapping_cost').html(formatCurrency(json.total_wrapping, currencyFormat, currencySign, currencyBlank));
            $('#cart_block_total').html(formatCurrency(json.total_price, currencyFormat, currencySign, currencyBlank));
        }

        $('#cart_block_tax_cost').html(formatCurrency(json.total_tax, currencyFormat, currencySign, currencyBlank));
        $('.ajax_cart_quantity').html(nbrProducts);

        /* Cart summary */
        $('#summary_products_quantity').html(nbrProducts+' '+(nbrProducts > 1 ? txtProducts : txtProduct));
        if (priceDisplayMethod !== 0)
        {
            $('#total_product').html(formatCurrency(json.total_products, currencyFormat, currencySign, currencyBlank));
        }
        else
        {
            $('#total_product').html(formatCurrency(json.total_products_wt, currencyFormat, currencySign, currencyBlank));
        }
        $('#total_price').html(formatCurrency(json.total_price, currencyFormat, currencySign, currencyBlank));
        $('#total_price_without_tax').html(formatCurrency(json.total_price_without_tax, currencyFormat, currencySign, currencyBlank));
        $('#total_tax').html(formatCurrency(json.total_tax, currencyFormat, currencySign, currencyBlank));

        if (json.total_shipping > 0)
        {
            if (priceDisplayMethod !== 0)
            {
                $('#total_shipping').html(formatCurrency(json.total_shipping_tax_exc, currencyFormat, currencySign, currencyBlank));
            }
            else
            {
                $('#total_shipping').html(formatCurrency(json.total_shipping, currencyFormat, currencySign, currencyBlank));
            }
        }
        else
        {
            $('#total_shipping').html(freeShippingTranslation);
        }

        if($('#total_shipping').html() != window.tmpshippingvalue) {
            /* force page reload */
            location.href = billmate_checkout_url;
        } else {
            window.tmpshippingvalue = $('#total_shipping').html();
        }



        if (json.total_wrapping > 0)
        {
            $('#total_wrapping').html(formatCurrency(json.total_wrapping, currencyFormat, currencySign, currencyBlank));
            $('#total_wrapping').parent().show();
        }
        else
        {
            $('#total_wrapping').html(formatCurrency(json.total_wrapping, currencyFormat, currencySign, currencyBlank));
            $('#total_wrapping').parent().hide();
        }
        if (window.ajaxCart !== undefined)
            ajaxCart.refresh();

        window.b_iframe.updatePsCheckout();
    };
    this.refreshOddRow = function()
    {
        var odd_class = 'odd';
        var even_class = 'even';
        $.each($('.cart_item'), function(i, it)
        {
            if (i === 0)
            {
                if ($(this).hasClass('even'))
                {
                    odd_class = 'even';
                    even_class = 'odd';
                }
                $(this).addClass('first_item');
            }
            if(i % 2)
                $(this).removeClass(odd_class).addClass(even_class);
            else
                $(this).removeClass(even_class).addClass(odd_class);
        });
        $('.cart_item:last-child, .customization:last-child').addClass('last_item');
    }
    this.updateHookShoppingCart = function(html)
    {
        $('#HOOK_SHOPPING_CART').html(html);
    };

    this.updateHookShoppingCartExtra = function(html)
    {
        $('#HOOK_SHOPPING_CART_EXTRA').html(html);
    }

};
window.b_cart = BillmateCart;


jQuery(document).ready(function(){
    jQuery(document).ajaxStart(function(){
        window.b_iframe.lock();

    });

    jQuery(document).ajaxComplete(function(){
        window.b_iframe.unlock();
    });

    $("#header .shopping_cart a").attr("href", billmate_checkout_url);
    $("#button_order_cart").attr("href", billmate_checkout_url);
    $("#layer_cart .layer_cart_cart a.button-medium").attr("href", billmate_checkout_url);
    $("#order p.cart_navigation a.standard-checkout").attr("href", billmate_checkout_url);

    $('.cart-summary .checkout .btn').attr("href", billmate_checkout_url);
    $('.cart-content-btn .btn').attr("href", billmate_checkout_url);
    if( $("#billmate_summary a.cart_quantity_delete").length) {
        $("#billmate_summary a.cart_quantity_delete").unbind('click').live('click', function () {
            window.b_cart.deleteProductFromSummary($(this).attr('id'));
            return false;
        });
    }
    if($("#billmate_summary a.cart_quantity_up").length) {
        $("#billmate_summary a.cart_quantity_up").unbind('click').live('click', function () {
            window.b_cart.updateProduct('add', $(this).attr('id').replace('cart_quantity_up_', ''));
            return false;
        });
    }
    if($("#billmate_summary a.cart_quantity_down").length) {
        $("#billmate_summary a.cart_quantity_down").unbind('click').live('click', function () {
            window.b_cart.updateProduct('sub', $(this).attr('id').replace('cart_quantity_down_', ''));
            return false;
        });
    }
    if(window.location.href == billmate_checkout_url) {
        $('body').attr('id', 'order');
    }

    if (is_billmate_checkout_page == 'yes') {
        if ($(document).find('input[type="radio"][name^="delivery_option["]').closest('form[id="js-delivery"]').length > 0) {
            $(document).find('input[type="radio"][name^="delivery_option["]').closest('form[id="js-delivery"]').find('textarea').closest('div').remove();
            $(document).find('input[type="radio"][name^="delivery_option["]').closest('form[id="js-delivery"]').find('button').remove();
            $(document).find('input[type="radio"][name^="delivery_option["]').closest('form[id="js-delivery"]').closest('section#shipping').find('h1 i').remove();
            $(document).find('input[type="radio"][name^="delivery_option["]').closest('form[id="js-delivery"]').closest('section#shipping').find('h1 span').remove();

            $(document).find('input[type="radio"][name^="delivery_option["]').closest('form[id="js-delivery"]').removeAttr('data-url-update');
            $(document).find('input[type="radio"][name^="delivery_option["]').closest('form[id="js-delivery"]').removeAttr('method');
            $(document).find('input[type="radio"][name^="delivery_option["]').closest('form[id="js-delivery"]').removeAttr('id');
        }

        $('body').on('click', 'input[type="radio"][name^="delivery_option["]', function(e) {
            var selectedMethod = e.target.value;
            window.b_iframe.updateShippingMethod(selectedMethod, true);
        });
    }

    if (is_billmate_checkout_page == 'yes') {
        window.b_iframe.hideShippingElements();
    }

});
function deleteProductFromSummary(id){
    window.b_iframe.updatePsCheckout();
}

