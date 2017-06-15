$(document).ready(
  function() {
    $('form[action]').each( function () {
      this.onsubmit = function() {
        $(this).children().prop("disabled", true);
      }
    });
});
