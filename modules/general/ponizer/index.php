<?php

$altCfg = $ubillingConfig->getAlter();
$legacy = 0;
set_time_limit(0);

if ($altCfg['PON_ENABLED']) {
    if (cfr('PON')) {

        $pon = new PONizer();
        if (isset($altCfg['PONIZER_LEGACY_VIEW'])) {
            if ($altCfg['PONIZER_LEGACY_VIEW']) {
                $legacy = 1;
                $pon2 = new PONizerLegacy();
            }
        }

        //getting ONU json data for list
        if (wf_CheckGet(array('ajaxonu', 'oltid'))) {
            $pon->ajaxOnuData(vf($_GET['oltid'], 3));
        }

        if ($legacy) {
            if (wf_CheckGet(array('ajaxonu', 'legacyView'))) {
                $pon2->ajaxOnuData();
            }
        }

        if (wf_CheckGet(array('searchunknownonu', 'searchunknownmac'))) {
            die($pon->getUserByONUMAC($_GET['searchunknownmac'], $_GET['searchunknownincrement'], $_GET['searchunknownserialize']));
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
                $newCreatedONUId = simple_get_lastid('pononu');
                multinet_rebuild_all_handlers();
                rcms_redirect('?module=ponizer&editonu=' . $newCreatedONUId);
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

        //force single OLT polling
        if (wf_CheckGet(array('forceoltidpoll'))) {
            $pon->pollOltSignal($_GET['forceoltidpoll']);

            if (!wf_CheckGet(array('IndividualRefresh')) OR ! wf_getBoolFromVar($_GET['IndividualRefresh'], true)) {
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
                        show_window(__('Unknown ONU'), $pon->controls() . $pon->renderUnknownOnuList());
                    }
                } else {
                    if (wf_CheckGet(array('fdbcachelist'))) {
                        if (wf_CheckGet(array('ajaxfdblist'))) {
                            $pon->ajaxFdbCacheList();
                        }
                        show_window(__('Current FDB cache'), $pon->renderOnuFdbCache());
                    } else {
                        if (wf_CheckGet(array('oltstats'))) {
                            if (!ubRouting::checkGet(array('oltid', 'if'))) {
                                //rendering OLT stats
                                show_window(__('Stats'), $pon->renderOltStats());
                            } else {
                                //saving manual descriptions
                                if (ubRouting::checkPost(array('newoltiddesc', 'newoltif'))) {
                                    $pon->ponInterfaces->save();
                                    ubRouting::nav($pon::URL_ME . '&oltstats=true&oltid=' . ubRouting::post('newoltiddesc') . '&if=' . ubRouting::post('newoltif'));
                                }
                                //manual interface description controller
                                show_window(__('Description'), $pon->ponInterfaces->renderIfForm(ubRouting::get('oltid'), ubRouting::get('if')));
                            }
                        } else {
                            //rendering available onu LIST
                            show_window(__('ONU directory'), $pon->controls());
                            if (!$legacy) {
                                $pon->renderOnuList();
                                zb_BillingStats(true);
                            } else {
                                $pon2->renderOnuList();
                            }
                        }
                    }
                }
            }
        } else {
            //deleting additional users
            if (wf_CheckGet(array('deleteextuser'))) {
                $pon->deleteOnuExtUser($_GET['deleteextuser']);
                rcms_redirect($pon::URL_ME . '&editonu=' . $_GET['editonu']);
            }

            //creating new additional user
            if (wf_CheckPost(array('newpononuextid', 'newpononuextlogin'))) {
                $pon->createOnuExtUser($_POST['newpononuextid'], $_POST['newpononuextlogin']);
                rcms_redirect($pon::URL_ME . '&editonu=' . $_GET['editonu']);
            }
            //show ONU editing interface aka ONU profile
            show_window(__('Edit'), $pon->onuEditForm($_GET['editonu']));
            show_window(__('ONU FDB'), $pon->renderOltFdbList($_GET['editonu']));
            $pon->loadonuSignalHistory($_GET['editonu'], true);
        }

        if (wf_CheckGet(array('renderCreateForm'))) {
            if (wf_CheckGet(array('renderDynamically')) && wf_getBoolFromVar($_GET['renderDynamically'], true)) {
                $CPECreateForm = $pon->onuRegisterForm($_GET['oltid'], $_GET['onumac'], $_GET['userLogin'], $_GET['userIP'], wf_getBoolFromVar($_GET['renderedOutside'], true), wf_getBoolFromVar($_GET['reloadPageAfterDone'], true), $_GET['ActionCtrlID'], $_GET['ModalWID']
                );
                die(wf_modalAutoForm(__('Register new ONU'), $CPECreateForm, $_GET['ModalWID'], $_GET['ModalWBID'], true));
            } else {
                die($pon->onuRegisterForm($_GET['oltid'], $_GET['onumac'], $_GET['userLogin'], $_GET['userIP'], wf_getBoolFromVar($_GET['renderedOutside'], true), wf_getBoolFromVar($_GET['reloadPageAfterDone'], true), $_GET['ActionCtrlID'], $_GET['ModalWID']
                        )
                );
            }
        }

        //ONU assigment check
        if ($_GET['action'] = 'checkONUAssignment' and isset($_GET['onumac'])) {
            $tString = '';
            $tStatus = 0;
            $tLogin = '';
            $oltData = '';
            $onuMAC = $_GET['onumac'];

            $ONUAssignment = $pon->checkONUAssignment($pon->getONUIDByMAC($onuMAC), true, true);

            $tStatus = $ONUAssignment['status'];
            $tLogin = $ONUAssignment['login'];
            $oltData = $ONUAssignment['oltdata'];

            switch ($tStatus) {
                case 0:
                    $tString = __('ONU is not assigned');
                    break;

                case 1:
                    $tString = __('ONU is already assigned, but such login is not exists anymore') . '. ' . __('Login') . ': ' . $tLogin . '. OLT: ' . $oltData;
                    break;

                case 2:
                    $tString = __('ONU is already assigned') . '. ' . __('Login') . ': ' . $tLogin . '. OLT: ' . $oltData;
                    break;
            }

            die($tString);
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
?>