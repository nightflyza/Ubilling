<?php

$result = '';

if ($darkVoidContext['altCfg']['TB_SWITCHMON']) {
    $dead_raw = zb_StorageGet('SWDEAD');
    $last_pingtime = zb_StorageGet('SWPINGTIME');

    if (!is_numeric($last_pingtime)) {
        $last_pingtime = 0;
    }
    $deathTime = zb_SwitchesGetAllDeathTime();
    $deadarr = array();
    $content = '';

    if ($darkVoidContext['altCfg']['SWYMAP_ENABLED']) {
        $content = wf_Link('?module=switchmap', wf_img('skins/swmapsmall.png', __('Switches map')), false);
    }

    $content .= wf_AjaxLoader() . wf_AjaxLink("?module=switches&forcereping=true&ajaxping=true", wf_img('skins/refresh.gif', __('Force ping')), 'switchping', true, '');

    if ($dead_raw) {
        $deadarr = unserialize($dead_raw);
        if (!empty($deadarr)) {
            $deadcount = sizeof($deadarr);
            if ($darkVoidContext['altCfg']['SWYMAP_ENABLED']) {
                $switchesGeo = zb_SwitchesGetAllGeo();
            }
            $content .= wf_tag('div', false, '', 'id="switchping"');

            foreach ($deadarr as $ip => $switch) {
                if ($darkVoidContext['altCfg']['SWYMAP_ENABLED']) {
                    if (isset($switchesGeo[$ip])) {
                        if (!empty($switchesGeo[$ip])) {
                            $devicefind = wf_Link('?module=switchmap&finddevice=' . $switchesGeo[$ip], wf_img('skins/icon_search_small.gif', __('Find on map'))) . ' ';
                        } else {
                            $devicefind = '';
                        }
                    } else {
                        $devicefind = '';
                    }
                } else {
                    $devicefind = '';
                }

                if (isset($deathTime[$ip])) {
                    $deathClock = wf_img('skins/clock.png', __('Switch dead since') . ' ' . $deathTime[$ip]) . ' ';
                } else {
                    $deathClock = '';
                }
                $switchLocator = wf_Link('?module=switches&gotoswitchbyip=' . $ip, web_edit_icon(__('Go to switch')));
                $content .= $devicefind . ' ' . $switchLocator . ' ' . $deathClock . $ip . ' - ' . $switch . '<br>';
            }

            $content .= wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s", $last_pingtime) . wf_tag('div', true);
            $result .= wf_tag('div', false, 'ubButton') . wf_modal(__('Dead switches') . ': ' . $deadcount, __('Dead switches'), $content, '', '500', '400') . wf_tag('div', true);
        } else {
            $content .= wf_tag('div', false, '', 'id="switchping"') . __('Switches are okay, everything is fine - I guarantee') . wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s", $last_pingtime) . wf_tag('div', true);
            $result .= wf_tag('div', false, 'ubButton') . wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400') . wf_tag('div', true);
        }
    } else {
        $content .= wf_tag('div', false, '', 'id="switchping"') . __('Switches are okay, everything is fine - I guarantee') . wf_delimiter() . __('Cache state at time') . ': ' . @date("H:i:s", $last_pingtime) . wf_tag('div', true);
        $result .= wf_tag('div', false, 'ubButton') . wf_modal(__('All switches alive'), __('All switches alive'), $content, '', '500', '400') . wf_tag('div', true);
    }
}

return ($result);
