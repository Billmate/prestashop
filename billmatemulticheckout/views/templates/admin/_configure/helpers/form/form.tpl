{extends file="helpers/form/form.tpl"}

{block name="label"}
    {if $input.type == 'topform'}

        <div class="panel ">
            <div class="panel-heading">
                <i class="icon-cogs"></i>     
                {l s='Setting.' mod='billmatemulticheckout'}
            </div>
            <div class="form-wrapper">
                <div class="row" style="background-color: transparent;" >
                    <div id="tab-description" class="plugin-description section "> 
                        <form action="" method="post" id="orderreferenceform">
                            <div class="form-wrapper">
                                {foreach from=$input.currencies item=ca}
                                    <div class="form-group">
                                        <label class="control-label col-lg-3">{l s='Enable Billmate Checkout by shop ' mod='billmatemulticheckout'} <strong> {$ca.name|escape:'htmlall':'UTF-8'} </strong></label>
                                        <div class="col-lg-9 ">
                                            <span class="switch prestashop-switch fixed-width-lg">
                                                <input type="radio" name="billmatemulticheckout_[{$ca.id_shop|escape:'htmlall':'UTF-8'}]" id="ref_on_{$ca.id_shop|escape:'htmlall':'UTF-8'}" value="1" {if $ca.billmatemulticheckout_ == '1'} checked="checked" {/if}>
                                                <label for="ref_on_{$ca.id_shop|escape:'htmlall':'UTF-8'}">Yes</label>
                                                <input type="radio" name="billmatemulticheckout_[{$ca.id_shop|escape:'htmlall':'UTF-8'}]" id="ref_off_{$ca.id_shop|escape:'htmlall':'UTF-8'}" value="0" {if $ca.billmatemulticheckout_ == '0'} checked="checked" {/if}>
                                                <label for="ref_off_{$ca.id_shop|escape:'htmlall':'UTF-8'}">No</label>
                                                <a class="slide-button btn"></a>
                                            </span>
                                            <p class="help-block"></p>
                                        </div>
                                    </div>
                                {/foreach}
                                <div class="col-lg-12 ">
                                    <div class="panel-footer">
                                        <button type="submit" value="1" id="module_form_submit_btn" name="submitbillmatemulticheckout" class="btn btn-default pull-right">
                                            <i class="process-icon-save"></i> {l s='Save' mod='billmatemulticheckout'}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel"></div>
    {/if}
{/block}