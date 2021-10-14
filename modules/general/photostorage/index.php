<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['PHOTOSTORAGE_ENABLED']) {
    if (cfr('PHOTOSTORAGE')) {


        if (wf_CheckGet(array('scope', 'itemid'))) {
            $photoStorage = new PhotoStorage($_GET['scope'], $_GET['itemid']);

            //catch ajax webcam upload request
            if (wf_CheckGet(array('uploadcamphoto'))) {
                $photoStorage->catchWebcamUpload();
            }

            //catch file upload request
            if (wf_CheckGet(array('uploadfilephoto'))) {
                $customBackLink = ubRouting::get('custombacklink');
                $photoStorage->catchFileUpload($customBackLink);
            }

            //catch file download
            if (wf_CheckGet(array('download'))) {
                $photoStorage->catchDownloadImage($_GET['download']);
            }

            //catch file deletion event
            if (wf_CheckGet(array('delete'))) {
                $photoStorage->catchDeleteImage($_GET['delete']);
            }

            //show webcam snapshot form
            if (wf_CheckGet(array('mode'))) {
                $modeSet = $_GET['mode'];
                //webcamera snapshot
                if ($modeSet == 'cam') {
                    show_window(__('Webcamera snapshot'), $photoStorage->renderWebcamForm(false));
                }
                //webcamera cropped snapshot
                if ($modeSet == 'avacam') {
                    show_window(__('Webcamera snapshot') . ' - ' . __('avatar'), $photoStorage->renderWebcamForm(true));
                }
                //just file upload interface
                if ($modeSet == 'loader') {
                    show_window(__('Upload images'), $photoStorage->renderUploadForm());
                }

                //listing images for some object
                if ($modeSet == 'list') {
                    show_window(__('Upload images'), $photoStorage->uploadControlsPanel());
                    show_window(__('Uploaded images'), $photoStorage->renderImagesList());
                }
            }
        } else {
            // rendering uploaded images gallery
            $photostorage = new PhotoStorage('GALLERY', 'nope');
            show_window(__('Uploaded images'), $photostorage->renderScopesGallery(12, true));
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>