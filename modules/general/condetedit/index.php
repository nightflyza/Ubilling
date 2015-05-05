<?php

if (cfr('CONDET')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['CONDET_ENABLED']) {
        if (wf_CheckGet(array('username'))) {
            $login = $_GET['username'];

            $conDet = new ConnectionDetails();

            if (wf_CheckPost(array('editcondet'))) {
                $conDet->set($login, $_POST['newseal'], $_POST['newlength'], $_POST['newprice']);
                rcms_redirect('?module=condetedit&username=' . $login);
            }

            show_window(__('Edit') . ' ' . __('Connection details'), $conDet->editForm($login));
            
//additional notes
            if ($altCfg['ADCOMMENTS_ENABLED']) {
                $adcomments = new ADcomments('CONDET');
                show_window(__('Additional comments'), $adcomments->renderComments($login));
            }
//default user controls
            show_window('', web_UserControls($login));
        } else {
            show_error(__('Strange exeption'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>