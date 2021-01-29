<div class="container billmate">
    <div class="row">
        <div class="col-sm-12">
            <section class="card">
                <div class="card-block">
                    <h3 class="h1 card-title">
                        {l s='Tack för din beställning'}
                    </h3>
                    <p>
                        {l s='Vi har tagit emot din beställning och behandlar för tillfället din betalning.'}<br>
                        {l s='När din betalning är godkänd kommer vi skicka en orderbekräftelse till din e-postadress.'}<br>
                        {l s='Om du inte får någon bekräftelse inom ett par timmar är du välkommen att kontakta vår kundtjänst.'}
                    </p>
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
