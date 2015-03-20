<fieldset>
    <legend>{$moduleName}</legend>
    <div class="fieldset-wrap">
    {foreach $settings as $setting }
        {if $setting.type == 'checkbox'}
            <div class="input-row">
                <span>{$setting.label}</span>
                <input type="checkbox" value="1" {if $setting.value == 1}checked="checked"{/if} name="{$setting.name}"/>{$setting.desc}
            </div>
        {/if}
        {if $setting.type == 'text'}
            <div class="input-row">
                <span>{$setting.label}</span>
                <input type="{$setting.type}" name="{$setting.name}" id="{$setting.name}" value="{$setting.value}" />{$setting.desc}
            </div>
        {/if}
        {if $setting.type == 'select'}
            <div class="input-row">
                <span>{$setting.label}</span>
                <select {if isset($setting.id)}id="{$setting.id}"{/if} {if isset($setting.name)}name="{$setting.name}"{/if}>
                    <option>{l s='Choose' mod='billmatepayment'}</option>
                    {html_options options=$setting.options selected=$setting.value}
                </select>
            </div>
        {/if}
        {if $setting.type == 'radio'}
            <div class="input-row">
                <span>{$setting.label}</span>
                {foreach $setting.options as $key => $option}
                    <input type="radio" name="{$setting.name}" {if $setting.value == $key}checked="checked"{/if} value="{$key}" /> {$option}
                {/foreach}
            </div>
        {/if}
    {/foreach}
    </div>
</fieldset>