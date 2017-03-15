$(function() {
    $("tr.row1 input:checkbox").change(function() {
        if($(this).is(":checked")) {
            $("tr.row3 input:checkbox").attr("checked", true);
        } else {
            $("tr.row3 input:checkbox").attr("checked", false);
        }
    });
});