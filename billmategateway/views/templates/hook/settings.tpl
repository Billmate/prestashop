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

            {if $setting.type == 'table'}
                <h4>{$setting.label}</h4>
                <table border="1px">
                    <thead>
                        <tr>
                            <td>{l s='Paymentplanid' mod='billmategateway'}</td>
                            <td>{l s='Description' mod='billmategateway'}</td>
                            <td>{l s='Months' mod='billmategateway'}</td>
                            <td>{l s='Start fee' mod='billmategateway'}</td>
                            <td>{l s='Handling fee' mod='billmategateway'}</td>
                            <td>{l s='Min. amount' mod='billmategateway'}</td>
                            <td>{l s='Max. amount' mod='billmategateway'}</td>
                            <td>{l s='Interest rate' mod='billmategateway'}</td>
                            <td>{l s='Expiry date' mod='billmategateway'}</td>
                            <td>{l s='Country' mod='billmategateway'}</td>
                            <td>{l s='Language' mod='billmategateway'}</td>
                            <td>{l s='Currency' mod='billmategateway'}</td>
                        </tr>
                    </thead>
                    <tbody>
                    {if $setting.pclasses != false}
                        {foreach $setting.pclasses as $pclass}
                            <tr>
                                <td>{$pclass.paymentplanid}</td>
                                <td>{$pclass.description}</td>
                                <td>{$pclass.nbrofmonths}</td>
                                <td>{$pclass.startfee}</td>
                                <td>{$pclass.handlingfee}</td>
                                <td>{$pclass.minamount}</td>
                                <td>{$pclass.maxamount}</td>
                                {assign var="rate" value=$pclass.interestrate}
                                <td>{math equation="$rate * 100"}</td>
                                <td>{$pclass.expirydate}</td>
                                <td>{$pclass.country}</td>
                                <td>{$pclass.language}</td>
                                <td>{$pclass.currency}</td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="12">{l s='No paymentplans found, please save settings' mod='billmategateway'}</td>
                        </tr>
                    {/if}

                    </tbody>
                </table>
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
