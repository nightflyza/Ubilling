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