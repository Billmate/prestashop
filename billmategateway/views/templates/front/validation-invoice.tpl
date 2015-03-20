{capture name=path}{l s='Billmate Invoice' mod='billmategateway'}{/capture}
<div id="order_area">
    <h2>{l s='Order summation' mod='billmategateway'}</h2>
    {assign var='current_step' value='payment'}
    {include file="$tpl_dir./order-steps.tpl"}

    <h3>{l s='Billmate Invoice Payment' mod='billmategateway'}</h3>
    <form action="javascript://" method="post" class="billmate">
        <input type="hidden" name="confirm" value="1" />
        <p>
            <img src="{$smarty.const._MODULE_DIR_}billmategateway/images/billmate_invoice_m.png" alt="{l s='Billmate Invoice Payment' mod='billmateinvoice'}" style="margin: 0px 10px 5px 0px;" />
        </p>
        <p class="blarge" style="padding-bottom:10px">
            {l s='The total amount of your order is' mod='billmategateway'}<span id="amount_{$currencies.0.id_currency}"> {convertPrice price=$total}.</span>
        </p>
        <p class="bnormal">
            {if $fee != 0}<span id="amount">{l s=' Includes an invoice fee of' mod='billmategateway'} {displayPrice price=$fee} {if $use_taxes == 1}
                ({l s='tax included' mod='billmategateway'}).<br/>
            {/if}</span>{/if}
        </p>
        <p class="bnormal">
            <b>{l s='Please fill following fields to complete your order' mod='billmategateway'}</b>
        </p>
        <p class="blarge">
            <label for="pno">{l s='Personal Number / Organization Number' mod='billmategateway'}</label>
            <input type="text" value="" id="billmate_pno" name="pno" style="border:1px solid #D3D3D3;padding:0.2em;" required  />
        </p>
        <p class="bsmall">
            <label for="confirm"><input type="checkbox" checked="checked" value="" id="confirm_my_age" class="comparator" name="confirm_my_age" required />
                {l s='My email %1$s is accurate and can be used for invoicing.' sprintf=[$customer_email] mod='billmategateway'}
                <br/> <a id="terms" class="terms" style="cursor:pointer!important;">{l s='I confirm the terms for invoice payment' mod='billmategateway'}</a></label>
        </p>
        <p>
            <input type="button" name="submit" value="{l s='I confirm my order' mod='billmategateway'}" style="width:26em!important" class="exclusive_large blarge" id="billmate_submit"/>
        </p>
        <p class="cart_navigation billfooter">
            <a href="{$previouslink}" class="billbutton blarge underline" style="float:left;line-height:1em;">{l s='Other payment methods' mod='billmategateway'}</a>
            <a id="terms" class="billbutton blarge terms underline" style="cursor:pointer!important;float:right">{l s='Terms of invoice' mod='billmategateway'}</a><script type="text/javascript">$.getScript("https://billmate.se/billmate/base.js", function(){ldelim}
                    $(".terms").Terms("villkor",{ldelim}invoicefee: {$fee}{rdelim});
                    {rdelim});</script>
        </p>
    </form>
</div>