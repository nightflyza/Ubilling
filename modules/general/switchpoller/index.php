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
                if (isset($allDevices[$switchId])) {
                    $deviceData = $allDevices[$switchId];
                    //detecting device template
                    if (!empty($allTemplatesAssoc)) {
                        if (isset($allTemplatesAssoc[$deviceData['modelid']])) {
                                $switchIsAlive=(!isset($alldeadswitches[$deviceData['ip']])) ? true : false;

                                $deviceTemplate = $allTemplatesAssoc[$deviceData['modelid']];
                                //force cache cleanup
                                if (ubRouting::checkGet('forcecache')) {
                                    $deviceRawSnmpCache = rcms_scandir('./exports/', $deviceData['ip'] . '_*');
                                    if (!empty($deviceRawSnmpCache)) {
                                        foreach ($deviceRawSnmpCache as $ir => $fileToDelete) {
                                            unlink('./exports/' . $fileToDelete);
                                        }
                                    }
                                    sp_SnmpPollDevice($deviceData['ip'], $deviceData['snmp'], $allTemplates, $deviceTemplate, $allusermacs, $alladdress, $deviceData['snmpwrite'], false, $allswitchmacs);
                                    ubRouting::nav('?module=switchpoller&switchid=' . $deviceData['id']);
                                }

                                
                                $modActions = wf_BackLink('?module=switches');
                                $modActions .= wf_Link('?module=switches&edit=' . $switchId, web_edit_icon() . ' ' . __('Edit') . ' ' . __('Switch'), false, 'ubButton');
                                 //is device alive?
                                if ($switchIsAlive) {
                                if (cfr('SWITCHSONIC')) {
                                    if ($ubillingConfig->getAlterParam('SWITCHSONIC_ENABLED')) {
                                        if (!empty($deviceData['snmp'])) {
                                            $ssonicUrl = '?module=switchsonic';
                                            $ssonicUrl .= '&swid=' . $deviceData['id'];
                                            $ssonicUrl .= '&swip=' . $deviceData['ip'];
                                            $ssonicUrl .= '&swcomm=' . $deviceData['snmp'];

                                            $modActions .= wf_Link($ssonicUrl, wf_img('skins/sonic_icon.png') . ' ' . __('Realtime traffic'), false, 'ubButton');
                                        }
                                    }
                                }
                                
                                $modActions .= wf_Link('?module=switchpoller&switchid=' . $deviceData['id'] . '&forcecache=true', wf_img('skins/refresh.gif') . ' ' . __('Force query'), false, 'ubButton');
                                }
                                $deviceModel = (isset($allModels[$deviceData['modelid']])) ? $allModels[$deviceData['modelid']] : __('Model') . ' ' . __('Unknown');
                                
                                show_window($deviceModel . ', ' . $deviceData['ip'] . ' - ' . $deviceData['location'], $modActions);
                                if (!$switchIsAlive) {
                                    show_error(__('Switch dead since') . ' ' . @$deathTime[$deviceData['ip']]);
                                }
                                web_SnmpRenderDevCache($deviceData['ip'], $allTemplates, $deviceTemplate, $allusermacs, $alladdress, $allswitchmacs);
                        } else {
                            show_error(__('No') . ' ' . __('SNMP template'));
                        } 
                    } else {
                        show_error(__('SNMP template').' '.__('not found'));
                    }
                } else {
                    show_error(__('Switch').' '.__('not exists').' ['.$switchId.']');
                    show_window('', wf_BackLink('?module=switches'));
                }
            
        } else {
            show_error(__('No switches found'));
        }
    } else {

    }
} else {
    show_error(__('Access denied'));
}

