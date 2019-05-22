{extends file='checkout/_partials/steps/addresses.tpl'}
{block name='step_content' prepend}
	<div class="text form-group">
		<label>{l s='Social Security Number / Corporate Registration number' mod='billmategateway'}</label>
		<div style="clear:both"></div>
		<input type="text" id="pno" class="text form-control" name="pno" value="{$pno}" style="float: left;width: 55%;max-width: 158px;"/>
		<button style="float:left;margin-bottom: 1%;" id="getaddress" class="btn btn-default button button-small">
			<span>{l s='Get address' mod='billmategateway'}</span>
		</button>
	</div>
	<div style="clear:both"></div>
{/block}

