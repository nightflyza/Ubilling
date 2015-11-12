<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['DEALWITHIT_ENABLED']) {
    if (cfr('DEALWITHIT')) {
        $dealWithIt = new DealWithIt();

        if (wf_CheckGet(array('username'))) {
            $login = $_GET['username'];
            //creating new task
            if (wf_CheckPost(array('newschedlogin'))) {
                $createResult = $dealWithIt->catchCreateRequest();
                if ($createResult) {
                    show_error($createResult);
                } else {
                    rcms_redirect(DealWithIt::URL_ME . '&username=' . $login);
                }
            }
            //deleting existing task
            if (wf_CheckGet(array('deletetaskid'))) {
                $dealWithIt->deleteTask($_GET['deletetaskid']);
                rcms_redirect(DealWithIt::URL_ME . '&username=' . $login);
            }
            //displaying interface parts
            show_window(__('Create new task'), $dealWithIt->renderCreateForm($login));
            show_window(__('Held jobs for this user'), $dealWithIt->renderTasksList($login));


            show_window('', web_UserControls($login));
        } else {
            if (wf_CheckGet(array('ajinput'))) {
                $dealWithIt->catchAjRequest();
            } else {
                show_error(__('Something went wrong') . ': EX_GET_NO_USERNAME');
            }
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>