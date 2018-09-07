<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['WHITEBOARD_ENABLED']) {
    if (cfr('WHITEBOARD')) {

        $whiteboard = new WhiteBoard();

        if (wf_CheckPost(array('createnewrecord'))) {
            $whiteboard->createRecord();
            rcms_redirect($whiteboard::URL_ME);
        }
        //debarr($whiteboard);
        show_window('', $whiteboard->renderControls());
        if (!wf_CheckGet(array('showrecord'))) {
            show_window(__('Whiteboard'), $whiteboard->renderRecordsList());
        } else {
            show_window(__('Edit'), $whiteboard->renderRecord($_GET['showrecord']));
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}