function showhideGlobalMenu() {
    if ($("#sidebar").is(":hidden")) {
        //showing
        $("#sidebar").fadeIn("fast");
        $("#main").css("width", "77%");
        $(".breadcrumbs_container").css("background","url(skins/ubng/images/secondary_bar_shadow.png) no-repeat left top");
        $.cookie('globalMenuToggle', 'visible', {path: '/'});

    } else {
        //hiding
        $("#sidebar").fadeOut("fast");
        $("#main").css("width", "99%");
        $(".breadcrumbs_container").css("background","url()");
        $.cookie('globalMenuToggle', 'hidden', {path: '/'});


    }

}

$(document).ready(function () {
    if ($.cookie('globalMenuToggle') == 'hidden') {
        $("#sidebar").hide();
        $("#main").css("width", "99%");
        $(".breadcrumbs_container").css("background","url()");
    }
});