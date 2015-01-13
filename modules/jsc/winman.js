$(document).ready(function() {

    var showText = '▼';
    var hideText = '▲';

    var is_visible = false;

    $('.toggleWMAN').prev().append(' <a href="#" class="toggleLinkWMAN">' + hideText + '</a>');

    $('.toggleWMAN').show();

    $('a.toggleLinkWMAN').click(function() {

        is_visible = !is_visible;

        if ($(this).text() == showText) {
            $(this).text(hideText);
            $(this).parent().next('.toggleWMAN').slideDown('slow');
        }
        else {
            $(this).text(showText);
            $(this).parent().next('.toggleWMAN').slideUp('slow');
        }

        return false;

    });
});