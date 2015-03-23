/*
 * Created by PhpStorm.
 * User: jesper
 * Date: 15-03-17
 * Time: 13:01
 * @author Jesper Johansson jesper@boxedlogistics.se
 * @copyright Billmate AB 2015
 */

$('li[class^="billmate_flag_"]').click(function () {
    var country = $(this).attr('class').replace('billmate_flag_', '');
    $('.billmate_form_' + country).toggle();
    if ($('.billmate_form_' + country).is(":visible"))
        $('.billmate_form_' + country).append('<input type="hidden" name="activate' + country + '" value="on" id="billmate_activate' + country + '"/>');
    else
        $('#billmate_activate' + country).remove();
});
$('li.menuTabButton').click(function () {
    $('li.menuTabButton').removeClass('selected');
    $(this).addClass('selected');

    var tab = $(this).attr('id');
    $('div.tabItem').removeClass('selected');
    $('div#' + tab + 'Sheet').addClass('selected');
})
