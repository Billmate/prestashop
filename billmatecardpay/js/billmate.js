$('li[class^="billmate_flag_"]').click(function()
	{
		var country = $(this).attr('class').replace('billmate_flag_', '');
		$('.billmate_form_'+country).toggle();
		if ($('.billmate_form_'+country).is(":visible"))
		$('.billmate_form_'+country).append('<input type="hidden" name="activate'+country+'" value="on" id="billmate_activate'+country+'"/>');
	else
		$('#billmate_activate'+country).remove();
});
$('#billmate_activation_on').click(function(){
    $('#activationSelect').prop('disabled',false);
});
$('#billmate_activation_off').click(function(){
    $('#activationSelect').prop('disabled',true);
})
$(document).ready(function(){
    var height = 0;
    $('.billmate-blockSmall').each(function(){
	if (height < $(this).height())
	    height = $(this).height();
    });

    $('.billmate-blockSmall').css({'height' : $('.billmate-blockSmall').css('height', 10 +height+'px')});
});