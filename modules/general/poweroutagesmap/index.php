<?php

if (cfr('SWITCHMAP')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['POWMAP_ENABLED']) {
        if ($altCfg['SWYMAP_ENABLED']) {
            $reportUrl = '?module=poweroutagesmap';
            $routeSwitches = 'onlyswitches';
            $routeOnu = 'onlyonu';
            $mapsCfg = $ubillingConfig->getYmaps();



            if ($altCfg['PON_ENABLED'] AND $altCfg['PONMAP_ENABLED']) {
                $controls = wf_Link($reportUrl, wf_img('skins/ponmap_icon.png') . ' ' . __('All'), false, 'ubButton') . ' ';
                $controls .= wf_Link($reportUrl . '&' . $routeSwitches . '=true', wf_img('skins/ymaps/network.png') . ' ' . __('Switches'), false, 'ubButton') . ' ';
                $controls .= wf_Link($reportUrl . '&' . $routeOnu . '=true', wf_img('skins/switch_models.png') . ' ' . __('ONU'), false, 'ubButton') . ' ';
                show_window('', $controls);
            }

            $deadSwitches = zb_SwitchesGetAllDead();
            $allSwitchesData = zb_SwitchesGetAll();

            $allUserData = zb_UserGetAllDataCache();
            $placemarks = '';

            $allSwitchesGeo = array();
            $allSwitchesDesc = array();
            if (!empty($allSwitchesData)) {
                foreach ($allSwitchesData as $io => $each) {
                    if (!empty($each['ip']) AND ! empty($each['geo'])) {
                        $allSwitchesGeo[$each['ip']] = $each['geo'];
                        $allSwitchesDesc[$each['ip']] = $each['desc'];
                    }
                }
            }



            if (!ubRouting::checkGet($routeOnu)) {
                if (!empty($allSwitchesGeo)) {
                    foreach ($allSwitchesGeo as $switchIp => $eachGeo) {
                        if (!empty($switchIp) AND ! empty($eachGeo)) {
                            if (isset($deadSwitches[$switchIp])) {
                                $radius = 100;
                                $opacity = 1;
                                $eachDesc = $allSwitchesDesc[$switchIp];
                                if (ispos($eachDesc, 'OLT')) {
                                    $radius = 1000;
                                    $opacity = 0.5;
                                }
                                $placemarks .= generic_MapAddCircle($eachGeo, $radius, '', '', 'ac0000', $opacity, 'ac0000', $opacity);
                            }
                        }
                    }
                }
            }

            if ($altCfg['PONMAP_ENABLED'] AND $altCfg['PON_ENABLED'] AND ! ubRouting::checkGet($routeSwitches)) {
                $ponizer = new PONizer();
                $allDeregReasons = $ponizer->getAllONUDeregReasons();
                if (!empty($allDeregReasons)) {
                    foreach ($allDeregReasons as $eachLogin => $eachDereg) {
                        if ($eachDereg['raw'] == 'Power off') {
                            if (isset($allUserData[$eachLogin])) {
                                $userData = $allUserData[$eachLogin];
                                if (!empty($userData['geo'])) {
                                    $radius = 20;
                                    $opacity = 1;
                                    $placemarks .= generic_MapAddCircle($userData['geo'], $radius, '', '', 'ac0000', $opacity, 'ac0000', $opacity);
                                }
                            }
                        }
                    }
                }
            }



            $map = generic_MapContainer('100%', '800px;', 'blackoutmap');
            $map .= generic_MapInit($mapsCfg['CENTER'], $mapsCfg['ZOOM'], 'map', $placemarks, '', $mapsCfg['LANG'], 'blackoutmap');
            show_window(__('Power outages') . '?', $map);
        } else {
            show_error(__('This module is disabled'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}