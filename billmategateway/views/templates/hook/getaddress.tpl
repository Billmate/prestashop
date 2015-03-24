<p class="text">
    <span>{l s='Personal / Corporate number' mod='billmategateway'}</span>
    <input type="text" id="pno" name="pno"/>
    <button id="getaddress">{l s="Get Address" mod='billmategateway'}</button>
</p>
<script type="text/javascript">
    var ajaxurl = '{$link->getModuleLink('billmategateway','getaddress')}';
    $(document).ready(function(){
        $('#getaddress').click(function(e) {
            e.preventDefault();
            var pno = $('#pno').val();
            if(pno != ''){
                $.ajax({
                    url: ajaxurl,
                    data: {pno: pno},
                    success: function(response){
                        if(response.success){

                        }
                    }
                })
            }
        })
    })
</script>