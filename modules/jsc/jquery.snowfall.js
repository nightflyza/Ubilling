/**
 * Created by JetBrains PhpStorm.
 * User: sul4bh
 * Date: 12/17/11
 * Time: 11:44 PM
 * To change this template use File | Settings | File Templates.
 */

var intervalId;
( function ($) {
  $.fn.snowFall = function (options) {

    var settings = {
      'color': '#eee',
      'interval': 10,
      'total': 20
    };

    // Override default values if values provided
    if ( options ) {
      $.extend(settings, options);
    }

    /*
     * Helper functions starts
     */
    function init( number ){
      if ( temp.currTotal <= settings.total ) {
        for ( i = 0; i < number; i++ ) {
          var obj = $("<div class='snow'>*</div>");
          obj.css('color', settings.color);
          obj.css('position', 'absolute');
          obj.css('opacity', '0.9');
          var rand = Math.random() * 15 + 8;
          obj.css('font-size', rand);

          obj.css('top', 0);
          var random = Math.floor(Math.random() * $(window).width() - 5);
          obj.css('left', random);

          obj.data('direction', Math.floor(Math.random() * 3) - 1);
          obj.data('speed', Math.floor(Math.random() * 2) + 1);
          obj.data('iter', 0);

          $('body').append(obj);
          temp.currTotal++;
        }
      }
    }

    var temp = {};
    temp.currTotal = 0;

    //start with 5 flakes
    init(5);

    setInterval(function () {
      $('.snow').each(function () {
        var speed = $(this).data('speed');
        var iter = $(this).data('iter');
        var dirn = $(this).data('direction');
        
        $(this).data('iter', iter + 1);

        if ( speed == iter ) {
          $(this).data('iter', 0);
          var p = $(this).position();
          if ((p.top + 40) < $(window).height()) {
            $(this).css('top', p.top + 1);
          } else {
            $(this).remove();
            temp.currTotal--;
            return 0;
          }
          if ((p.left + 20) < $(document).width() && p.left > 0) {
            $(this).css('left', p.left + dirn);
          } else {
            $(this).remove();
            temp.currTotal--;
            return 0;
          }
        }
      });
    }, settings.interval
    );
    intervalId = setInterval(function () {
      init(1);
    }, 1000);
  };
  $.fn.stopFall = function () {
    clearInterval(intervalId);
  };
})(jQuery);