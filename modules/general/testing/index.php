<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {



    $jsGallery = wf_tag('link', false, '', 'rel="stylesheet" href="modules/jsc/image-gallery-lightjs/src/jquery.light.css"');
    $jsGallery .= wf_tag('script', false, '', 'src="modules/jsc/image-gallery-lightjs/src/jquery.light.js"') . wf_tag('script', true);



    $jsGallery .= '<a href="https://source.unsplash.com/EVXB_Is-UqI/1200x900" data-caption="This is my caption1" data-gallery="1" rel="photostoragegallery"><img src="https://source.unsplash.com/EVXB_Is-UqI/300x200"></a>
			<a href="https://source.unsplash.com/YXDTQ4e5wDo/1200x900" data-caption="This is my caption" data-gallery="1" rel="photostoragegallery"><img src="https://source.unsplash.com/YXDTQ4e5wDo/300x200"></a>
			<a href="https://source.unsplash.com/IwVRO3TLjLc/1200x900" data-caption="This is my caption" data-gallery="1" rel="photostoragegallery"><img src="https://source.unsplash.com/IwVRO3TLjLc/300x200"></a>';
    
    $jsGallery.= wf_tag('script');
    $jsGallery .= "
        $('a[rel=photostoragegallery]').light({
        unbind:true,
        prevText:'" . __('Previous') . "', 
        nextText:'" . __('Next') . "',
        loadText:'" . __('Loading') . "...',
        keyboard:true
    });
    ";
    $jsGallery.= wf_tag('script',true);

    deb($jsGallery);
}