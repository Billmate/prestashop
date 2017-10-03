{extends file='page.tpl'}

{block name='notifications'}{/block}

{block name='page_content_container'}
    <section id="content" class="page-content">
        {block name='page_content_top'}
            {block name='customer_notifications'}
                {include file='_partials/notifications.tpl'}
            {/block}
        {/block}
        {block name='page_content'}
            <div class="container">



                    <div id="order-conf">
                        {$order_conf nofilter}

                    </div>

            </div>
        {/block}
    </section>
{/block}

