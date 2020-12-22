{extends file='page.tpl'}

{block name='page_content_container' prepend}
    <section id="content-hook_order_confirmation" class="card">
        <div class="card-block">
            <div class="row">
                <div class="col-md-12">
                    {block name='order_confirmation_header'}
                        <h3 class="h1 card-title">
                            <i class="material-icons rtl-no-flip done">&#xE876;</i>{l s='Tack för din beställning'}
                        </h3>
                    {/block}
                    <p>
                        {l s='Vi har tagit emot din beställning och behandlar för tillfället din betalning.'}<br>
                        {l s='När din betalning är godkänd kommer vi skicka en orderbekräftelse till din e-postadress.'}<br>
                        {l s='Om du inte får någon bekräftelse inom ett par timmar är du välkommen att kontakta vår kundtjänst.'}
                    </p>
                </div>
            </div>
        </div>
    </section>
{/block}

{block name='page_content_container'}
    {block name='page_content'}
    {/block}
{/block}
