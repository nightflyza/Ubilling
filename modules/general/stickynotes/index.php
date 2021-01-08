<?php

if (cfr('STICKYNOTES')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['STICKY_NOTES_ENABLED']) {
        //creating main object
        $stickyNotes = new StickyNotes(false);

        //control panel display
        show_window('', $stickyNotes->panel());

        //sticky notes management
        if (!wf_CheckGet(array('revelations'))) {
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
                    $noteData = $stickyNotes->getNoteData($_GET['shownote']);
                    $noteParams = '';
                    if (!empty($noteData['reminddate'])) {
                        $noteParams .= ' / ' . __('Remind time') . ': ' . $noteData['reminddate'];
                    }

                    if (!empty($noteData['remindtime'])) {
                        $noteParams .= ' ' . $noteData['remindtime'];
                    }
                    show_window(__('Sticky note') . $noteParams, $stickyNotes->renderNote($_GET['shownote']));
                }

                //note editing interface
                if (ubRouting::checkGet('editform')) {
                    $editNoteId = ubRouting::get('editform', 'int');
                    show_window(__('Edit'), $stickyNotes->editForm($editNoteId, true));
                    //some controls here
                    show_window('', wf_BackLink($stickyNotes::URL_ME) . ' ' . $stickyNotes->getEditFormDeleteControls($editNoteId));
                }
            }
        } else {
            //revelations management
            if (cfr('REVELATIONS')) {
                //new revelation creation
                if (wf_CheckPost(array('newrevelationtext'))) {
                    $stickyNotes->addMyRevelation();
                    rcms_redirect($stickyNotes::URL_REVELATIONS);
                }

                //revelation deletion
                if (wf_CheckGet(array('deleterev'))) {
                    $stickyNotes->deleteRevelation($_GET['deleterev']);
                    rcms_redirect($stickyNotes::URL_REVELATIONS);
                }

                //revelation editing
                if (wf_CheckPost(array('editrevelationtext', 'editrevelationid'))) {
                    $stickyNotes->saveMyRevelation();
                    rcms_redirect($stickyNotes::URL_REVELATIONS);
                }
            }

            if (wf_CheckGet(array('editrev'))) {
                show_window(__('Edit'), $stickyNotes->revelationEditForm($_GET['editrev']));
            } else {
                //rendering existing list
                show_window(__('Revelations'), $stickyNotes->renderRevelationsList());
            }
        }
    } else {

        show_window(__('Error'), __('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>