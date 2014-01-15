
<link href="{$billmatebankcss}" rel="stylesheet" type="text/css">
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
