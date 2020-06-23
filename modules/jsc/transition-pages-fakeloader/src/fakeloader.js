//
// Fake Loader
//
window.FakeLoader = (function($, window, document) {

  var settings = {
    auto_hide: true,
    overlay_id: 'fakeloader-overlay',
    fade_timeout: 200,
    wait_for_images: true,
    wait_for_images_selector: 'body'
  }

  var $overlay = null;

  var fakeLoader = {

    hideOverlay: function() {
      $overlay.removeClass('visible');

      window.setTimeout(
        function() {
          $overlay.addClass('hidden');
        }, 
        settings.fade_timeout
      );
    },

    showOverlay: function() {
      $overlay.removeClass('hidden').addClass('visible');
    },

    init: function( given_settings ) {

      $.extend( settings, given_settings );

      if ( $('#' + settings.overlay_id).length <= 0 ) {
        $overlay = $('<div id="' + settings.overlay_id + '" class="visible incoming"><div class="loader-wrapper-outer"><div class="loader-wrapper-inner"><div class="loader"></div></div></div></div>');
        $('body').append($overlay);

        if (typeof(console) !== 'undefined' && typeof(console.log) !== 'undefined') {
          console.log( "You should put the fakeLoader loading overlay element in your markup directly for best results." );
        }
      }
      else {
        $overlay = $('#' + settings.overlay_id);
      }

      $overlay.click(
        function() {
          fakeLoader.hideOverlay();
        }
      );

      $(window).bind('beforeunload', function() {

        $('#' + settings.overlay_id).removeClass('incoming').addClass('outgoing');
        fakeLoader.showOverlay();

      });

      $(document).ready(
        function() {
          if ( settings.auto_hide == true ) {
            if ( typeof($.fn.waitForImages) == 'function' && settings.wait_for_images == true) {
              $(settings.wait_for_images_selector).waitForImages(
                function() {
                  fakeLoader.hideOverlay();
                }
              ) 

            }
            else {
              fakeLoader.hideOverlay();
            }
          }
        }
      );

    }

  }

  return fakeLoader;

})(jQuery, window, document);