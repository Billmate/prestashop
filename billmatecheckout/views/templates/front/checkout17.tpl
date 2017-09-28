{extends file=$layout}
{block name='content'}
<div class="container">
    {block name='cart'}
    <div id="billmate_summary">
        {include file='checkout/_partials/cart-detailed.tpl'}
    </div>
    {/block}
    <div id="shippingdiv">
        {$carrier_block nofilter}
    </div>
    <div id="checkoutdiv">
        <iframe id="checkout" src="{$billmatecheckouturl}" style="width: 100%; min-height: 800px; border:none;" sandbox="allow-same-origin allow-scripts allow-modals allow-popups allow-forms allow-top-navigation"></iframe>
        <div class="billmateoverlay"></div>
        <div class="billmateloading"></div>
    </div>

</div>
{/block}