$(document).ready(function () {
    $(".row4").hover(function () {
        $(this).stop().animate({color: "#B22222"}, 50);
    }, function () {
        $(this).stop().animate({color: "#000000"}, 50);
    });
});


$(document).ready(function () {
    $(".row4").click(function () {
        $(this).toggleClass("ChosenOne");
    });
});

$(document).ready(function () {
    $(".row4").dblclick(function () {
        var data = $(this).find("a").text();
        var login = $.trim(data);
        var params = decodeURIComponent(window.location.search.substring(1)),
                splited = params.split('&'),
                get = [];
        get[0] = splited[0].split('=');
        get[1] = splited[1].split('=');        
        if (get[0][1] === 'per_city_action') {
            get[2] = splited[2].split('=');
            if (get[1][1] === 'city_payments') {
                get[3] = splited[3].split('=');
                window.location.href = $.query.REMOVE(get[1][0]).REMOVE(get[2][0]).REMOVE(get[3][0]).SET("module", "userprofile").SET("username", login);
            } else {
                window.location.href = $.query.REMOVE(get[1][0]).REMOVE(get[2][0]).SET("module", "userprofile").SET("username", login);
            }
        }
    });
});

