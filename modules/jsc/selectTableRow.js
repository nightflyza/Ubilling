$(function() {
$('tr.row1 input:checkbox').change(function() {
    var checkboxes = $('tr.row3 input:checkbox').closest('form').find(':checkbox');
    if($(this).is(':checked')) {
        checkboxes.prop('checked', true);
    } else {
        checkboxes.prop('checked', false);
    }
});
});
