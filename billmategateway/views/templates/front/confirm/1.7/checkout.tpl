{extends file=$layout}

{block name='content'}
<div class="container billmate-checkout">
    <div class="row">
        <div class="col-sm-12">
            <section class="card">
                <div class="card-block">
                    <div id="checkoutdiv">
                        <iframe id="checkout" src="{$billmatecheckouturl}" style="width: 100%; min-height: 800px; border:none;" sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-top-navigation" scrolling="no"></iframe>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
{/block}
