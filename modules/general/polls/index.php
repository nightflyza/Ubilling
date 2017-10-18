<?php
$altcfg = $ubillingConfig->getAlter();

if ($altcfg['POLLS_ENABLED']) {
    if (cfr('POLLS')) {
    $polls = new Polls();

    //getting polls data
    if (wf_CheckGet(array('ajaxavaiblepolls'))) {
        $polls->ajaxAvaiblePolls();
    }

    //show polls control panel
    show_window('', $polls->panel());

        // show form for create poll
        if (wf_CheckGet(array('action'))) {
                if (cfr('POLLSCONFIG')) {
                    // create new poll
                    if ($_GET['action'] == 'create_poll') {

                        if (wf_CheckPost(array('createpoll'))) {
                            show_window('', $polls->controlPoll($_POST['createpoll']));
                        }
                        show_window('Create poll', $polls->renderFormPoll());
                    }
                    // edit poll
                    if ($_GET['action'] == 'edit_poll') {
                        if (wf_CheckPost(array('editpoll'))) {
                            show_window('', $polls->controlPoll($_POST['editpoll']));
                        }
                        show_window('Edit poll', $polls->renderFormPoll());
                    }
                    // create or edit poll options
                    if ($_GET['action'] == 'polloptions') {
                        if (wf_CheckPost(array('polloptions'))) {
                            show_window('', $polls->controlPollOptions($_POST['polloptions']));
                        }
                         show_window('Configure poll options', $polls->renderFormPollOption());
                    }
                    // delete poll
                    if ($_GET['action'] == 'delete_poll') {
                        $polls->deletePollData();
                    }
                } else {
                    show_error(__('Access denied'));
                }
        } elseif (wf_CheckGet(array('show_options'))) {
                show_window('Preview poll form', $polls->renderPreviewPollOption());
        } else {
            show_window('Avaible polls', $polls->renderAvaiblePolls());
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('Polls now disabled'));
}

?>
