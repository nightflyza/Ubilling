<?php
if (cfr('SWITCHESEDIT')) {
    if (wf_CheckGet(array('switchid'))) {
        //run replace
        if (wf_CheckPost(array('switchreplace','toswtichreplace','replaceemployeeid'))) {
            zb_SwitchReplace($_POST['switchreplace'], $_POST['toswtichreplace'],$_POST['replaceemployeeid']);
            rcms_redirect('?module=switches&edit='.$_POST['toswtichreplace']);
        }
       
        //display form
        $switchId=vf($_GET['switchid'],3);
        $switchData=  zb_SwitchGetData($switchId);
        show_window(__('Switch replacement').': '.$switchData['location'].' - '.$switchData['ip'],zb_SwitchReplaceForm($switchId));
    } else {
        show_error(__('Strange exeption'));
    }
    
} else {
    show_error(__('Access denied'));
}

?>