<div class="switch-bm-checkout bold">
	<a href="{$link->getModuleLink('billmategateway', 'billmatecheckout', ['switch' => $switchDirection], true)}" >
        {if ((float)$PS_VERSION) >= 1.7}
            {if $page.page_name == 'module-billmategateway-billmatecheckout'}
                <span class="h6">{l s='Choose another payment method' mod='billmategateway'}</span>
            {/if}
            {if $page.page_name == 'checkout'}
                <span class="h6">{l s='Pay with Billmate Checkout' mod='billmategateway'}</span>
            {/if}
        {else}
            {if $page_name == 'module-billmategateway-billmatecheckout'}
                <span class="h6">{l s='Choose another payment method' mod='billmategateway'}</span>
            {/if}
            {if $page_name == 'checkout'}
                <span class="h6">{l s='Pay with Billmate Checkout' mod='billmategateway'}</span>
            {/if}
        {/if}
	</a>
</div>