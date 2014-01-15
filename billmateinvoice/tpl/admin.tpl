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

<link href="{$billmateinvoiceCss}" rel="stylesheet" type="text/css">
<script type="text/javascript">
	{literal}
	/* Fancybox */
	$('a.billmate-video-btn').live('click', function(){
		$.fancybox({
			'padding'		: 0,
			'autoScale'		: false,
			'transitionIn'	: 'none',
			'transitionOut'	: 'none',
			'title'			: this.title,
			'width'			: 640,
			'height'		: 360,
			'href'			: this.href.replace(new RegExp("([0-9])","i"),'moogaloop.swf?clip_id=$1'),
			'type'			: 'swf'
		});

		return false;
	});
	{/literal}
</script>

<div class="billmate-wrapper">	
	<ul id="menuTab">
		{foreach from=$tab item=li}
		<li id="menuTab{$li.tab}" class="menuTabButton {if $li.selected}selected{/if}"><img src="{$li.icon}" alt="{$li.title}"/> {$li.title}</li>
		{/foreach}
	</ul>
	
	<div id="tabList">
		{foreach from=$tab item=div}
		<div id="menuTab{$div.tab}Sheet" class="tabItem {if $div.selected}selected{/if}">
			{$div.content}
		</div>
		{/foreach}
	</div>
</div>
{foreach from=$js item=link}
<script type="text/javascript" src="{$link}"></script>
{/foreach}
