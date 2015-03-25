<p class="text">
    <span>{l s='Personal / Corporate number' mod='billmategateway'}</span>
    <input type="text" id="pno" name="pno"/>
    <button id="getaddress">{l s="Get Address" mod='billmategateway'}</button>
</p>
<script type="text/javascript">
    var getaddressurl = "{$link->getModuleLink('billmategateway','getaddress', ['ajax'=> 0], true)}";
    var errormessage = '{l s='We couldnt find your address, please enter manually' mod='billmategateway'}';
    {literal}
    $(document).ready(function(){
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
                            $('input[name="lastname"]').val(response.data.lastname);
                            $('input[name="company"]').val(response.data.company);
                            $('input[name="address1"]').val(response.data.street);
                            $('input[name="city"]').val(response.data.city);
                            $('input[name="postcode"]').val(response.data.zip);
                            $('input[name="id_country"]').val(response.data.id_country);
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