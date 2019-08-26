

$(document).ready(function() {
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
                        if (response.data.zip.match('/[0-9]{3} [0-9]{2}/g')) {
                            $('input[name="postcode"]').val(response.data.zip);
                            $('input[name="postcode"]').trigger('change');
                        }
                        else {
                            var zip = response.data.zip.replace(' ', '');
                            zip = zip.slice(0,3) + " " + zip.slice(3);
                            $('input[name="postcode"]').val(zip);
                            $('input[name="postcode"]').trigger('change');
                        }
                        $('input[name="id_country"]').val(response.data.id_country);
                        $('input[name="id_country"]').trigger('change');
                        $('input[name="email"]').val(response.data.email);
                        $('input[name="email"]').trigger('change');
                        $('input[name="phone_mobile"]').val(response.data.phone);
                        $('input[name="phone_mobile"]').trigger('change');;
                        $('input[name="phone"]').val(response.data.phone);
                        $('input[name="phone"]').trigger('change');
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