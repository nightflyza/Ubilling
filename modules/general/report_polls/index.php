<?php
$altcfg = $ubillingConfig->getAlter();

if ($altcfg['POLLS_ENABLED']) {
    if (cfr('POLLSREPORT')) {
    $pollsReport = new PollsReport();

    //getting polls data
    if (wf_CheckGet(array('ajaxavaiblevotes'))) {
       $pollsReport->ajaxAvaibleVotes();
    }

    //getting polls votes result
    if (wf_CheckGet(array('ajaxapollvotes'))) {
       $pollsReport->ajaxPollVotes(vf($_GET['poll_id']));
    }

    //show polls control panel
    show_window(__('Polls results'), $pollsReport->panel());

        // show form for create poll
        if (wf_CheckGet(array('action'))) {
            if ($_GET['action'] == 'show_poll_votes') {
                $pollsReport->renderPollVotes();
            }
            if ($_GET['action'] == 'show_option_votes') {
                $pollsReport->renderOptionVotes();
            }
        } else {
            $pollsReport->renderAvaibleVotes();
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('Polls now disabled'));
}
?>
