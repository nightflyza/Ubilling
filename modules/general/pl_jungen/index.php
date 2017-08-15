<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['JUNGEN_ENABLED']) {
    if (cfr('JUNGEN')) {
        if (wf_CheckGet(array('username'))) {
            $userLogin = $_GET['username'];
            $juncast = new JunCast();
            //session termination
            if (wf_CheckGet(array('terminate'))) {
                $juncast->terminateUser($userLogin);
                log_register('JUNGEN TERMINATE (' . $userLogin . ')');
                rcms_redirect('?module=pl_jungen&username=' . $userLogin);
            }

            //user session manual blocking
            if (wf_CheckGet(array('block'))) {
                $juncast->blockUser($userLogin);
                log_register('JUNGEN MANUAL BLOCK (' . $userLogin . ')');
                rcms_redirect('?module=pl_jungen&username=' . $userLogin);
            }

            //user session manual unblocking
            if (wf_CheckGet(array('unblock'))) {
                $juncast->unblockUser($userLogin);
                log_register('JUNGEN MANUAL UNBLOCK (' . $userLogin . ')');
                rcms_redirect('?module=pl_jungen&username=' . $userLogin);
            }

            //manual attributes regeneration
            if (wf_CheckGet(array('regenerateall'))) {
                $junGen=new JunGen();
                $junGen->totalRegeneration();
                log_register('JUNGEN MANUAL REGENERATION');
                rcms_redirect('?module=pl_jungen&username=' . $userLogin);
            }

            $junControls = wf_Link('?module=pl_jungen&username=' . $userLogin . '&terminate=true', wf_img('skins/skull.png') . ' ' . __('Terminate user session'), false, 'ubButton') . ' ';
            $junControls.= wf_Link('?module=pl_jungen&username=' . $userLogin . '&block=true', wf_img('skins/whreservation.png') . ' ' . __('Block user'), false, 'ubButton') . ' ';
            $junControls.= wf_Link('?module=pl_jungen&username=' . $userLogin . '&unblock=true', wf_img('skins/icon_online.gif') . ' ' . __('Unblock user'), false, 'ubButton') . ' ';
            $junControls.= wf_Link('?module=pl_jungen&username=' . $userLogin . '&regenerateall=true', wf_img('skins/refresh.gif') . ' ' . __('Base regeneration'), false, 'ubButton') . ' ';
            show_window('', $junControls);

            $junAcct = new JunAcct($userLogin);
            show_window(__('Juniper NAS sessions stats'), $junAcct->renderAcctStats());
            show_window('', web_UserControls($userLogin));
        } else {
            show_error(__('Something went wrong'));
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}