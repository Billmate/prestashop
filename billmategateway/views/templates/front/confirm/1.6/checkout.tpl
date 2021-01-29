<div class="container billmate-checkout">
    <div class="row">
        <div class="col-sm-12">
            <section class="card">
                <div class="card-block">
                    <div id="checkoutdiv">
                        <iframe id="checkout" src="{$billmate_checkout_url}" style="width: 100%; min-height: 800px; border:none;" sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-top-navigation" scrolling="no"></iframe>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-sm-12 text-xs-center" style="margin:2.5rem 0 3rem 0;">
            <a class="btn btn-primary btn-md" role="button" href="{$link->getPageLink('index')}">
                {l s='Fortsätt handla'}
            </a>
        </div>
    </div>
</div>
