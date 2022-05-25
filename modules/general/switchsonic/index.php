<?php

if (cfr('SWITCHSONIC')) {
    if ($ubillingConfig->getAlterParam('SWITCHSONIC_ENABLED')) {
        if (ubRouting::checkGet(array('swip', 'swcomm'))) {
            $swId = ubRouting::get('swid');
            $swIp = ubRouting::get('swip');
            $swComm = ubRouting::get('swcomm');
            $displayMode = ubRouting::get('mode');
            $baseUrl = '?module=switchsonic';
            $baseUrl .= '&swid=' . $swId;
            $baseUrl .= '&swip=' . $swIp;
            $baseUrl .= '&swcomm=' . $swComm;

            $sonicTimeout = $ubillingConfig->getAlterParam('SWITCHSONIC_TIMEOUT');
            if (!$sonicTimeout) {
                $sonicTimeout = 5; //default if not set obviously
            }
            $sonicTimeout = ubRouting::filters($sonicTimeout, 'int') * 1000; //in ms.

            $controls = '';
            if (!empty($swId)) {
                $controls .= wf_BackLink('?module=switchpoller&switchid=' . $swId);
            }
            $controls .= wf_Link($baseUrl, wf_img('skins/sonic_icon.png') . ' ' . __('Realtime traffic'), false, 'ubButton');
            $controls .= wf_Link($baseUrl . '&mode=charts', wf_img('skins/realtime_icon.png') . ' ' . __('Realtime charts'), false, 'ubButton');
            $controls .= wf_Link($baseUrl . '&mode=freeze', wf_img('skins/icon_lock.png') . ' ' . __('Freeze dont move'), false, 'ubButton');

            show_window('', $controls);


            $sonic = new SwitchSonic($swIp, $swComm);
            if ($sonic->checkAuth()) {
                if (empty($displayMode)) {
                    $zenFlow = new ZenFlow('ajswsonic', $sonic->renderSpeeds(), $sonicTimeout);
                    show_window(__('Realtime bandwidth monitor'), $zenFlow->render());
                } else {
                    if ($displayMode == 'charts') {
                        $zenFlow = new ZenFlow('ajswsonic', $sonic->renderCharts(), ($sonicTimeout + 1000));
                        show_window(__('Realtime charts'), $zenFlow->render());
                    }

                    if ($displayMode == 'freeze') {
                        show_window(__('Traffic'), $sonic->renderSpeeds());
                        show_window(__('Graphs'), $sonic->renderCharts());
                    }
                }
                zb_BillingStats(true);
            } else {
                show_error(__('Something went wrong') . ': ' . __('Authorization failed'));
            }
        } else {
            show_error(__('Something went wrong'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}