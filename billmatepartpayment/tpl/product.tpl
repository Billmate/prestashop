{*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @version  Release: $Revision: 14011 $
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<link media="all" type="text/css" rel="stylesheet" href="{$productcss}">
<br />
{if $country == 'NL'}
<img style="float:right;margin-bottom:15px;width:160px;" src="./modules/billmatepartpayment/img/warning-product.jpg" alt="warning dutch"/>
<br />
{/if}
<div style="display: block;" class="billmate_PPBox" id="billmate_PPBox">
    <div style="display: none" id="billmate_partpayment"></div>
    <div class="billmate_PPBox_inner">
         <div class="billmate_PPBox_top">
            <div class="billmate_PPBox_topRight"></div>
            <div class="billmate_PPBox_topLeft"></div>
            <div class="billmate_PPBox_topMid">
              <span>{l s='From' mod='billmatepartpayment'}<label>{displayPrice price=$minValue}</label>{l s='Month' mod='billmatepartpayment'}*</span>
            </div>
         </div>
         <div class="billmate_PPBox_bottom">
            <div class="billmate_PPBox_bottomMid">
                <table cellspacing="0" cellpadding="0" width="100%" border="0">
                    <thead>
                        <tr>
                            <th class="billmate_column_left"></th>
                            <th class="billmate_column_right">{l s='Total/Month' mod='billmatepartpayment'}</th>
                        </tr>
                    </thead>
                    <tbody>
		      {foreach from=$accountPrices item=price}
                        <tr>
                            <td class="billmate_column_left">
			    {$price.description}
                            </td>
                            <td class="billmate_column_right billmate_PPBox_pricetag">
                                {displayPrice price=$price.price}
                            </td>
                        </tr>
			{/foreach}
                    </tbody>
                  </table>
                  <div class="billmate_PPBox_bottomMid_readMore"><a id="billmate-link-dynamic" class="billmate-link" href="#">{l s='Read More' mod='billmatepartpayment'}</a></div>
                <div id="billmate_PPBox_pullUp" class="billmate_PPBox_pull">
                    <div class="billmate_PPBox_pull_img"></div>
                </div>
              </div>
              <div id="billmate_PPBox_pullDown" class="billmate_PPBox_pull">
                  <div class="billmate_PPBox_pullDown_img"></div>
              </div>
              <div class="bannerhook"></div>
         </div>
    </div>
</div>

<div id="billmate_terms_condition" style="display:none;position:absolute;top:30px;left:50%;margin-left:-300px;background-color: #FFFFFF;border: 1px solid black;border-radius: 2px 2px 2px 2px;box-shadow: 4px 4px 4px #888888;padding: 0 0 10px;z-index: 9999;"><iframe style="width: 550px;height:680px;border:0" src="{$linkTermsCond}"></iframe><br/><p style="cursor:pointer" onclick="closeIframe('billmate_terms_condition')">{l s='Close' mod='billmatepartpayment'}</p></div>
<script type="text/javascript">
  $(document).ready(function()
  {
  $('#billmate-link-dynamic').attr('href', 'Javascript:void(0)');
  $('#billmate-link-dynamic').click(function(){
  $("#billmate_terms_condition").show();
  });
  });
  function closeIframe(id)
  {
  $('#'+id).hide();
  }
</script>

<script type="text/javascript">
  $('.billmate_PPBox_top,.billmate_PPBox_pullDown_img,#billmate_PPBox_pullUp').click(function(){
  $('.billmate_PPBox_bottomMid').slideToggle();
  $('.billmate_PPBox_pullDown_img').slideToggle();
  });
  
</script>
