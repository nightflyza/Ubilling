<?php

if (cfr('STICKYNOTES')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['STICKY_NOTES_ENABLED']) {
        //creating main object
        $stickyNotes = new StickyNotes(false);


        // new note creation
        if (wf_CheckPost(array('newtext'))) {
            $stickyNotes->addMyNote();
            rcms_redirect($stickyNotes::URL_ME);
        }

        //note deletion
        if (wf_CheckGet(array('delete'))) {
            $stickyNotes->deleteNote($_GET['delete']);
            rcms_redirect($stickyNotes::URL_ME);
        }

        //note editing
        if (wf_CheckPost(array('edittext', 'editnoteid'))) {
            $stickyNotes->saveMyNote();
            rcms_redirect($stickyNotes::URL_ME);
        }

        //control panel display
        show_window('', $stickyNotes->panel());

        if ((!wf_CheckGet(array('shownote'))) AND ( !wf_CheckGet(array('editform')))) {
            //grid or calendar view switch
            if (!wf_CheckGet(array('calendarview'))) {
                show_window(__('Available personal notes'), $stickyNotes->renderListGrid());
            } else {
                show_window(__('Available personal notes'), $stickyNotes->renderListCalendar());
            }
        } else {
            //rendering full note content
            if (wf_CheckGet(array('shownote'))) {
                show_window(__('Sticky note'), $stickyNotes->renderNote($_GET['shownote']));
            }

            //note editing interface
            if (wf_CheckGet(array('editform'))) {
                show_window(__('Edit'), $stickyNotes->editForm($_GET['editform'], true));
                show_window('', wf_BackLink($stickyNotes::URL_ME));
            }
        }
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>