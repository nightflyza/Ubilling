<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['WHITEBOARD_ENABLED']) {
    if (cfr('WHITEBOARD')) {
        $whiteboard = new WhiteBoard();

        if (wf_CheckPost(array('createnewrecord'))) {
            $whiteboard->createRecord();
            rcms_redirect($whiteboard::URL_ME);
        }

        if (wf_CheckPost(array('editrecord'))) {
            $whiteboard->saveRecord();
            rcms_redirect($whiteboard::URL_ME . '&showrecord=' . $_POST['editrecord']);
        }

        if (wf_CheckGet(array('deleterecord'))) {
            $whiteboard->delete($_GET['deleterecord']);
            rcms_redirect($whiteboard::URL_ME);
        }

        show_window('', $whiteboard->renderControls());

        if (!wf_CheckGet(array('showrecord'))) {
            show_window(__('Whiteboard'), $whiteboard->renderRecordsList());
        } else {
            $taskFromLabel=' '.__('created by').' '.$whiteboard->getCreator($_GET['showrecord']);
            show_window(__('Task').$taskFromLabel, $whiteboard->renderRecord($_GET['showrecord']));
            show_window(__('Additional comments'), $whiteboard->adcomments->renderComments($_GET['showrecord']));
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}