{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}

<div class="jumbotron text-xs-center">  
<svg width="3em" height="3em" viewBox="0 0 16 16" class="bi bi-cart-check" fill="currentColor">
  <path style="color:green"; fill-rule="evenodd" d="M11.354 5.646a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L8 8.293l2.646-2.647a.5.5 0 0 1 .708 0z"/>
  <path fill-rule="evenodd" d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm7 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
</svg>
   <h1 class="display-3">{l s='Your order on' mod='billmategateway'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='billmategateway'}</h1>
   <hr>
   <p ><strong>{l s='Your order will be sent very soon.' mod='billmategateway'}  {$additional_order_info_html}</p>
   <hr>
   <p> {l s='For any questions or for further information, please contact our' mod='billmategateway'} </p>
   <button  class="btn btn-info"  href="{$link->getPageLink('contact-form', true)}">{l s='customer support' mod='billmategateway'}</button >
</div>