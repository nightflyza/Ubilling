<?php

$altCfg = $ubillingConfig->getAlter();
$legacyPonizerView = 0;
set_time_limit(0);

if ($altCfg['PON_ENABLED']) {
    if (cfr('PON')) {
        //Checking is ponizer legacy enabled?
        if (isset($altCfg['PONIZER_LEGACY_VIEW'])) {
            if ($altCfg['PONIZER_LEGACY_VIEW']) {
                $legacyPonizerView = $altCfg['PONIZER_LEGACY_VIEW'];
            }
        }

        //Creating required ponizer object instance
        if ($legacyPonizerView) {
            $pon = new PONizerLegacy();
        } else {
            $oltLoadData = '';

            if (ubRouting::checkGet(array('ajaxonu', 'oltid'))) {
                //load only selected OLTs data on ONU list rendering
                $oltLoadData = ubRouting::get('oltid');
            }
            $pon = new PONizer($oltLoadData);
        }

        //getting ONU json data for list
        if (ubRouting::checkGet(array('ajaxonu', 'oltid'))) {
            $pon->ajaxOnuData(ubRouting::get('oltid', 'int'));
        }

        if ($legacyPonizerView) {
            if (ubRouting::checkGet(array('ajaxonu', 'legacyView'))) {
                $pon->ajaxOnuData();
            }
        }


        if (ubRouting::checkGet(array('searchunknownonu', 'searchunknownmac'))) {
            die($pon->getUserByONUMAC(ubRouting::get('searchunknownmac'), ubRouting::get('searchunknownincrement'), ubRouting::get('searchunknownserialize')));
        }

        //getting unregistered ONU list
        if (ubRouting::checkGet('ajaxunknownonu')) {
            $pon->ajaxOnuUnknownData();
        }

        //getting OLT FDB list
        if (ubRouting::checkGet(array('ajaxoltfdb', 'onuid'))) {
            $pon->ajaxOltFdbData(ubRouting::get('onuid', 'int'));
        }

        //creating new ONU device
        if (ubRouting::checkPost(array('createnewonu', 'newoltid', 'newmac'))) {
            $onuCreateResult = $pon->onuCreate(ubRouting::post('newonumodelid'), ubRouting::post('newoltid'), ubRouting::post('newip'), ubRouting::post('newmac'), ubRouting::post('newserial'), ubRouting::post('newlogin'));
            if ($onuCreateResult) {
                $newCreatedONUId = simple_get_lastid('pononu');
                multinet_rebuild_all_handlers();
                ubRouting::nav($pon::URL_ME . '&editonu=' . $newCreatedONUId);
            } else {
                show_error(__('This MAC have wrong format'));
            }
        }

        //edits existing ONU in database
        if (ubRouting::checkPost(array('editonu', 'editoltid', 'editmac'))) {
            $pon->onuSave(ubRouting::post('editonu'), ubRouting::post('editonumodelid'), ubRouting::post('editoltid'), ubRouting::post('editip'), ubRouting::post('editmac'), ubRouting::post('editserial'), ubRouting::post('editlogin'));
            multinet_rebuild_all_handlers();
            ubRouting::nav($pon::URL_ME . '&editonu=' . ubRouting::post('editonu'));
        }

        //deleting existing ONU
        if (ubRouting::checkGet('deleteonu')) {
            $pon->onuDelete(ubRouting::get('deleteonu'));
            multinet_rebuild_all_handlers();
            ubRouting::nav($pon::URL_ME);
        }

        //burial of some ONU
        if (ubRouting::checkGet('onuburial')) {
            $pon->onuBurial(ubRouting::get('onuburial'));
            ubRouting::nav($pon::URL_ME . '&editonu=' . ubRouting::get('onuburial'));
        }

        //resurrection of some ONU
        if (ubRouting::checkGet('onuresurrect')) {
            $pon->onuResurrect(ubRouting::get('onuresurrect'));
            ubRouting::nav($pon::URL_ME . '&editonu=' . ubRouting::get('onuresurrect'));
        }

        //assigning ONU with some user
        if (ubRouting::checkPost(array('assignonulogin', 'assignonuid'))) {
            $pon->onuAssign(ubRouting::post('assignonuid'), ubRouting::post('assignonulogin'));
            multinet_rebuild_all_handlers();
            ubRouting::nav($pon::URL_ME . '&editonu=' . ubRouting::post('assignonuid'));
        }

        //force OLT polling
        if (ubRouting::checkGet('forcepoll')) {
            $pon->oltDevicesPolling(true);
            if (ubRouting::checkGet('uol')) {
                //back to unknown ONU list
                ubRouting::nav($pon::URL_ME . '&unknownonulist=true');
            } else {
                //or just to OLT list
                ubRouting::nav($pon::URL_ME);
            }
        }

        //force single OLT polling
        if (ubRouting::checkGet('forceoltidpoll')) {
            $pon->pollOltSignal(ubRouting::get('forceoltidpoll'));

            if (!ubRouting::checkGet('IndividualRefresh') OR ! wf_getBoolFromVar(ubRouting::get('IndividualRefresh'), true)) {
                ubRouting::nav($pon::URL_ME);
            }
        }


        if (!ubRouting::checkGet('editonu')) {
            if (ubRouting::checkGet('username')) {
                //try to detect ONU id by user login
                $login = ubRouting::get('username');
                $userOnuId = $pon->getOnuIdByUser($login);
                //redirecting to assigned ONU
                if ($userOnuId) {
                    ubRouting::nav($pon::URL_ME . '&editonu=' . $userOnuId);
                } else {
                    //rendering assign form
                    show_window(__('ONU assign'), $pon->onuAssignForm($login));
                }
            } else {
                if (ubRouting::checkGet('unknownonulist')) {
                    if (ubRouting::checkGet(array('fastreg', 'oltid', 'onumac'))) {
                        $newOltId = ubRouting::get('oltid', 'int');
                        $newOnuMac = ubRouting::get('onumac', 'mres');
                        show_window(__('Register new ONU'), wf_BackLink($pon::URL_ME . '&unknownonulist=true', __('Back'), true) . $pon->onuRegisterForm($newOltId, $newOnuMac));
                    } else {
                        show_window(__('Unknown ONU'), $pon->controls() . $pon->renderUnknownOnuList());
                    }
                } else {
                    if (ubRouting::checkGet('fdbcachelist')) {
                        if (ubRouting::checkGet('ajaxfdblist')) {
                            $pon->ajaxFdbCacheList();
                        }
                        if (ubRouting::checkGet('fixonuoltassings')) {
                            show_window(__('Fix OLT inconsistencies'), $pon->fixOnuOltAssigns());
                        } else {
                            show_window(__('Current FDB cache'), $pon->renderOnuFdbCache());
                        }
                    } else {
                        if (ubRouting::checkGet('oltstats')) {
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
                            //ONU search results
                            if (ubRouting::checkPost('onusearchquery')) {
                                show_window('', wf_BackLink($pon::URL_ME));
                                if (@$altCfg['PON_ONU_SEARCH_ENABLED']) {
                                    show_window(__('Search') . ' ' . __('ONU'), $pon->renderOnuSearchForm());
                                    show_window(__('Search results'), $pon->renderOnuSearchResult());
                                } else {
                                    show_error(__('Search') . ' ' . __('ONU') . ' ' . __('Disabled'));
                                }
                            } else {
                                //rendering available ONU list
                                show_window(__('ONU directory'), $pon->controls());

                                $pon->renderOnuList();
                                zb_BillingStats(true);
                            }
                        }
                    }
                }
            }
        } else {
            //deleting additional users
            if (ubRouting::checkGet(array('deleteextuser'))) {
                $pon->deleteOnuExtUser(ubRouting::get('deleteextuser'));
                ubRouting::nav($pon::URL_ME . '&editonu=' . ubRouting::get('editonu'));
            }

            //creating new additional user
            if (ubRouting::checkPost(array('newpononuextid', 'newpononuextlogin'))) {
                $pon->createOnuExtUser(ubRouting::post('newpononuextid'), ubRouting::post('newpononuextlogin'));
                ubRouting::nav($pon::URL_ME . '&editonu=' . ubRouting::get('editonu'));
            }

            //show ONU editing interface aka ONU profile
            show_window(__('Edit'), $pon->onuEditForm(ubRouting::get('editonu')));
            show_window(__('ONU FDB'), $pon->renderOltFdbList(ubRouting::get('editonu')));
            $pon->loadonuSignalHistory(ubRouting::get('editonu'), true);
        }

        if (ubRouting::checkGet(array('renderCreateForm'))) {
            if (ubRouting::checkGet('renderDynamically') && wf_getBoolFromVar(ubRouting::get('renderDynamically'), true)) {
                $CPECreateForm = $pon->onuRegisterForm(ubRouting::get('oltid'), ubRouting::get('onumac'), ubRouting::get('userLogin'), ubRouting::get('userIP'), wf_getBoolFromVar(ubRouting::get('renderedOutside'), true), wf_getBoolFromVar(ubRouting::get('reloadPageAfterDone'), true), ubRouting::get('ActionCtrlID'), ubRouting::get('ModalWID'));
                die(wf_modalAutoForm(__('Register new ONU'), $CPECreateForm, ubRouting::get('ModalWID'), ubRouting::get('ModalWBID'), true));
            } else {
                die($pon->onuRegisterForm(ubRouting::get('oltid'), ubRouting::get('onumac'), ubRouting::get('userLogin'), ubRouting::get('userIP'), wf_getBoolFromVar(ubRouting::get('renderedOutside'), true), wf_getBoolFromVar(ubRouting::get('reloadPageAfterDone'), true), ubRouting::get('ActionCtrlID'), ubRouting::get('ModalWID')));
            }
        }

        //ONU assigment check
        if (ubRouting::get('action') == 'checkONUAssignment' AND ubRouting::checkGet('onumac')) {
            $tString = '';
            $tStatus = 0;
            $tLogin = '';
            $oltData = '';
            $onuMAC = ubRouting::get('onumac');

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