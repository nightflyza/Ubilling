<?php

$altCfg = $ubillingConfig->getAlter();

if (cfr('BRANCHESONUVIEW')) {
    if ($altCfg['PON_ENABLED']) {
        if ($altCfg['BRANCHES_ONUVIEW']) {
            if ($altCfg['BRANCHES_ENABLED']) {
                $pon = new PONizer();

                //getting OLT FDB list
                if (ubRouting::checkGet(array('ajaxoltfdb', 'onuid'))) {
                    $pon->ajaxOltFdbData(ubRouting::get('onuid', 'int'));
                }

                //assigning ONU with some user
                if (ubRouting::checkPost(array('assignonulogin', 'assignonuid'))) {
                    if (cfr('PONEDIT')) {
                        $pon->onuAssign(ubRouting::post('assignonuid'), ubRouting::post('assignonulogin'));
                        if ($ubillingConfig->getAlterParam('OPT82_ENABLED')) {
                            multinet_rebuild_all_handlers();
                        }
                        ubRouting::nav('?module=pl_branchesonuview&editonu=' . ubRouting::post('assignonuid'));
                    } else {
                        show_error(__('Access denied'));
                    }
                }

                //detecting user assigned ONUs
                if (ubRouting::checkGet('username')) {
                    //try to detect ONU id by user login
                    $login = ubRouting::get('username');
                    $userOnuIds = $pon->getOnuIdByUserAll($login);

                    if ($userOnuIds) {
                        if (sizeof($userOnuIds) > 1) {
                            //multiple ONUs here... rendering ONU navigation here
                            show_window(__('This user has multiple devices assigned'), $pon->renderOnuNavBar($userOnuIds, '?module=pl_branchesonuview&editonu='));
                            show_window('', web_UserControls(ubRouting::get('username')));
                        } else {
                            //redirecting to single assigned ONU
                            ubRouting::nav('?module=pl_branchesonuview&editonu=' . $userOnuIds[0]);
                        }
                    } else {
                        if (cfr('PONEDIT')) {
                            //rendering assign form if user have any
                            show_window(__('ONU assign'), $pon->onuAssignForm($login));

                            //render batch ONU register forms
                            if ($altCfg['BRANCHES_ONUVIEW_BATCHREG']) {
                                if (!ubRouting::checkPost('runmassonureg')) {
                                    show_window(__('Unknown ONU'), $pon->renderBatchOnuRegList());
                                    show_window(__('Register all unknown ONUs'), $pon->renderBatchOnuRegForm());
                                } else {
                                    //running batch ONU register subroutine
                                    show_window(__('Register all unknown ONUs'), $pon->runBatchOnuRegister('?module=pl_branchesonuview&username=' . $login));
                                }
                            }
                        } else {
                            show_warning(__('No ONUs of devices assigned to this user were detected'));
                            show_window('', wf_BackLink(UserProfile::URL_PROFILE . $login));
                        }
                    }
                }

                //ONU editing form aka ONU profile here
                if (ubRouting::checkGet('editonu')) {
                    //getting ONU data if it exists
                    $onuData = $pon->getOnuData(ubRouting::get('editonu'));
                    if (!empty($onuData)) {
                        $assignedUserLogin = $onuData['login'];
                        //is this user allowed to access this ONU depend on his branch?
                        if ($branchControl->isMyUser($assignedUserLogin)) {
                            //deleting additional users
                            if (ubRouting::checkGet(array('deleteextuser'))) {
                                if (cfr('PONEDIT')) {
                                    $pon->deleteOnuExtUser(ubRouting::get('deleteextuser'));
                                    ubRouting::nav('?module=pl_branchesonuview&editonu=' . ubRouting::get('editonu'));
                                }
                            }

                            //creating new additional user
                            if (ubRouting::checkPost(array('newpononuextid', 'newpononuextlogin'))) {
                                if (cfr('PONEDIT')) {
                                    $pon->createOnuExtUser(ubRouting::post('newpononuextid'), ubRouting::post('newpononuextlogin'));
                                    ubRouting::nav('?module=pl_branchesonuview&editonu=' . ubRouting::get('editonu'));
                                }
                            }

                            //edits existing ONU in database
                            if (ubRouting::checkPost(array('editonu', 'editoltid', 'editmac'))) {
                                $pon->onuSave(ubRouting::post('editonu'), ubRouting::post('editonumodelid'), ubRouting::post('editoltid'), ubRouting::post('editip'), ubRouting::post('editmac'), ubRouting::post('editserial'), ubRouting::post('editlogin'));
                                if ($ubillingConfig->getAlterParam('OPT82_ENABLED')) {
                                    multinet_rebuild_all_handlers();
                                }
                                ubRouting::nav('?module=pl_branchesonuview&editonu=' . ubRouting::post('editonu'));
                            }

                            //show ONU editing interface aka ONU profile
                            show_window(__('Edit'), $pon->onuEditForm(ubRouting::get('editonu'), true));
                            show_window(__('ONU FDB'), $pon->renderOltFdbList(ubRouting::get('editonu'), '?module=pl_branchesonuview&ajaxoltfdb=true&onuid='));
                            $pon->loadonuSignalHistory(ubRouting::get('editonu'));
                            zb_BillingStats(true);
                        } else {
                            show_error(__('Access denied'));
                            log_register('BRANCH ACCESS FAIL (' . $assignedUserLogin . ') ADMIN {' . whoami() . '} ONU [' . ubRouting::get('editonu') . ']');
                        }
                    } else {
                        show_error(__('Strange exception') . ': ONUID_NOT_EXISTS');
                        show_window('', wf_img('skins/ponywrong.png'));
                    }
                }


                //no extra routes or extra post data received
                if (sizeof(ubRouting::rawGet()) < 2) {
                    show_error(__('Strange exception'));
                    show_window('', wf_img('skins/ponywrong.png'));
                }
            } else {
                show_error(__('This module disabled') . ': ' . __('Branches'));
            }
        } else {
            show_error(__('This module disabled') . ': ' . __('ONU view'));
        }
    } else {
        show_error(__('This module disabled') . ': ' . __('PONizer'));
    }
} else {
    show_error(__('Access denied'));
}