<?php

set_time_limit(0);

if (cfr('SWITCHPOLL')) {
    $allDevices = sp_SnmpGetAllDevices();
    $allTemplates = sp_SnmpGetAllModelTemplates();
    $allTemplatesAssoc = sp_SnmpGetModelTemplatesAssoc();
    $allusermacs = zb_UserGetAllMACs();
    $alladdress = zb_AddressGetFullCityaddresslist();
    $alldeadswitches = zb_SwitchesGetAllDead();
    $deathTime = zb_SwitchesGetAllDeathTime();
    $allModels = zb_SwitchModelsGetAllTag();

    if ($ubillingConfig->getAlterParam('SWITCHES_EXTENDED')) {
        $allswitchmacs = array();
        $allSwitches = zb_SwitchesGetAll();
        if (!empty($allSwitches)) {
            foreach ($allSwitches as $io => $each) {
                if (!empty($each['swid'])) {
                    $allswitchmacs[$each['swid']]['id'] = $each['id'];
                    $allswitchmacs[$each['swid']]['ip'] = $each['ip'];
                    $allswitchmacs[$each['swid']]['location'] = $each['location'];
                }
            }
        }
    } else {
        $allswitchmacs = array();
    }

    //poll single device
    if (ubRouting::checkGet('switchid')) {
        $switchId = ubRouting::get('switchid', 'int');
        if (!empty($allDevices)) {
            foreach ($allDevices as $ia => $eachDevice) {
                if ($eachDevice['id'] == $switchId) {
                    //detecting device template
                    if (!empty($allTemplatesAssoc)) {
                        if (isset($allTemplatesAssoc[$eachDevice['modelid']])) {
                            if (!isset($alldeadswitches[$eachDevice['ip']])) {
                                //cache cleanup
                                if (wf_CheckGet(array('forcecache'))) {
                                    $deviceRawSnmpCache = rcms_scandir('./exports/', $eachDevice['ip'] . '_*');
                                    if (!empty($deviceRawSnmpCache)) {
                                        foreach ($deviceRawSnmpCache as $ir => $fileToDelete) {
                                            unlink('./exports/' . $fileToDelete);
                                        }
                                    }
                                    rcms_redirect('?module=switchpoller&switchid=' . $eachDevice['id']);
                                }
                                $deviceTemplate = $allTemplatesAssoc[$eachDevice['modelid']];
                                $modActions = wf_BackLink('?module=switches');
                                $modActions .= wf_Link('?module=switches&edit=' . $switchId, web_edit_icon() . ' ' . __('Edit') . ' ' . __('Switch'), false, 'ubButton');
                                if (cfr('SWITCHSONIC')) {
                                    if ($ubillingConfig->getAlterParam('SWITCHSONIC_ENABLED')) {
                                        if (!empty($eachDevice['snmp'])) {
                                            $ssonicUrl = '?module=switchsonic';
                                            $ssonicUrl .= '&swid=' . $eachDevice['id'];
                                            $ssonicUrl .= '&swip=' . $eachDevice['ip'];
                                            $ssonicUrl .= '&swcomm=' . $eachDevice['snmp'];

                                            $modActions .= wf_Link($ssonicUrl, wf_img('skins/sonic_icon.png') . ' ' . __('Realtime traffic'), false, 'ubButton');
                                        }
                                    }
                                }
                                $modActions .= wf_Link('?module=switchpoller&switchid=' . $eachDevice['id'] . '&forcecache=true', wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
                                $deviceModel = (isset($allModels[$eachDevice['modelid']])) ? $allModels[$eachDevice['modelid']] : __('Model') . ' ' . __('Unknown');
                                show_window($deviceModel . ', ' . $eachDevice['ip'] . ' - ' . $eachDevice['location'], $modActions);
                                sp_SnmpPollDevice($eachDevice['ip'], $eachDevice['snmp'], $allTemplates, $deviceTemplate, $allusermacs, $alladdress, $eachDevice['snmpwrite'], false, $allswitchmacs);
                            } else {
                                show_error(__('Switch dead since') . ' ' . @$deathTime[$eachDevice['ip']]);
                                show_window('', wf_BackLink('?module=switches') . ' ' . wf_Link('?module=switches&edit=' . $switchId, web_edit_icon() . ' ' . __('Edit switch'), false, 'ubButton'));
                            }
                        } else {
                            show_error(__('No') . ' ' . __('SNMP template'));
                        }
                    }
                }
            }
        }
    } else {
        //display all of available fdb tables
        $fdbData_raw = rcms_scandir('./exports/', '*_fdb');
        if (!empty($fdbData_raw)) {
            //// mac filters setup
            if (wf_CheckPost(array('setmacfilters'))) {
                //setting new MAC filters
                if (!empty($_POST['newmacfilters'])) {
                    $newFilters = base64_encode($_POST['newmacfilters']);
                    zb_StorageSet('FDBCACHEMACFILTERS', $newFilters);
                }
                //deleting old filters
                if (isset($_POST['deletemacfilters'])) {
                    zb_StorageDelete('FDBCACHEMACFILTERS');
                }
            }

            //log download
            if (wf_CheckGet(array('dlswpolllog'))) {
                zb_FDBTableLogDownload();
            }

            //push ajax data
            if (wf_CheckGet(array('ajax'))) {
                if (wf_CheckGet(array('swfilter'))) {
                    $fdbData_raw = array($_GET['swfilter'] . '_fdb');
                }
                if (wf_CheckGet(array('macfilter'))) {
                    $macFilter = $_GET['macfilter'];
                } else {
                    $macFilter = '';
                }
                sn_SnmpParseFdbCacheJson($fdbData_raw, $macFilter);
            } else {
                if (wf_CheckGet(array('fdbfor'))) {
                    $fdbSwitchFilter = $_GET['fdbfor'];
                } else {
                    $fdbSwitchFilter = '';
                }
                if (wf_CheckGet(array('macfilter'))) {
                    $fdbMacFilter = $_GET['macfilter'];
                } else {
                    $fdbMacFilter = '';
                }

                web_FDBTableShowDataTable($fdbSwitchFilter, $fdbMacFilter);
            }
        } else {
            show_warning(__('Nothing found'));
        }
    }
} else {
    show_error(__('Access denied'));
}

