<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['PHOTOSTORAGE_ENABLED']) {
    if (cfr('PHOTOSTORAGE')) {
        if (ubRouting::checkGet(array('scope', 'itemid'))) {
            $photoStorage = new PhotoStorage(ubRouting::get('scope'), ubRouting::get('itemid'));

            //catch ajax webcam upload request
            if (ubRouting::checkGet('uploadcamphoto')) {
                $photoStorage->catchWebcamUpload();
            }

            //catch file upload request
            if (ubRouting::checkGet('uploadfilephoto')) {
                $customBackLink = ubRouting::get('custombacklink');
                $photoStorage->catchFileUpload($customBackLink);
            }

            //catch file download
            if (ubRouting::checkGet('download')) {
                $photoStorage->catchDownloadImage(ubRouting::get('download'));
            }

            //catch file deletion event
            if (ubRouting::checkGet('delete')) {
                $photoStorage->catchDeleteImage(ubRouting::get('delete'));
            }

            //show webcam snapshot form
            if (ubRouting::checkGet('mode')) {
                $modeSet = ubRouting::get('mode');
                switch ($modeSet) {
                    //webcamera snapshot
                    case 'cam':
                        show_window(__('Webcamera snapshot'), $photoStorage->renderWebcamForm(false));
                        break;
                    //webcamera cropped snapshot
                    case 'avacam':
                        show_window(__('Webcamera snapshot') . ' - ' . __('avatar'), $photoStorage->renderWebcamForm(true));
                        break;
                    //just file upload interface
                    case 'loader':
                        show_window(__('Upload images'), $photoStorage->renderUploadForm());
                        break;
                    //listing images for some object
                    case 'list':
                        show_window(__('Upload images'), $photoStorage->uploadControlsPanel());
                        show_window(__('Uploaded images'), $photoStorage->renderImagesList());
                        break;
                }
            }
        } else {
            $photoStorage = new PhotoStorage('GALLERY', 'nope'); //just generic scope here
            //catch proxy-mode image request
            if (ubRouting::checkGet($photoStorage::ROUTE_PROXY)) {
                $photoStorage->proxyImage(ubRouting::get($photoStorage::ROUTE_PROXY));
            }
            // rendering uploaded images gallery
            show_window(__('Uploaded images'), $photoStorage->renderScopesGallery(12, true));
            zb_BillingStats(true, 'photogallery');
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
