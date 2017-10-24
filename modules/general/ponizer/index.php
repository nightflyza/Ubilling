<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['PON_ENABLED']) {
    if (cfr('PON')) {

        $pon = new PONizer();

        //getting ONU json data for list
        if (wf_CheckGet(array('ajaxonu', 'oltid'))) {
            $pon->ajaxOnuData(vf($_GET['oltid'], 3));
        }

        //getting unregistered ONU list
        if (wf_CheckGet(array('ajaxunknownonu'))) {
            $pon->ajaxOnuUnknownData();
        }

        //getting OLT FDB list
        if (wf_CheckGet(array('ajaxoltfdb', 'onuid'))) {
            $pon->ajaxOltFdbData(vf($_GET['onuid'], 3));
        }

        //creating new ONU device
        if (wf_CheckPost(array('createnewonu', 'newoltid', 'newmac'))) {
            $onuCreateResult = $pon->onuCreate($_POST['newonumodelid'], $_POST['newoltid'], $_POST['newip'], $_POST['newmac'], $_POST['newserial'], $_POST['newlogin']);
            if ($onuCreateResult) {
                multinet_rebuild_all_handlers();
                rcms_redirect('?module=ponizer');
            } else {
                show_error(__('This MAC have wrong format'));
            }
        }

        //edits existing ONU in database
        if (wf_CheckPost(array('editonu', 'editoltid', 'editmac'))) {
            $pon->onuSave($_POST['editonu'], $_POST['editonumodelid'], $_POST['editoltid'], $_POST['editip'], $_POST['editmac'], $_POST['editserial'], $_POST['editlogin']);
            multinet_rebuild_all_handlers();
            rcms_redirect('?module=ponizer&editonu=' . $_POST['editonu']);
        }

        //deleting existing ONU
        if (wf_CheckGet(array('deleteonu'))) {
            $pon->onuDelete($_GET['deleteonu']);
            multinet_rebuild_all_handlers();
            rcms_redirect('?module=ponizer');
        }

        //assigning ONU with some user
        if (wf_CheckPost(array('assignonulogin', 'assignonuid'))) {
            $pon->onuAssign($_POST['assignonuid'], $_POST['assignonulogin']);
            multinet_rebuild_all_handlers();
            rcms_redirect('?module=ponizer&editonu=' . $_POST['assignonuid']);
        }

        //force OLT polling
        if (wf_CheckGet(array('forcepoll'))) {
            $pon->oltDevicesPolling(true);
            if (wf_CheckGet(array('uol'))) {
                rcms_redirect('?module=ponizer&unknownonulist=true');
            } else {
                rcms_redirect('?module=ponizer');
            }
        }


        if (!wf_CheckGet(array('editonu'))) {
            if (wf_CheckGet(array('username'))) {
                //try to detect ONU id by user login
                $login = $_GET['username'];
                $userOnuId = $pon->getOnuIdByUser($login);
                //redirecting to assigned ONU
                if ($userOnuId) {
                    rcms_redirect('?module=ponizer&editonu=' . $userOnuId);
                } else {
                    //rendering assign form
                    show_window(__('ONU assign'), $pon->onuAssignForm($login));
                }
            } else {
                if (wf_CheckGet(array('unknownonulist'))) {
                    if (wf_CheckGet(array('fastreg', 'oltid', 'onumac'))) {
                        $newOltId = vf($_GET['oltid'], 3);
                        $newOnuMac = mysql_real_escape_string($_GET['onumac']);
                        show_window(__('Register new ONU'), wf_BackLink('?module=ponizer&unknownonulist=true', __('Back'), true) . $pon->onuRegisterForm($newOltId, $newOnuMac));
                    } else {
                        show_window(__('Unknown ONU'), $pon->controls() . $pon->renderUnknowOnuList());
                    }
                } else {
                    if (wf_CheckGet(array('fdbcachelist'))) {
                        if (wf_CheckGet(array('ajaxfdblist'))) {
                            $pon->ajaxFdbCacheList();
                        }
                        show_window(__('Current FDB cache'), $pon->renderOnuFdbCache());
                    } else {
                        //rendering availavle onu LIST
                        show_window(__('ONU directory'), $pon->controls());
                        $pon->renderOnuList();
                    }
                }
            }
        } else {
            //show ONU editing interface
            show_window(__('Edit'), $pon->onuEditForm($_GET['editonu']));
            show_window(__('ONU FDB'), $pon->renderOltFdbList($_GET['editonu']));
            $pon->loadonuSignalHistory($_GET['editonu']);
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>