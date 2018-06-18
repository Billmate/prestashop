<div class="container">
    <div id="billmate_summary">
        {include file="$tpl_dir/shopping-cart.tpl"}
    </div>
    <div id="shippingdiv">
        {$carrier_block}
    </div>
    <div id="checkoutdiv">
        <iframe id="checkout" src="{$billmatecheckouturl}" style="width: 100%; min-height: 800px; border:none;" sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-top-navigation" scrolling="no"></iframe>
    </div>

</div>
