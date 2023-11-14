<?php

if (cfr('STICKYNOTES')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['STICKY_NOTES_ENABLED']) {
        //creating main object
        $stickyNotes = new StickyNotes(false);

        //control panel display
        show_window('', $stickyNotes->panel());

        //sticky notes management
        if (!ubRouting::checkGet($stickyNotes::ROUTE_REVELATIONS)) {
            //custom return URLs?
            $backUrl = '';
            if (ubRouting::get($stickyNotes::ROUTE_BACK) == 'calendar') {
                $backUrl .= '&' . $stickyNotes::ROUTE_CALENDAR . '=true';
            }

            // new note creation
            if (ubRouting::checkPost($stickyNotes::PROUTE_NEW_NOTE)) {
                $stickyNotes->addMyNote();
                ubRouting::nav($stickyNotes::URL_ME . $backUrl);
            }

            //note deletion
            if (ubRouting::checkGet($stickyNotes::ROUTE_DEL_NOTE)) {
                $stickyNotes->deleteNote(ubRouting::get($stickyNotes::ROUTE_DEL_NOTE));
                ubRouting::nav($stickyNotes::URL_ME . $backUrl);
            }

            //note editing
            if (ubRouting::checkPost(array($stickyNotes::PROUTE_EDIT_NOTE_TEXT, $stickyNotes::PROUTE_EDIT_NOTE_ID))) {
                $stickyNotes->saveMyNote();
                ubRouting::nav($stickyNotes::URL_ME . $backUrl);
            }

            if ((!ubRouting::checkGet($stickyNotes::ROUTE_SHOW_NOTE)) AND (!ubRouting::checkGet($stickyNotes::ROUTE_EDIT_FORM))) {
                //grid or calendar view switch
                if (!ubRouting::checkGet($stickyNotes::ROUTE_CALENDAR)) {
                    show_window(__('Available personal notes'), $stickyNotes->renderListGrid());
                } else {
                    show_window(__('Available personal notes'), $stickyNotes->renderListCalendar());
                }
            } else {
                //rendering full note content
                if (ubRouting::checkGet($stickyNotes::ROUTE_SHOW_NOTE)) {
                    $noteData = $stickyNotes->getNoteData(ubRouting::get($stickyNotes::ROUTE_SHOW_NOTE));
                    $noteParams = '';
                    if (!empty($noteData[$stickyNotes::PROUTE_REMIND_DATE])) {
                        $noteParams .= ' / ' . __('Remind time') . ': ' . $noteData[$stickyNotes::PROUTE_REMIND_DATE];
                    }

                    if (!empty($noteData[$stickyNotes::PROUTE_REMIND_TIME])) {
                        $noteParams .= ' ' . $noteData[$stickyNotes::PROUTE_REMIND_TIME];
                    }
                    show_window(__('Sticky note') . $noteParams, $stickyNotes->renderNote(ubRouting::get($stickyNotes::ROUTE_SHOW_NOTE)));
                }

                //note editing interface
                if (ubRouting::checkGet($stickyNotes::ROUTE_EDIT_FORM)) {
                    $editNoteId = ubRouting::get($stickyNotes::ROUTE_EDIT_FORM, 'int');
                    show_window(__('Edit'), $stickyNotes->editForm($editNoteId, true));
                    //some controls here
                    show_window('', wf_BackLink($stickyNotes::URL_ME) . ' ' . $stickyNotes->getEditFormDeleteControls($editNoteId));
                }
            }
        } else {
            //revelations management
            if (cfr('REVELATIONS')) {
                //new revelation creation
                if (ubRouting::checkPost($stickyNotes::PROUTE_NEW_REVELATION)) {
                    $stickyNotes->addMyRevelation();
                    ubRouting::nav($stickyNotes::URL_REVELATIONS);
                }

                //revelation deletion
                if (ubRouting::checkGet($stickyNotes::ROUTE_DEL_REV)) {
                    $stickyNotes->deleteRevelation(ubRouting::get($stickyNotes::ROUTE_DEL_REV));
                    ubRouting::nav($stickyNotes::URL_REVELATIONS);
                }

                //revelation editing
                if (ubRouting::checkPost(array($stickyNotes::PROUTE_EDIT_REV_TEXT, $stickyNotes::PROUTE_EDIT_REV_ID))) {
                    $stickyNotes->saveMyRevelation();
                    ubRouting::nav($stickyNotes::URL_REVELATIONS);
                }
            }

            if (ubRouting::checkGet(array('editrev'))) {
                show_window(__('Edit'), $stickyNotes->revelationEditForm(ubRouting::get($stickyNotes::ROUTE_EDIT_REV_FORM)));
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
