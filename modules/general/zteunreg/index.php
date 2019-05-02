<?php

$altcfg = $ubillingConfig->getAlter();

if (@$altcfg[OnuRegister::MODULE_CONFIG]) {
    if (cfr(OnuRegister::MODULE_RIGHTS)) {
        $register = new OnuRegister();
        $avidity = $register->getAvidity();
        $onuIdentifier = OnuRegister::EMPTY_FIELD;
        if (!empty($avidity)) {
            $avidity_z = $avidity['M']['LUCIO'];
            $avidity_w = $avidity['M']['REAPER'];
            show_window(__('Check for unauthenticated ONU/ONT'), $register->$avidity_z());

            show_window(OnuRegister::EMPTY_FIELD, wf_BackLink(PONizer::URL_ME));

            if (wf_CheckGet(array(OnuRegister::OLTIP_FIELD, OnuRegister::INTERFACE_FIELD, OnuRegister::TYPE_FIELD))) {
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
            }
            if (wf_CheckPost(array(OnuRegister::TYPE_FIELD, OnuRegister::INTERFACE_FIELD, OnuRegister::OLTIP_FIELD, OnuRegister::MODELID_FIELD, OnuRegister::VLAN_FIELD, OnuRegister::OLTID_FIELD))) {
                if ($_POST[OnuRegister::MODELID_FIELD] != OnuRegister::MODELID_PLACEHOLDER) {
                    $mac_onu = OnuRegister::EMPTY_FIELD;
                    $save = false;
                    $router = false;
                    $login = OnuRegister::EMPTY_FIELD;
                    $PONizerAdd = false;
                    if (wf_CheckPost(array(OnuRegister::LOGIN_FIELD))) {
                        $login = $_POST[OnuRegister::LOGIN_FIELD];
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
                        $mac_onu = $_POST[OnuRegister::MAC_ONU_FIELD];
                    }
                    if (isset($_POST[OnuRegister::RANDOM_MAC_FIELD])) {
                        $mac_onu = $register->generateRandomOnuMac();
                    }
                    if (isset($_POST[OnuRegister::SAVE_FIELD])) {
                        $save = $_POST[OnuRegister::SAVE_FIELD];
                    }
                    if (isset($_POST[OnuRegister::PONIZER_ADD_FIELD])) {
                        $PONizerAdd = true;
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
                    $loginCheck = $register->checkOltParams();
                    if ($loginCheck !== OnuRegister::NO_ERROR_CONNECTION) {
                        show_error(__($loginCheck));
                    } else {
                        $register->$avidity_w($login, $mac_onu, $PONizerAdd);
                        if (empty($register->error)) {
                            show_window(__('Result'), $register->result);
                        } else {
                            show_error(__($register->error) . ': ' . count($register->existId));
                        }
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
                if (!wf_CheckPost(array(OnuRegister::VLAN_FIELD))) {
                    show_error(__(OnuRegister::ERROR_NO_VLAN_SET));
                }
            }
        } else {
            show_error(__(OnuRegister::ERROR_NO_LICENSE));
        }
    } else {
        show_error(__(OnuRegister::ERROR_NO_RIGHTS));
    }
} else {
    show_error(__(OnuRegister::ERROR_NOT_ENABLED));
}
