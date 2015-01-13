function showhideGlobalMenu() {
    if ($("#sidebar").is(":hidden")) {
        //showing
        $("#sidebar").fadeIn("fast");
        $("#main").css("width", "77%");
        $.cookie('globalMenuToggle', 'visible', {path: '/'});

    } else {
        //hiding
        $("#sidebar").fadeOut("fast");
        $("#main").css("width", "99%");
        $.cookie('globalMenuToggle', 'hidden', {path: '/'});


    }

}

$(document).ready(function () {
    if ($.cookie('globalMenuToggle') == 'hidden') {
        $("#sidebar").hide();
        $("#main").css("width", "99%");
    }
});