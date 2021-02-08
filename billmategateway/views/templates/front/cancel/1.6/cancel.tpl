{extends file='page.tpl'}

{block name='page_content_container' prepend}
    <section class="card">
        <div class="card-block">
            <div class="row">
                <div class="col-md-12">
                    <h3 class="h1 card-title">
                        {l s='Betalningen avbröts'}
                    </h3>
                </div>
            </div>
        </div>
    </section>
{/block}

{block name='page_content_container'}
    <section id="content" class="page-content card">
        <div class="card-block">
            <div class="row">
                <div class="col-md-12">
                    <p>
                        {l s='Betalningen avbröts antingen av dig eller så uppstod det ett tekinskt problem.'}<br>
                        {l s='Försök igen eller välj ett annat betalalterntiv. Om problemet kvarstår är du välkommen att kontakta vår kundtjänst.'}
                    </p>
                </div>
            </div>
        </div>
    </section>
{/block}
