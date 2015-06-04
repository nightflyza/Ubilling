<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['PON_ENABLED']) {
    if (cfr('PON')) {
        
        $pon=new PONizer();
 
        
        show_window('',$pon->controls());
        
        //creating new ONU device
        if (wf_CheckPost(array('createnewonu','newoltid','newmac'))) {
            $pon->onuCreate($_POST['newonumodelid'], $_POST['newoltid'], $_POST['newip'], $_POST['newmac'],$_POST['newserial'], $_POST['newlogin']);
            rcms_redirect('?module=ponizer');
        }
        

    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>