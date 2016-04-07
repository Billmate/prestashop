{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}
<div class="text form-group">
    <label>{l s='Social Security Number / Corporate Registration number' mod='billmategateway'}</label>
    <div style="clear:both"></div>
    <input type="text" id="pno" class="text form-control" name="pno" value="{$pno}" style="float: left;width: 55%;max-width: 158px;"/>
    <button style="float:left;margin-bottom: 1%;" id="getaddress" class="btn btn-default button button-small"><span>{l s='Get address' mod='billmategateway'}</span></button>
</div>
<div style="clear:both"></div>
<script type="text/javascript">

    var getaddressurl = "{$link->getModuleLink('billmategateway','getaddress', ['ajax'=> 0], true)}";
    var errormessage = '{l s='We couldnt find your address, please enter manually' mod='billmategateway'}';
    {literal}
    $(document).ready(function(){
        if($('.pno_container')) {
            $('.pno_container').hide();
        }
        $('#getaddress').click(function(e) {
            e.preventDefault();
            var pno = $('#pno').val();
            if(pno != ''){
                $.ajax({
                    url: getaddressurl,
                    data: {pno: pno},
                    success: function(response){
                        response = JSON.parse(response);
                        if(response.success){
                            $('input[name="firstname"]').val(response.data.firstname);
                            $('input[name="firstname"]').trigger('change');
                            $('input[name="customer_firstname"]').val(response.data.firstname);
                            $('input[name="customer_firstname"]').trigger('change');
                            $('input[name="customer_lastname"]').val(response.data.lastname);
                            $('input[name="customer_lastname"]').trigger('change');
                            $('input[name="lastname"]').val(response.data.lastname);
                            $('input[name="lastname"]').trigger('change');
                            if(typeof response.data.company != 'undefined') {
                                $('input[name="company"]').val(response.data.company);
                                $('input[name="company"]').trigger('change');
                            }
                            $('input[name="address1"]').val(response.data.street);
                            $('input[name="address1"]').trigger('change');
                            $('input[name="city"]').val(response.data.city);
                            $('input[name="city"]').trigger('change');
                            $('input[name="postcode"]').val(response.data.zip);
                            $('input[name="postcode"]').trigger('change');
                            $('input[name="id_country"]').val(response.data.id_country);
                            $('input[name="id_country"]').trigger('change');
                            $('input[name="email"]').val(response.data.email);
                            $('input[name="email"]').trigger('change');
                            $('input[name="phone_mobile"]').val(response.data.phone);
                            $('input[name="phone_mobile"]').trigger('change');
                            var year = 0;
                            var month = 0;
                            var day = 0;
                            pno = pno.replace('-','');
                            if(pno.length == 10){
                                var tmpYear = pno.substring(0,2);
                                month = pno.substring(2,4);
                                day = pno.substring(4,6);
                                year = '19'+tmpYear;

                            }
                            if(pno.length == 12){
                                year = pno.substring(0,4);
                                month = pno.substring(4,6);
                                day = pno.substring(6,8);
                            }

                            month = month.replace(/^0+/, '');
                            day = day.replace(/^0+/, '');
                            if(typeof response.data.company == 'undefined') {
                                $('select[name="years"]').val(year);
                                $('select[name="years"]').trigger('change');
                                $('select[name="months"]').val(month);
                                $('select[name="months"]').trigger('change');
                                $('select[name="days"]').val(day);
                                $('select[name="days"]').trigger('change');
                            }
                            if(typeof validateAllFieldsNow == "function"){
                                validateAllFieldsNow(true);
                            }
                        } else {
                            alert(errormessage);
                        }
                    }
                })
            }
        })
    })
    {/literal}
</script>