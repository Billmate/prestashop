/*
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-17
 * Time: 13:01
 * @author Jesper Johansson jesper@boxedlogistics.se
 * @copyright Billmate AB 2015
 */
var $checkoutbtn = null;
var billmateprocessing = false;
var billmatepopupLoaded = false;
if (navigator.appName != 'Microsoft Internet Explorer') {
//https://github.com/paulirish/matchMedia.js/
    window.matchMedia || (window.matchMedia = function () {
        "use strict";
        var e = window.styleMedia || window.media;
        if (!e) {
            var t = document.createElement("style"), n = document.getElementsByTagName("script")[0], r = null;
            t.type = "text/css";
            t.id = "matchmediajs-test";
            n.parentNode.insertBefore(t, n);
            r = "getComputedStyle"in window && window.getComputedStyle(t, null) || t.currentStyle;
            e = {
                matchMedium: function (e) {
                    var n = "@media " + e + "{ #matchmediajs-test { width: 1px; } }";
                    if (t.styleSheet) {
                        t.styleSheet.cssText = n
                    } else {
                        t.textContent = n
                    }
                    return r.width === "1px"
                }
            }
        }
        return function (t) {
            return {matches: e.matchMedium(t || "all"), media: t || "all"}
        }
    }())

//https://raw.github.com/paulirish/matchMedia.js/master/matchMedia.addListener.js
    (function () {
        if (window.matchMedia && window.matchMedia("all").addListener) {
            return false
        }
        var e = window.matchMedia, t = e("only all").matches, n = false, r = 0, i = [], s = function (t) {
            clearTimeout(r);
            r = setTimeout(function () {
                for (var t = 0, n = i.length; t < n; t++) {
                    var r = i[t].mql, s = i[t].listeners || [], o = e(r.media).matches;
                    if (o !== r.matches) {
                        r.matches = o;
                        for (var u = 0, a = s.length; u < a; u++) {
                            s[u].call(window, r)
                        }
                    }
                }
            }, 30)
        };
        window.matchMedia = function (r) {
            var o = e(r), u = [], a = 0;
            o.addListener = function (e) {
                if (!t) {
                    return
                }
                if (!n) {
                    n = true;
                    window.addEventListener("resize", s, true)
                }
                if (a === 0) {
                    a = i.push({mql: o, listeners: u})
                }
                u.push(e)
            };
            o.removeListener = function (e) {
                for (var t = 0, n = u.length; t < n; t++) {
                    if (u[t] === e) {
                        u.splice(t, 1)
                    }
                }
            };
            return o
        }
    })()
}
if (typeof modalWin == 'undefined') {

    var xxx_modalPopupWindow = null;
    var popupshowed = false;

    function CreateModalPopUpObject() {
        if (xxx_modalPopupWindow == null) {
            xxx_modalPopupWindow = new ModalPopupWindow();
        }
        return xxx_modalPopupWindow;
    }

    function ModalPopupWindow() {
        var strOverLayHTML = '<div id="divOverlay" style="position:absolute;z-index:10; background-color:WHITE; filter: alpha(opacity = 70);opacity:0.7;"></div><div id="divFrameParent" style="position:absolute;z-index:9999; display:none;background-color:white;border:1px solid;-moz-box-shadow: 0 0 10px 10px #BBB;-webkit-box-shadow: 0 0 10px 10px #BBB;box-shadow: 0 0 10px 10px #BBB;padding:10px;line-height:21px;font-size:15px;color:#000;text-align:left;font-family:Arial,Helvetica,sans-serif;"	class="Example_F"><div class="checkout-heading" id="spanOverLayTitle"></div><div id="divMessage" style="display:none;"><span id="spanMessage"></span></div><span id="spanLoading"></span></div>'
        var orginalHeight;
        var orginalWidth;
        var btnStyle = "";
        var maximize = false;
        div = document.createElement("div");
        div.innerHTML = strOverLayHTML;
        //document.body.appendChild(div);
        document.body.insertBefore(div, document.body.firstChild);

        this.ResizePopUp = function (height, width) {
            var divFrameParent = document.getElementById("divFrameParent");
            var divOverlay = document.getElementById("divOverlay");
            var left = (window.screen.availWidth - width) / 2;
            var top = (window.screen.availHeight - height) / 2;
            var xy = GetScroll();
            if (maximize) {
                left = xy[0] + 10;
                top = xy[1] + 10;
            } else {
                left += xy[0];
                top += xy[1];
            }
            divFrameParent.style.top = top + "px";
            divFrameParent.style.left = left + "px";
            divFrameParent.style.height = height + "px";
            divFrameParent.style.width = width + "px";
            ShowDivInCenter("divFrameParent");
        }
        var onPopUpCloseCallBack = null;
        var callbackArray = null;

        this.SetButtonStyle = function (_btnStyle) {
            btnStyle = _btnStyle;
        }

        function ApplyBtnStyle() {
        }

        function __InitModalPopUp(height, width, title) {

            orginalWidth = width;
            orginalHeight = height;
            maximize = false;
            var divFrameParent = document.getElementById("divFrameParent");
            var divOverlay = document.getElementById("divOverlay");
            var left = (window.screen.availWidth - width) / 2;
            var top = (window.screen.availHeight - height) / 2;
            var xy = GetScroll();
            left += xy[0];
            top += xy[1];
            document.getElementById("spanOverLayTitle").innerHTML = title;
            divOverlay.style.top = "0px";
            divOverlay.style.left = "0px";
            var e = document;
            var c = "Height";
            var maxHeight = Math.max(e.documentElement["client" + c], e.body["scroll" + c], e.documentElement["scroll" + c], e.body["offset" + c], e.documentElement["offset" + c]);
            c = "Width";
            var maxWidth = Math.max(e.documentElement["client" + c], e.body["scroll" + c], e.documentElement["scroll" + c], e.body["offset" + c], e.documentElement["offset" + c]);
            divOverlay.style.height = maxHeight + "px";
            divOverlay.style.width = maxWidth - 2 + "px";
            divOverlay.style.display = "";
            divFrameParent.style.display = "";
            //$('#divFrameParent').animate({ opacity: 1 }, 2000);
            divFrameParent.style.top = (top - 100) + "px";
            divFrameParent.style.left = left + "px";
            //divFrameParent.style.height = height + "px";
            divFrameParent.style.width = width + "px";
            onPopUpCloseCallBack = null;
            callbackArray = null;
        }

        this.ShowMessage = function (message, height, width, title) {
            __InitModalPopUp(height, width, title);
            popupshowed = true;
            document.getElementById("spanMessage").innerHTML = message;
            document.getElementById("divMessage").style.display = "";
            document.getElementById("spanLoading").style.display = "none";
            ApplyBtnStyle();
            ShowDivInCenter("divFrameParent");
        }
        this.ShowConfirmationMessage = function (message, height, width, title, onCloseCallBack, firstButtonText, onFirstButtonClick, secondButtonText, onSecondButtonClick) {
            this.ShowMessage(message, height, width, title);
            var maxWidth = 100;
            document.getElementById("spanMessage").innerHTML = message;
            document.getElementById("divMessage").style.display = "";
            document.getElementById("spanLoading").style.display = "none";
            if (onCloseCallBack != null && onCloseCallBack != '') {
                onPopUpCloseCallBack = onCloseCallBack;
            }
            ApplyBtnStyle();
        }
        function ShowLoading() {
            document.getElementById("spanLoading").style.display = "";
        }

        this.HideModalPopUp = function () {
            popupshowed = false;
            var divFrameParent = document.getElementById("divFrameParent");
            var divOverlay = document.getElementById("divOverlay");
            divOverlay.style.display = "none";
            divFrameParent.style.display = "none";
            if (onPopUpCloseCallBack != null && onPopUpCloseCallBack != '') {
                onPopUpCloseCallBack();
            }
        }
        this.CallCallingWindowFunction = function (index, para) {
            callbackArray[index](para);
        }
        this.ChangeModalPopUpTitle = function (title) {
            document.getElementById("spanOverLayTitle").innerHTML = title;
        }

        function setParentVariable(variableName, variableValue) {
            window[String(variableName)] = variableValue;
        }

        function GetScroll() {
            if (window.pageYOffset != undefined) {
                return [pageXOffset, pageYOffset];
            } else {
                var sx, sy, d = document,
                    r = d.documentElement,
                    b = d.body;
                sx = r.scrollLeft || b.scrollLeft || 0;
                sy = r.scrollTop || b.scrollTop || 0;
                return [sx, sy];
            }
        }
    }


    function AddEvent(html_element, event_name, event_function) {
        if (html_element.attachEvent) //Internet Explorer
            html_element.attachEvent("on" + event_name, function () {
                event_function.call(html_element);
            });
        else if (html_element.addEventListener) //Firefox & company
            html_element.addEventListener(event_name, event_function, false); //don't need the 'call' trick because in FF everything already works in the right way
    }

    var modalWin = new CreateModalPopUpObject();

    function closefunc(obj) {
        checkout.setLoadWaiting(false);
        modalWin.HideModalPopUp();
    }

    function reviewstep() {
    }

    function ShowMessage(content, wtitle) {

        if (typeof modalWin == 'undefined') {
            modalWin = new CreateModalPopUpObject();
        }

        if (navigator.appName != 'Microsoft Internet Explorer') {
            if (matchMedia('(max-width: 800px)').matches) {
                modalWin.ShowMessage(content, 390, 250, wtitle);
            } else if (matchMedia('(min-width: 800px)').matches) {
                modalWin.ShowMessage(content, 280, 500, wtitle);
            }
        } else {
            modalWin.ShowMessage(content, 280, 500, wtitle);
        }
    }

    AddEvent(window, 'resize', function () {
        if (popupshowed) {
            if (navigator.appName != 'Microsoft Internet Explorer') {
                if (matchMedia('(min-width: 500px) and (max-width: 800px)').matches) {
                    modalWin.ResizePopUp(390, 250);
                } else if (matchMedia('(min-width: 800px)').matches) {
                    modalWin.ResizePopUp(280, 500);
                }
            } else {
                modalWin.ResizePopUp(280, 500);
            }
        }
    });
    function addTerms(){
        jQuery(document).Terms('villkor',{invoicefee:0}, '#terms');
        jQuery(document).Terms('villkor_delbetalning',{eid: PARTPAYMENT_EID, effectiverate:34},'#terms-partpay');
    }

    AddEvent(window,'load',function(){
        jQuery.getScript('https://billmate.se/billmate/base_jquery.js',function(){addTerms();})

        // is there Radios for Payment methods? In One page checkout they exists
        if($('input[name="id_payment_method"]')) {
            try {
                var selectedMethod = $('input[name="id_payment_method"]:checked').val();
                if(typeof selectedMethod != 'undefined') {
                    if ($('#' + selectedMethod).parent('.payment_module').children('.payment-form')) {
                        var el = $('#' + selectedMethod).parent('.payment_module').children('.payment-form');
                        var method = el.attr('id');
                        //method = method.replace('-fields','');

                        if (typeof method != 'undefined') {
                            var methodName = method.replace('-fields', '');

                            if (!$('#payment_' + selectedMethod).parents('.item,.alternate_item').hasClass('fields'))
                                $('#payment_' + selectedMethod).parents('.item,.alternate_item').addClass('fields');

                            $('#' + selectedMethod).parent('.payment_module').children('.payment-form').appendTo($('.cssback.' + methodName).parents('.item,.alternate_item').children('.payment_description'));
                            $('#' + method).show();

                            $('#' + methodName + 'Submit').hide();
                            $checkoutbtn = $('.confirm_button')[1].onclick;
                            $('.confirm_button')[1].onclick = function (e) {
                                $('#' + methodName + 'Submit').click();
                                e.preventDefault();
                            }
                        }
                    }
                }


            }catch(err){
                console.log(err);
            }
        }

    });
    function billmateOpc(){

    }
    AddEvent(window,'ready',function(){

    })
    function ShowDivInCenter(divId) {
        try {
            var div = document.getElementById(divId);
            divWidth = document.getElementById("divFrameParent").offsetWidth;
            divHeight = document.getElementById("divFrameParent").offsetHeight;

            // Get the x and y coordinates of the center in output browser's window
            var centerX, centerY;
            if (self.innerHeight) {
                centerX = self.innerWidth;
                centerY = self.innerHeight;
            }
            else if (document.documentElement && document.documentElement.clientHeight) {
                centerX = document.documentElement.clientWidth;
                centerY = document.documentElement.clientHeight;
            }
            else if (document.body) {
                centerX = document.body.clientWidth;
                centerY = document.body.clientHeight;
            }

            var offsetLeft = (centerX - divWidth) / 2;
            var offsetTop = (centerY - divHeight) / 2;

            // The initial width and height of the div can be set in the
            // style sheet with display:none; divid is passed as an argument to // the function
            var ojbDiv = document.getElementById(divId);

            left = (offsetLeft) / 2 + window.scrollX;
            top = (offsetTop) / 2 + window.scrollY;

            ojbDiv.style.position = 'absolute';
            ojbDiv.style.top = top + 'px';
            ojbDiv.style.left = offsetLeft + 'px';
            ojbDiv.style.display = "block";

        }
        catch (e) {
        }
    }

    versionCompare = function (left, right) {
        if (typeof left + typeof right != 'stringstring')
            return false;

        var a = left.split('.')
            , b = right.split('.')
            , i = 0, len = Math.max(a.length, b.length);

        for (; i < len; i++) {
            if ((a[i] && !b[i] && parseInt(a[i]) > 0) || (parseInt(a[i]) > parseInt(b[i]))) {
                return 1;
            } else if ((b[i] && !a[i] && parseInt(b[i]) > 0) || (parseInt(a[i]) < parseInt(b[i]))) {
                return -1;
            }
        }

        return 0;
    };
    function getData(param, form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, method) {

        ShowMessage('<div class="billmate-loader"></div>', loadingWindowTitle);
        if (versionCompare(version, '1.6') == 1) {
            $('div.alert-danger').remove();
            $('#error_billmate'+method).css('padding-bottom','0px');

        } else {
            $('div.error').remove();
            $('#error_billmate'+method).css('padding-bottom','0px');

        }
        billmateprocessing = true;
        jQuery.post(ajaxurl + '&method=' + method + param, form, function (json) {
            eval('var response = ' + json);
            billmateprocessing = false;
            if (response.success) {
                if (typeof response.redirect != 'undefined') {

                    window.location.href = response.redirect;
                } else {
                    if (versionCompare(version, '1.6') == 1) {
                        $('.alert-danger').remove();
                    } else {
                        $('.error').remove();
                    }
                    if (typeof response.action != 'undefined') {

                        $.post(carrierurl, response.action, function (response) {
                            getData('&geturl=yes', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, method);
                        });
                    } else {
                        getData('&geturl=yes', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, method);
                    }
                }
            } else {
                if (typeof response.popup != 'undefined' && response.popup) {
                    ShowMessage(response.content, windowtitlebillmate);

                } else {
                    modalWin.HideModalPopUp();
                    if (versionCompare(version, '1.6') == 1) {
                        $('.alert-danger').remove();
                        $('#error_billmate'+method).css('padding-bottom','10px');

                        $('<div class="alert alert-danger">' + response.content + '</div>').insertBefore($('#error_billmate'+method).first());
                    } else {
                        $('.error').remove();
                        $('#error_billmate'+method).css('padding-bottom','10px');
                        $('<div class="error">' + response.content + '</div>').insertBefore($('#error_billmate'+method).first());
                    }
                }
            }
        });


    }

    function Action1() {
        alert('Action1 is excuted');
        modalWin.HideModalPopUp();
    }

    function Action2() {
        alert('Action2 is excuted');
        modalWin.HideModalPopUp();
    }

    function EnrollNow(msg) {
        modalWin.HideModalPopUp();
        modalWin.ShowMessage(msg, 200, 400, 'User Information', null, null);
    }

    function EnrollLater() {
        modalWin.HideModalPopUp();
        modalWin.ShowMessage(msg, 200, 400, 'User Information', null, null);
    }

}
