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
                $modeSet=$_GET['mode'];
                if ($modeSet=='cam') {
                   show_window(__('Webcamera snapshot'), $photoStorage->renderWebcamForm(false));
                }
                
                if ($modeSet=='avacam') {
                    show_window(__('Webcamera snapshot').' - '.__('avatar'), $photoStorage->renderWebcamForm(true));
                }
                
                if ($modeSet=='list') {
                    show_window(__('Uploaded images'), $photoStorage->renderImagesList());
                }
            }
        } else {
           // displaying report
           //... soon ....
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>