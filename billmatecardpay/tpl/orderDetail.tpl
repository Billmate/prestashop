
<script type="text/javascript">

$(document).ready(function(){
    $('td[class=history_method]').each(function(){
	if ($(this).html() == "{$moduleName}")
	{
	    $(this).next().next().html('-');
	    if ($(this).next().html() == '{$wrongText}')
		$(this).next().html('{$validateText}');
	}
    });
});
</script>
