<?php

if (cfr('CONDET')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['CONDET_ENABLED']) {
        if (wf_CheckGet(array('username'))) {
            $login = ubRouting::get('username');
            $conDet = new ConnectionDetails();

            if (ubRouting::checkPost('editcondet')) {
                $conDet->set($login, ubRouting::post('newseal'), ubRouting::post('newlength'), ubRouting::post('newprice'), ubRouting::post('newterm'));
                ubRouting::nav($conDet::URL_ME . $login);
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
