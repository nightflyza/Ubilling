$(document).ready(function() {

    var showText = '▼';
    var hideText = '▲';

    var is_visible = false;

    $('.toggleGMENU').prev().append(' <a href="#" class="toggleLinkGMENU">' + hideText + '</a>');

    $('.toggleGMENU').show();

    $('a.toggleLinkGMENU').click(function() {

        is_visible = !is_visible;

        if ($(this).text() == showText) {
            $(this).text(hideText);
            $(this).parent().next('.toggleGMENU').slideDown('slow');
        }
        else {
            $(this).text(showText);
            $(this).parent().next('.toggleGMENU').slideUp('slow');
        }

        return false;

    });
});