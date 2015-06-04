<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['PON_ENABLED']) {
    if (cfr('PON')) {
        
        $pon=new PONizer();
     
       //getting ONU json data for list
       if (wf_CheckGet(array('ajaxonu'))) {
           die($pon->ajaxOnuData());
       }
        
        //creating new ONU device
        if (wf_CheckPost(array('createnewonu','newoltid','newmac'))) {
            $pon->onuCreate($_POST['newonumodelid'], $_POST['newoltid'], $_POST['newip'], $_POST['newmac'],$_POST['newserial'], $_POST['newlogin']);
            rcms_redirect('?module=ponizer');
        }
        

       show_window(__('Available ONU devices'), $pon->controls().$pon->renderOnuList());
        

    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>