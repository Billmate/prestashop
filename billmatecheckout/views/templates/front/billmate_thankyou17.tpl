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


                {if isset($HOOK_ORDER_CONFIRMATION)}
                    <div id="order-conf">
                        {$HOOK_ORDER_CONFIRMATION nofilter}

                    </div>
                {/if}
            </div>
        {/block}
    </section>
{/block}

