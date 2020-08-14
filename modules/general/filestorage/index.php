<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['FILESTORAGE_ENABLED']) {
    if (cfr('FILESTORAGE')) {

        if (ubRouting::checkGet(array('scope', 'itemid'))) {
            $fileStorage = new FileStorage(ubRouting::get('scope'), ubRouting::get('itemid'));

            //catch file upload request
            if (ubRouting::checkGet('uploadfile')) {
                $fileStorage->catchFileUpload();
            }

            //catch file download
            if (ubRouting::checkGet('download')) {
                $fileStorage->catchDownloadFile(ubRouting::get('download'));
            }

            //catch file deletion event
            if (ubRouting::checkGet('delete')) {
                $fileStorage->catchDeleteFile(ubRouting::get('delete'));
            }

            //show file upload form
            if (ubRouting::checkGet('mode')) {
                $modeSet = ubRouting::get('mode');

                //just file upload interface
                if ($modeSet == 'loader') {
                    show_window(__('File upload'), $fileStorage->renderUploadForm());
                }

                //listing images for some object
                if ($modeSet == 'list') {
                    show_window(__('Upload files'), $fileStorage->uploadControlsPanel());
                    show_window(__('Uploaded files'), $fileStorage->renderFilesList());
                }
            }
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>