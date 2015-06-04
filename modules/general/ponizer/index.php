<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['PON_ENABLED']) {
    if (cfr('PON')) {

        $pon = new PONizer();

        //getting ONU json data for list
        if (wf_CheckGet(array('ajaxonu'))) {
            die($pon->ajaxOnuData());
        }

        //creating new ONU device
        if (wf_CheckPost(array('createnewonu', 'newoltid', 'newmac'))) {
            $pon->onuCreate($_POST['newonumodelid'], $_POST['newoltid'], $_POST['newip'], $_POST['newmac'], $_POST['newserial'], $_POST['newlogin']);
            rcms_redirect('?module=ponizer');
        }

        //edits existing ONU in database
        if (wf_CheckPost(array('editonu', 'editoltid', 'editmac'))) {
            $pon->onuSave($_POST['editonu'], $_POST['editonumodelid'], $_POST['editoltid'], $_POST['editip'], $_POST['editmac'], $_POST['editserial'], $_POST['editlogin']);
            rcms_redirect('?module=ponizer&editonu=' . $_POST['editonu']);
        }

        //deleting existing ONU
        if (wf_CheckGet(array('deleteonu'))) {
            $pon->onuDelete($_GET['deleteonu']);
            rcms_redirect('?module=ponizer');
        }


        //rendering availavle onu LIST
        if (!wf_CheckGet(array('editonu'))) {
            show_window(__('ONU directory'), $pon->controls() . $pon->renderOnuList());
        } else {
            //show ONU editing interface
            show_window(__('Edit'), $pon->onuEditForm($_GET['editonu']));
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>