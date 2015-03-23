<div class="billmate-wrapper">
    <ul id="menuTab">
        {foreach $tab as $li}
            <li id="menuTab{$li.tab}" class="menuTabButton {if $li.selected}selected{/if}"><img src="{$li.icon}"
                                                                                                alt="{$li.title}"/> {$li.title}
            </li>
        {/foreach}
    </ul>
    <form action="{$FormCredential}" method="POST">
        <div id="tabList">

            {foreach $tab as $div}
                <div id="menuTab{$div.tab}Sheet" class="tabItem {if $div.selected}selected{/if}">
                    {$div.content}
                </div>
            {/foreach}
        </div>
        <div class="center pspace"><input type="submit" name="billmateSubmit"
                                          value="{l s='Save' mod='billmategateway'}"/></div>
    </form>
</div>
{foreach from=$js item=link}
    <script type="text/javascript" src="{$link}"></script>
{/foreach}