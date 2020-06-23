$(document).ready(
  function() {
    $('form[action]').each( function () {
      $(this).submit( function(e) {
        this.submit();
        $(this).children().prop("disabled", true);
        //console.log("element_disabled");
        return false;
      })
    });
});
