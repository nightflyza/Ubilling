/**
 * jQuery Capty - A Caption Plugin - http://wbotelhos.com/capty
 * ---------------------------------------------------------------------------------
 *
 * jQuery Capty is a plugin that creates captions over the images.
 *
 * Licensed under The MIT License
 *
 * @version         0.2.1
 * @since           12.18.2010
 * @author          Washington Botelho dos Santos
 * @documentation   wbotelhos.com/capty
 * @twitter         twitter.com/wbotelhos
 * @license         opensource.org/licenses/mit-license.php MIT
 * @package         jQuery Plugins
 *
 * Usage with default values:
 * ---------------------------------------------------------------------------------
 * $('#capty').capty();
 *
 * <img id="capty" src="image.jpg" alt="Super Mario Bros.&reg;" width="150" height="150"/>
 *
 */

;(function($) {

	$.fn.capty = function(settings) {
		var options = $.extend({}, $.fn.capty.defaults, settings);

		if (this.length == 0) {
			debug('Selector invalid or missing!');
			return;
		} else if (this.length > 1) {
			return this.each(function() {
				$.fn.capty.apply($(this), [settings]);
			});
		}

		var $this		= $(this),
			name		= $this.attr('name'),
			$caption	= $('<div class="' + options.cCaption + '"/>'),
			$elem		= $this;

		if ($this.parent().is('a')) {
			$elem = $this.parent();
		}

		var $image		= $elem.wrap('<div class="' + options.cImage + '"/>').parent(),
			$wrapper	= $image.wrap('<div class="' + options.cWrapper + '"/>').parent();

		$wrapper.css({
			height:		$this.height(),
			overflow:	'hidden',
			position:	'relative',
			width:		$this.width()
		});

		$caption.css({
			height:		options.height,
			opacity:	options.opacity,
			position:	'relative'
		})
		.click(function(evt) {
			evt.stopPropagation();
		})
		.appendTo($wrapper);

		if (name) {
			var $content = $(name);

			if ($content.length) {
				$content.appendTo($caption);
			} else {
				$caption.html('<span style="color: #F00;">Content invalid or missing!</span>');
			}
		} else {
			$caption.html($this.attr('alt'));
		}

		if (options.prefix) {
			$caption.prepend(options.prefix);
		}

		if (options.sufix) {
			$caption.append(options.sufix);
		}

		if (options.animation == 'slide') {
			$wrapper.hover(
				function() {
					$caption.animate({ top: (-1 * options.height) }, { duration: options.speed, queue: false });
				},
	    		function() {
					$caption.animate({ top: 0 }, { duration: options.speed, queue: false });
				}
	    	);
		} else if (options.animation == 'fade') {
			$caption.css({
				opacity:	0,
				top:		(-1 * options.height) + 'px'
			});

			$wrapper.hover(
				function() {
					$caption.stop().animate({ opacity: options.opacity }, options.speed);
				},
	    		function() {
					$caption.stop().animate({ opacity: 0 }, options.speed);
				}
	    	);
		} else if (options.animation == 'fixed') {
			$caption.css('top', (-1 * options.height) + 'px');
		} else {
			debug($this.attr('id') + ': invalid animation!');
		}

		return $this;
	};

	function debug(message) {
		if (window.console && window.console.log) {
			window.console.log(message);
		}
	};

	$.fn.capty.defaults = {
		animation:	'slide',
		cCaption:	'capty-caption',
		cImage:		'capty-image',
		cWrapper:	'capty-wrapper',
		height:		30,
		opacity:	.7,
		prefix:		'',
		speed:		200,
		sufix:		''
	};

})(jQuery);