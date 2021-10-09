(function( $ ){
	var methods = {
		init : function( options ) {
			var settings = $.extend( {
				unbind:true,
				prevText:'Previous',
				nextText:'Next',
				loadText:'Loading...',
				errorText:'Image not Found',
				keyboard:true
			}, options);
			if(settings.unbind) {
				$(this).unbind('click');
			}
			var loaded_imgs=[];
			$(this).click(function(e) {
				var t=$(this);
				var url=t.attr('href');
				e.preventDefault();
				var div = $('<div></div>').addClass('light_container').html('<span class="light_inner"><div class="light_loading">'+settings.loadText+'</div></span><a href="javascript:;" class="light_close">x</a>');
				var di=div.find('.light_inner');
				$('body').append(div);
				di.width(di.children().first().outerWidth());
				di.height(di.children().first().outerHeight());
				// lock scroll position, but retain settings for later
				var scrollPosition = [
					self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
					self.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
				];
				var html = jQuery('html'); // it would make more sense to apply this to body, but IE7 won't have that
				html.data('scroll-position', scrollPosition);
				html.data('previous-overflow', html.css('overflow'));
				html.css('overflow', 'hidden');
				html.css('height','100%');
				window.scrollTo(scrollPosition[0], scrollPosition[1]);
				div.click(function(e) {
					var ele=$(e.target);
					var go=true;
					while(!ele.hasClass('light_container')) {
						if(ele.hasClass('light_inner')) {
							go=false;
						}
						ele=ele.parent();
					}
					if(go) {
						e.preventDefault();
						div.remove();
						// un-lock scroll position
						var html = jQuery('html');
						var scrollPosition = html.data('scroll-position');
						html.css('overflow', html.data('previous-overflow'));
						html.css('height','auto');
						window.scrollTo(scrollPosition[0], scrollPosition[1]);
						if(settings.keyboard) {
							$(document).unbind('keydown');
						}
					}
				});
				if(settings.keyboard) {
					$(document).unbind('keydown');
					$(document).keydown(function(e) {
						if(e.keyCode==27) {
							e.preventDefault();
							div.click();
						}
					});
				}
				var img=$('<img />').attr('src',url);
				img.load(function(e) {
					var found=false;
					$.each(loaded_imgs, function( index, value ) {
						if(url==value[0]) {
							found=true;
						}
					});
					if(found===false) {
						loaded_imgs.push([url,true]);
					}
					di.html(img);
					var w=$( window ).width()-20;
					var h=$( window ).height()-20;
					img.css({'max-width':w,'max-height':h,'width':'auto','height':'auto'})
					di.width(di.children().first().outerWidth());
					di.height(di.children().first().outerHeight());
					if(t.attr('data-caption')!==undefined) {
						di.append('<div class="light_caption"><div class="light_caption_inner">'+t.attr('data-caption')+'</div></div>');
					}
					if(t.attr('data-gallery')!==undefined) {
						var my_gallery=[];
						var size=-1;
						var ix=-1;
						var gallery_id=t.attr('data-gallery');
						$('[data-gallery='+gallery_id+']').each(function(index) {
							my_gallery.push($(this));
							size++;
							if($(this)[0]==t[0]) {
								ix=size;
							}
						});
						if(size>0) {
							di.append('<div class="light_nav"><a href="javascript:;" class="light_prev">'+settings.prevText+'</a><a href="javascript:;" class="light_next">'+settings.nextText+'</a></div>');
							di.find('.light_prev').click(function(e) {
								e.preventDefault();
								ix--;
								if(ix<0) {
									ix=size;
								}
								div.click();
								my_gallery[ix].click();
							});
							di.find('.light_next').click(function(e) {
								e.preventDefault();
								ix++;
								if(ix>size) {
									ix=0;
								}
								div.click();
								my_gallery[ix].click();
							});
							if(settings.keyboard) {
								$(document).unbind('keydown');
								$(document).keydown(function(e) {
									if(e.keyCode==27) {
										e.preventDefault();
										div.click();
									}
									if (e.keyCode == 37) {
										e.preventDefault();
										ix--;
										if(ix<0) {
											ix=size;
										}
										div.click();
										my_gallery[ix].click();
									}
									if (e.keyCode == 39) {
										e.preventDefault();
										ix++;
										if(ix>size) {
											ix=0;
										}
										div.click();
										my_gallery[ix].click();
									}
								});
							}
						}
					}
				}).error(function() {
					var found=false;
					$.each(loaded_imgs, function( index, value ) {
						if(url==value[0]) {
							found=true;
						}
					});
					if(found===false) {
						loaded_imgs.push([url,false]);
					}
					di.width(500);
					di.html('<div class="light_error">'+settings.errorText+'</div>');
					di.width(di.children().first().outerWidth());
					di.height(di.children().first().outerHeight());
				});
				$.each(loaded_imgs, function( index, value ) {
					if(url==value[0]) {
						if(value[1]) {
							img.load();
						}else{
							img.error();
						}
					}
				});
			});
		}
	};
	$.fn.light = function( method ) {
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		}else{
			$.error( 'Method ' +  method + ' does not exist on jQuery.light' );
		}
	};
})( jQuery );
