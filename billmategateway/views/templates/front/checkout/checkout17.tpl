{extends file=$layout}
{block name='content'}
<div class="container billmate-checkout">
    <div class="row">

        <div class="col-md-6 col-md-push-6 col-sm-12 col-sm-push-0">
            {block name='cart_summary'}
                {include file='checkout/_partials/cart-summary.tpl' cart = $cart}
            {/block}
            <section id="js-checkout-summary" class="card js-cart">
                <div class="card-block">
                    {$carrier_block nofilter}
                </div>
            </section>
            <div class="col-md-12 visible-md visible-lg">
                {hook h='displayReassurance'}
            </div>
        </div>

        <div class="col-md-6 col-md-pull-6 col-sm-12 col-sm-pull-0">
            <section class="card">
                <div class="card-block">
                    <iframe id="checkout" src="{$billmatecheckouturl}" style="width: 100%; min-height: 800px; border:none;" sandbox="allow-same-origin allow-scripts allow-modals allow-popups allow-forms allow-top-navigation" scrolling="no"></iframe>
                </div>
            </section>
            <div class="col-md-12 visible-sm visible-xs">
                {hook h='displayReassurance'}
            </div>
        </div>

    </div>
</div>
{/block}
