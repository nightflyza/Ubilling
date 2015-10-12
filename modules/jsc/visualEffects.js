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

function getParameters() {
    var searchString = window.location.search.substring(1),
            params = searchString.split("&"),
            hash = {};

    if (searchString == "")
        return {};
    for (var i = 0; i < params.length; i++) {
        var val = params[i].split("=");
        hash[unescape(val[0])] = unescape(val[1]);
    }

    return hash;
}

function isset() {
    var a = arguments, l = a.length, i = 0;
    if (l === 0) {
        throw new Error('Empty isset');
    }
    while (i !== l) {
        if (typeof (a[i]) == 'undefined' || a[i] === null) {
            return false;
        } else {
            i++;
        }
    }
    return true;
}

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

