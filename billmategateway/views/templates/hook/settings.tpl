{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}
<fieldset>
    <legend>{$moduleName|escape:'html'}</legend>
    <div class="fieldset-wrap">
        {foreach $settings as $setting }
            {if $setting.type == 'checkbox'}
                <div class="input-row">
                    <span>{$setting.label|escape:'html'}</span>
                    <input type="checkbox" value="1" {if $setting.value == 1}checked="checked"{/if}
                           name="{$setting.name|escape:'html'}"/>{$setting.desc|escape:'html'}
                </div>
            {/if}
            {if $setting.type == 'text'}
                <div class="input-row">
                    <span>{$setting.label|escape:'html'}</span>
                    <input type="{$setting.type|escape:'html'}" name="{$setting.name|escape:'html'}" id="{$setting.name|escape:'html'}"
                           value="{$setting.value|escape:'html'}"/>{$setting.desc|escape:'html'}
                </div>
            {/if}
            {if $setting.type == 'select'}
                <div class="input-row">
                    <span>{$setting.label|escape:'html'}</span>
                    <select {if isset($setting.id)}id="{$setting.id}"{/if} {if isset($setting.name)}name="{$setting.name}"{/if}>
                        <option>{l s='Choose' mod='billmategateway'}</option>
                        {html_options options=$setting.options selected=$setting.value|escape:'html'}
                    </select>
                </div>
            {/if}
            {if $setting.type == 'radio'}
                <div class="input-row">
                    <span>{$setting.label|escape:'html'}</span>
                    {foreach $setting.options as $key => $option}
                        <input type="radio" name="{$setting.name}" {if $setting.value == $key}checked="checked"{/if}
                               value="{$key|escape:'html'}"/>
                        {$option|escape:'html'}
                    {/foreach}
                </div>
            {/if}
            {if $setting.type == 'multiselect'}
                <div class="input-row" {if isset($setting.id)} id="{$setting.id}"{/if} {if $setting.name == 'activateStatuses' && $setting.value == 0}style="display:none;"{/if}>
                    <span>{$setting.label|escape:'html'}</span>
                    <select multiple="multiple" {if isset($setting.id)}id="{$setting.id}
                    "{/if} {if isset($setting.name)}name="{$setting.name}"{/if}>
                    {html_options options=$setting.options selected=$setting.value|escape:'html'}
                    </select>
                </div>
            {/if}
        {/foreach}
    </div>
</fieldset>
<script type="text/javascript">
    $(document).ready(function () {
        $('input[name="activate"]').change(function () {
            $('#activation_options').toggle();
        })
    });
</script>
