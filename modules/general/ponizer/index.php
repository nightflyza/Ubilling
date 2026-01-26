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

        //Creating new PONizer object instance
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

        //getting ONU json data from some OLT
        if (ubRouting::checkGet(array('ajaxonu', 'oltid'))) {
            $pon->ajaxOnuData(ubRouting::get('oltid', 'int'));
        }

        //getting full ONU list json data
        if ($legacyPonizerView) {
            if (ubRouting::checkGet(array('ajaxonu', 'legacyView'))) {
                $pon->ajaxOnuData();
            }
        }

        //creating new ONU device
        if (ubRouting::checkPost(array('createnewonu', 'newoltid'))) {
            //MAC or Serial is required
            if (ubRouting::checkPost('newmac') or ubRouting::checkPost('newserial')) {
                if (cfr('PONEDIT')) {
                    $onuCreateResult = $pon->onuCreate(ubRouting::post('newonumodelid'), ubRouting::post('newoltid'), ubRouting::post('newip'), ubRouting::post('newmac'), ubRouting::post('newserial'), ubRouting::post('newlogin'));
                    if ($onuCreateResult) {
                        $newCreatedONUId = simple_get_lastid('pononu');
                        if ($ubillingConfig->getAlterParam('OPT82_ENABLED')) {
                            multinet_rebuild_all_handlers();
                        }
                        ubRouting::nav($pon::URL_ONU . $newCreatedONUId);
                    } else {
                        show_error(__('This MAC have wrong format') . ' ' . __('or') . ' ' . __('MAC duplicate'));
                    }
                } else {
                    log_register('PON CREATE ONU ACCESS VIOLATION');
                    show_error(__('Access denied'));
                }
            }
        }

        //perform search in nethosts for a MAC and a login linked to it
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

        //edits existing ONU in database
        if (ubRouting::checkPost(array('editonu', 'editoltid', 'editmac'))) {
            $pon->onuSave(
                ubRouting::post('editonu'),
                ubRouting::post('editonumodelid'),
                ubRouting::post('editoltid'),
                ubRouting::post('editip'),
                ubRouting::post('editmac'),
                ubRouting::post('editserial'),
                ubRouting::post('editlogin'),
                ubRouting::post('editgeo')
                );
            if ($ubillingConfig->getAlterParam('OPT82_ENABLED')) {
                multinet_rebuild_all_handlers();
            }
            ubRouting::nav($pon::URL_ONU . ubRouting::post('editonu'));
        }

        //deleting existing ONU
        if (ubRouting::checkGet('deleteonu')) {
            if (cfr('PONDEL')) {
                $pon->onuDelete(ubRouting::get('deleteonu'));
                if ($ubillingConfig->getAlterParam('OPT82_ENABLED')) {
                    multinet_rebuild_all_handlers();
                }
                ubRouting::nav($pon::URL_ONULIST);
            } else {
                log_register('PON DELETE ONU [' . ubRouting::get('deleteonu', 'int') . '] ACCESS VIOLATION');
                show_error(__('Access denied'));
            }
        }

        //burial of some ONU
        if (ubRouting::checkGet('onuburial')) {
            if (cfr('PONEDIT')) {
                $pon->onuBurial(ubRouting::get('onuburial'));
                ubRouting::nav($pon::URL_ONU . ubRouting::get('onuburial'));
            } else {
                show_error(__('Access denied'));
            }
        }

        //resurrection of some ONU
        if (ubRouting::checkGet('onuresurrect')) {
            if (cfr('PONEDIT')) {
                $pon->onuResurrect(ubRouting::get('onuresurrect'));
                ubRouting::nav($pon::URL_ONU . ubRouting::get('onuresurrect'));
            } else {
                show_error(__('Access denied'));
            }
        }

        //assigning ONU with some user
        if (ubRouting::checkPost(array('assignonulogin', 'assignonuid'))) {
            if (cfr('PONEDIT')) {
                $pon->onuAssign(ubRouting::post('assignonuid'), ubRouting::post('assignonulogin'));
                if ($ubillingConfig->getAlterParam('OPT82_ENABLED')) {
                    multinet_rebuild_all_handlers();
                }
                ubRouting::nav($pon::URL_ONU . ubRouting::post('assignonuid'));
            } else {
                show_error(__('Access denied'));
            }
        }

        //force OLT polling
        if (ubRouting::checkGet('forcepoll')) {
            $pon->oltDevicesPolling(true);
            if (ubRouting::checkGet('uol')) {
                //back to unknown ONU list
                ubRouting::nav($pon::URL_ME . '&unknownonulist=true');
            } else {
                //or just to OLT list
                ubRouting::nav($pon::URL_ONULIST);
            }
        }

        //force single OLT polling
        if (ubRouting::checkGet('forceoltidpoll')) {
            $pon->pollOltSignal(ubRouting::get('forceoltidpoll'));
            if (!ubRouting::checkGet('IndividualRefresh') or ! wf_getBoolFromVar(ubRouting::get('IndividualRefresh'), true)) {
                ubRouting::nav($pon::URL_ME);
            }
        }

        //user assigned ONU search or assign form
        if (ubRouting::checkGet('username')) {
            //try to detect ONU id by user login
            $login = ubRouting::get('username');
            $userOnuIds = $pon->getOnuIdByUserAll($login);

            if ($userOnuIds) {
                if (sizeof($userOnuIds) > 1) {
                    //multiple ONUs here... rendering ONU navigation here
                    show_window(__('This user has multiple devices assigned'), $pon->renderOnuNavBar($userOnuIds));
                    show_window('', web_UserControls(ubRouting::get('username')));
                } else {
                    //redirecting to single assigned ONU
                    $backUrl = wf_GenBackUrl(UserProfile::URL_PROFILE . $login);
                    ubRouting::nav($pon::URL_ONU . $userOnuIds[0] . $backUrl);
                }
            } else {
                //rendering assign form
                show_window(__('ONU assign'), $pon->onuAssignForm($login));
            }
        }

        //unknown ONU list
        if (ubRouting::checkGet('unknownonulist')) {
            if (cfr('PONEDIT')) {
                if (ubRouting::checkGet(array('fastreg', 'oltid', 'onumac'))) {
                    $newOltId = ubRouting::get('oltid', 'int');
                    $newOnuMac = ubRouting::get('onumac', 'mres');
                    show_window(__('Register new ONU'), wf_BackLink($pon::URL_ME . '&unknownonulist=true', __('Back'), true) . $pon->onuRegisterForm($newOltId, $newOnuMac));
                } else {
                    show_window(__('Unknown ONU'), $pon->controls() . $pon->renderUnknownOnuList());
                }
            } else {
                show_error(__('Access denied'));
            }
        }

        //All OLTs FDB cache list
        if (ubRouting::checkGet('fdbcachelist')) {
            if (ubRouting::checkGet('ajaxfdblist')) {
                $pon->ajaxFdbCacheList();
            }
            if (ubRouting::checkGet('fixonuoltassings')) {
                if (cfr('ROOT')) {
                    show_window(__('Fix OLT inconsistencies'), $pon->fixOnuOltAssigns());
                } else {
                    show_error(__('Access denied'));
                }
            } else {
                show_window(__('Current FDB cache'), $pon->renderOnuFdbCache());
            }
        }


        //Custom OLT interfaces description
        if (ubRouting::checkGet(array('oltid', 'if'))) {
            if (cfr('PONEDIT')) {
                if (@$altCfg['PON_IFDESC']) {
                    //saving manual descriptions
                    if (ubRouting::checkPost(array('newoltiddesc', 'newoltif'))) {
                        $pon->ponInterfaces->save();
                        ubRouting::nav($pon::URL_ME . '&oltid=' . ubRouting::post('newoltiddesc') . '&if=' . ubRouting::post('newoltif'));
                    }
                    //manual interface description controller
                    show_window(__('Description'), $pon->ponInterfaces->renderIfForm(ubRouting::get('oltid'), ubRouting::get('if')));
                } else {
                    show_error(__('This module is disabled'));
                }
            } else {
                show_error(__('Access denied'));
            }
        }

        //Basic OLTs stats
        if (ubRouting::checkGet('oltstats')) {
            show_window(__('Stats'), $pon->renderOltStats());
        }

        //pondata cache cleanup
        if (ubRouting::checkGet('pondatacleanup')) {
            if (cfr('ROOT')) {
                $oltData = new OLTAttractor();
                $ponDataCleanupResult = $oltData->flushAllCacheData();
                log_register('PON DATACACHE FLUSHED `' . $ponDataCleanupResult . '` CONTAINERS');
                ubRouting::nav($pon::URL_ME . '&oltstats=true');
            }
        }

        //OLTs polling log render here
        if (ubRouting::checkGet('polllogs')) {
            show_window(__('OLT polling log'), $pon->renderLogControls());
            if (ubRouting::checkGet('zenlog')) {
                $ponyZen = new ZenFlow('oltpollzen', $pon->renderPollingLog(), 3000);
                show_window(__('Zen') . ' ' . __('Log'), $ponyZen->render());
            } else {
                show_window(__('Log'), $pon->renderPollingLog());
            }
        }

        //ONU search here
        if (ubRouting::checkGet('onusearch')) {
            if (ubRouting::checkPost('onusearchquery')) {
                show_window('', wf_BackLink($pon::URL_ONULIST));
                if (@$altCfg['PON_ONU_SEARCH_ENABLED']) {
                    show_window(__('Search') . ' ' . __('ONU'), $pon->renderOnuSearchForm());
                    show_window(__('Search results'), $pon->renderOnuSearchResult());
                } else {
                    show_error(__('Search') . ' ' . __('ONU') . ' ' . __('Disabled'));
                }
            } else {
                show_window('', wf_BackLink($pon::URL_ONULIST));
                show_warning(__('Search query') . ' ' . __('is empty'));
            }
        }

        //ONU assigment check
        if (ubRouting::get('action') == 'checkONUAssignment' and ubRouting::checkGet('onumac')) {
            $pon->checkONUAssignmentReply();
        }


        // background ONU creation form callback
        if (ubRouting::checkGet(array('renderCreateForm'))) {
            if (ubRouting::checkGet('renderDynamically') && wf_getBoolFromVar(ubRouting::get('renderDynamically'), true)) {
                $CPECreateForm = $pon->onuRegisterForm(
                    ubRouting::get('oltid'),
                    ubRouting::get('onumac'),
                    ubRouting::get('userLogin'),
                    ubRouting::get('userIP'),
                    wf_getBoolFromVar(ubRouting::get('renderedOutside'), true),
                    wf_getBoolFromVar(ubRouting::get('reloadPageAfterDone'), true),
                    ubRouting::get('ActionCtrlID'),
                    ubRouting::get('ModalWID'),
                    ubRouting::get('onulogin')
                );

                die(wf_modalAutoForm(__('Register new ONU'), $CPECreateForm, ubRouting::get('ModalWID'), ubRouting::get('ModalWBID'), true));
            } else {
                die($pon->onuRegisterForm(ubRouting::get('oltid'), ubRouting::get('onumac'), ubRouting::get('userLogin'), ubRouting::get('userIP'), wf_getBoolFromVar(ubRouting::get('renderedOutside'), true), wf_getBoolFromVar(ubRouting::get('reloadPageAfterDone'), true), ubRouting::get('ActionCtrlID'), ubRouting::get('ModalWID')));
            }
        }

        //Unknown ONU batch registration here
        if (ubRouting::checkGet('onumassreg')) {
            if (cfr('ROOT')) {
                if (!ubRouting::checkPost('runmassonureg')) {
                    //Unknown ONU list and form here
                    show_window('', wf_BackLink('?module=ponizer&unknownonulist=true'));
                    show_window(__('Register all unknown ONUs'), $pon->renderBatchOnuRegForm());
                    show_window(__('Unknown ONU'), $pon->renderBatchOnuRegList());
                } else {
                    //running batch ONU register subroutine
                    show_window(__('Register all unknown ONUs'), $pon->runBatchOnuRegister());
                }
            } else {
                show_error(__('Access denied'));
            }
        }

        //ONU editing form aka ONU profile here
        if (ubRouting::checkGet('editonu')) {
            //deleting additional users
            if (ubRouting::checkGet(array('deleteextuser'))) {
                if (cfr('PONEDIT')) {
                    $pon->deleteOnuExtUser(ubRouting::get('deleteextuser'));
                    ubRouting::nav($pon::URL_ONU . ubRouting::get('editonu'));
                }
            }

            //creating new additional user
            if (ubRouting::checkPost(array('newpononuextid', 'newpononuextlogin'))) {
                if (cfr('PONEDIT')) {
                    $pon->createOnuExtUser(ubRouting::post('newpononuextid'), ubRouting::post('newpononuextlogin'));
                    ubRouting::nav($pon::URL_ONU . ubRouting::get('editonu'));
                }
            }

            //show ONU editing interface aka ONU profile
            show_window(__('Edit'), $pon->onuEditForm(ubRouting::get('editonu')));
            show_window(__('ONU FDB'), $pon->renderOltFdbList(ubRouting::get('editonu')));
            $pon->loadonuSignalHistory(ubRouting::get('editonu'));
        }

        //Rendering all OLTs ONUs list and main module controls
        if (ubRouting::checkGet('onulist')) {
            show_window(__('ONU directory'), $pon->controls());
            //rendering available ONU list
            $pon->renderOnuList();
            zb_BillingStats(true);
        }

        //no extra routes or extra post data received
        if (sizeof(ubRouting::rawGet()) == 1 and sizeof(ubRouting::rawPost()) == 1) {
            show_error(__('Strange exception'));
            show_window('', wf_img('skins/ponywrong.png'));
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module disabled'));
}
