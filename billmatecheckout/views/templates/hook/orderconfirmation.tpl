{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}

<p style="padding: 0px 12px;">{l s='Your order on' mod='billmategateway'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='billmategateway'}
    <br /><br /><span class="bold">{l s='Your order will be sent very soon.' mod='billmategateway'}</span>
    <br /><br />{l s='For any questions or for further information, please contact our' mod='billmategateway'} <a href="{$link->getPageLink('contact-form', true)}">{l s='customer support' mod='billmategateway'}</a>.
</p>