<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['DEALWITHIT_ENABLED']) {
    if (cfr('DEALWITHIT')) {
        if (wf_CheckGet(array('username'))) {
            $login=$_GET['username'];
            $dealWithIt=new DealWithIt();
           
            deb($dealWithIt->renderCreateForm($login));
           
            
            
            show_window('', web_UserControls($login));
        } else {
            show_error(__('Something went wrong') . ': EX_GET_NO_USERNAME');
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>