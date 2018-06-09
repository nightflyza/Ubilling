<?php

if (cfr('MULTIGEN')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['MULTIGEN_ENABLED']) {

        $mg = new MultiGen();

        if (wf_CheckGet(array('editnasoptions'))) {
            //editing NAS options
            if (wf_CheckPost(array('editnasid'))) {
                $nasOptionsSaveResult = $mg->saveNasOptions();
                if (empty($nasOptionsSaveResult)) {
                    rcms_redirect($mg::URL_ME . '&editnasoptions=' . $_POST['editnasid']);
                } else {
                    show_error($nasOptionsSaveResult);
                }
            }
            //rendering basic options form
            show_window(__('NAS options'), $mg->renderNasOptionsEditForm($_GET['editnasoptions']));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>