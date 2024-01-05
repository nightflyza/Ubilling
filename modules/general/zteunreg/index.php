<?php

global $ubillingConfig;
$altcfg = $ubillingConfig->getAlter();

if (@$altcfg[OnuRegister::MODULE_CONFIG]) {
    if (cfr(OnuRegister::REG_MODULE_RIGHTS)) {
        $register = new OnuRegister();
        $avidity = $register->getAvidity();
        $onuIdentifier = OnuRegister::EMPTY_FIELD;
        if (wf_CheckGet(array('action'))) {
            if (isset($_GET['login'])) {
                $tmpLogin = $_GET['login'];
            } else {
                $tmpLogin = '';
            }
            die($register->getQinqByLogin($tmpLogin));
        }
        if (!empty($avidity)) {
            $avidity_z = $avidity['M']['LUCIO'];
            $avidity_w = $avidity['M']['REAPER'];
            $avidity_b = $avidity['M']['LAI'];
            if (wf_CheckGet(array('oltlist'))) {
                if (wf_CheckGet(array('oltid'))) {
                    if (wf_CheckGet(array('massfix', 'preview'))) {
                        $actionLinks = wf_Link(OnuRegister::UNREG_MASS_FIX_RUN_OLT_URL . $_GET['oltid'], __('Run'), false, 'ubButton');
                        $register->listFixable();
                    }
                    if (wf_CheckGet(array('massfix', 'run'))) {
                        $register->onuMassRegister();
                        show_window(__('Result'), $register->result);
                    }
                    if (wf_CheckGet(array(OnuRegister::OLTIP_FIELD, OnuRegister::INTERFACE_FIELD, OnuRegister::TYPE_FIELD))) {
                        if ($altcfg['ONUREG_ALWAYS_SHOW_UNREGISTERED']) {
                            show_info(__('OLT device') . ' : ' . $_GET[OnuRegister::OLTIP_FIELD]);
                            show_window(__('Check for unauthenticated ONU/ONT'), $register->$avidity_z());
                            show_warning(__('OLT device') . ' : ' . $_GET[OnuRegister::OLTIP_FIELD]);
                        }

                        if (wf_CheckGet(array(OnuRegister::MACONU_FIELD))) {
                            $onuIdentifier = $_GET[OnuRegister::MACONU_FIELD];
                        }
                        if (wf_CheckGet(array(OnuRegister::SERIAL_FIELD))) {
                            $onuIdentifier = $_GET[OnuRegister::SERIAL_FIELD];
                        }
                        if (!empty($onuIdentifier)) {
                            $register->currentOltIp = $_GET[OnuRegister::OLTIP_FIELD];
                            $register->currentOltInterface = $_GET[OnuRegister::INTERFACE_FIELD];
                            $register->currentPonType = $_GET[OnuRegister::TYPE_FIELD];
                            $register->onuIdentifier = $onuIdentifier;
                            $register->currentOltSwId = $_GET[OnuRegister::OLTID_FIELD];
                            show_window(__('Register'), $register->registerOnuForm());
                        }
                    } else {
                        show_window(__('Check for unauthenticated ONU/ONT'), $register->$avidity_z());
                    }
                } else {
                    show_window(__('All ZTE OLTs'), $register->$avidity_b(false));
                }
            } elseif (wf_CheckGet(array('massfix', 'preview'))) {
                $actionLinks = wf_Link(OnuRegister::UNREG_MASS_FIX_RUN_URL, __('Run'), false, 'ubButton');
                $register->listFixable();
            } elseif (wf_CheckGet(array('massfix', 'run'))) {
                $register->onuMassRegister();
                show_window(__('Result'), $register->result);
            } else {
                if (wf_CheckGet(array(OnuRegister::OLTIP_FIELD, OnuRegister::INTERFACE_FIELD, OnuRegister::TYPE_FIELD))) {
                    if ($altcfg['ONUREG_ALWAYS_SHOW_UNREGISTERED']) {
                        show_window(__('Check for unauthenticated ONU/ONT'), $register->$avidity_z());
                    }

                    if (wf_CheckGet(array(OnuRegister::MACONU_FIELD))) {
                        $onuIdentifier = $_GET[OnuRegister::MACONU_FIELD];
                    }
                    if (wf_CheckGet(array(OnuRegister::SERIAL_FIELD))) {
                        $onuIdentifier = $_GET[OnuRegister::SERIAL_FIELD];
                    }
                    if (!empty($onuIdentifier)) {
                        $register->currentOltIp = $_GET[OnuRegister::OLTIP_FIELD];
                        $register->currentOltInterface = $_GET[OnuRegister::INTERFACE_FIELD];
                        $register->currentPonType = $_GET[OnuRegister::TYPE_FIELD];
                        $register->onuIdentifier = $onuIdentifier;
                        $register->currentOltSwId = $_GET[OnuRegister::OLTID_FIELD];
                        show_window(__('Register'), $register->registerOnuForm());
                    }
                } else {
                    show_window(__('Check for unauthenticated ONU/ONT'), $register->$avidity_z());
                }
            }
            show_window(OnuRegister::EMPTY_FIELD, wf_BackLink(PONizer::URL_ONULIST));
            if (wf_CheckPost(array(OnuRegister::TYPE_FIELD, OnuRegister::INTERFACE_FIELD, OnuRegister::OLTIP_FIELD, OnuRegister::MODELID_FIELD, OnuRegister::OLTID_FIELD))) {
                if ($_POST[OnuRegister::MODELID_FIELD] != OnuRegister::MODELID_PLACEHOLDER) {
                    if (wf_CheckPost(array(OnuRegister::VLAN_FIELD)) or $_POST[OnuRegister::GET_UNIVERSALQINQ] != 'none') {

                        $register->addMac = OnuRegister::EMPTY_FIELD;
                        $save = false;
                        $router = false;
                        $register->login = OnuRegister::EMPTY_FIELD;
                        $PONizerAdd = false;
                        if (wf_CheckPost(array(OnuRegister::LOGIN_FIELD))) {
                            $register->login = $_POST[OnuRegister::LOGIN_FIELD];
                        }
                        if (wf_CheckPost(array(OnuRegister::MAC_FIELD))) {
                            $onuIdentifier = $_POST[OnuRegister::MAC_FIELD];
                        }
                        if (wf_CheckPost(array(OnuRegister::SN_FIELD))) {
                            $onuIdentifier = $_POST[OnuRegister::SN_FIELD];
                        }
                        if (isset($_POST[OnuRegister::ROUTER_FIELD])) {
                            $router = $_POST[OnuRegister::ROUTER_FIELD];
                        }
                        if (isset($_POST[OnuRegister::MAC_ONU_FIELD])) {
                            $register->addMac = $_POST[OnuRegister::MAC_ONU_FIELD];
                        }
                        if (isset($_POST[OnuRegister::RANDOM_MAC_FIELD])) {
                            $register->addMac = $register->generateRandomOnuMac();
                        }
                        if (isset($_POST[OnuRegister::SAVE_FIELD])) {
                            $save = $_POST[OnuRegister::SAVE_FIELD];
                        }
                        if (isset($_POST[OnuRegister::PONIZER_ADD_FIELD])) {
                            $register->ponizerAdd = true;
                        }
                        $register->currentOltIp = $_POST[OnuRegister::OLTIP_FIELD];
                        $register->currentOltInterface = $_POST[OnuRegister::INTERFACE_FIELD];
                        $register->currentPonType = $_POST[OnuRegister::TYPE_FIELD];
                        $register->onuIdentifier = $onuIdentifier;
                        $register->currentOltSwId = $_POST[OnuRegister::OLTID_FIELD];
                        $register->save = $save;
                        $register->router = $router;
                        $register->vlan = $_POST[OnuRegister::VLAN_FIELD];
                        $register->onuModel = $_POST[OnuRegister::MODELID_FIELD];
                        if ($_POST[OnuRegister::ONUDESCRIPTION_FIELD] and !empty($_POST[OnuRegister::ONUDESCRIPTION_FIELD]) and $_POST[OnuRegister::ONUDESCRIPTION_FIELD] != '__empty') {
                            $register->onuDescription = $_POST[OnuRegister::ONUDESCRIPTION_FIELD];
                        }
                        if ($register->login) {
                            if (isset($_POST[OnuRegister::ONUDESCRIPTION_AS_LOGIN_FIELD])) {
                                $register->onuDescription = $register->login;
                            }
                        }
                        if (isset($_POST[OnuRegister::GET_UNIVERSALQINQ])) {
                            $register->useUniversalQINQ = $_POST[OnuRegister::GET_UNIVERSALQINQ];
                        }
                        if (isset($_POST[OnuRegister::DHCP_SNOOPING_FIELD])) {
                            $register->onuDhcpSnooping = 'set';
                        }
                        if (isset($_POST[OnuRegister::LOOPDETECT_FIELD])) {
                            $register->onuLoopdetect = 'set';
                        }
                        $loginCheck = $register->checkOltParams();
                        if ($loginCheck !== OnuRegister::NO_ERROR_CONNECTION) {
                            show_error(__($loginCheck));
                        } else {
                            $register->$avidity_w();
                            if (empty($register->error)) {
                                show_window(__('Result'), $register->result);
                            } else {
                                show_error(__($register->error) . ': ' . count($register->existId));
                            }
                        }
                    } else {
                        show_error(__(OnuRegister::ERROR_NOT_ALL_FIELDS));
                        show_error(__(OnuRegister::ERROR_NO_VLAN_SET));
                    }
                } else {
                    show_error(__(OnuRegister::ERROR_WRONG_MODELID));
                }
            } elseif (wf_CheckPost(array(OnuRegister::TYPE_FIELD))) {
                show_error(__(OnuRegister::ERROR_NOT_ALL_FIELDS));
                if (!wf_CheckPost(array(OnuRegister::INTERFACE_FIELD))) {
                    show_error(__(OnuRegister::ERROR_NO_INTERFACE_SET));
                }
                if (!wf_CheckPost(array(OnuRegister::OLTIP_FIELD))) {
                    show_error(__(OnuRegister::ERROR_NO_OLTIP_SET));
                }
            }
            zb_BillingStats(true, 'zteonureg');
        } else {
            show_error(__(OnuRegister::ERROR_NO_LICENSE));
        }
    } else {
        show_error(__(OnuRegister::ERROR_NO_RIGHTS));
    }
} else {
    show_error(__(OnuRegister::ERROR_NOT_ENABLED));
}