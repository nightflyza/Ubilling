<?php

if (cfr('SWITCHMAP')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['POWMAP_ENABLED']) {
        if ($altCfg['SWYMAP_ENABLED']) {
            $reportUrl = '?module=poweroutagesmap';
            $routeSwitches = 'onlyswitches';
            $routeOnu = 'onlyonu';
            $mapsCfg = $ubillingConfig->getYmaps();
            $mapCore= new MapCore('blackoutmap');
            $mapCore->setZoom($mapsCfg['ZOOM']);
            $mapCore->setType($mapsCfg['TYPE']);
            if (!empty($mapsCfg['CENTER'])) {
                $mapCore->setCenter($mapsCfg['CENTER']);
            }


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

                                $options = array(
                                    'opacity' => $opacity,
                                    'color' => 'ac0000',
                                    'fillColor' => 'ac0000',
                                    'fillOpacity' => $opacity
                                );
                                $mapCore->addCircle($eachGeo,$radius,'',$options);
                                
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
                                    $options = array(
                                        'opacity' => $opacity,
                                        'color' => 'ac0000',
                                        'fillColor' => 'ac0000',
                                        'fillOpacity' => $opacity
                                    );
                                    $mapCore->addCircle($userData['geo'],$radius,'',$options);
                                }
                            }
                        }
                    }
                }
            }



            $map = $mapCore->renderContainer('100%', '800px;');
            $map .= $mapCore->render();
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