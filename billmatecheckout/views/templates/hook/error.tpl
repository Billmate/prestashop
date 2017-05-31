{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}
<div class="error">
    {foreach $billmateError as $error}
        {$error|escape:'html'}</br>
  {/foreach}
</div>
