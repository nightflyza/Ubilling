<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['MAPON_ENABLED']) {
    if (cfr('MAPON')) {
        $mapsConfig = $ubillingConfig->getYmaps();
        $mapon = new MapOn();
        try {
            $lastRouteFlag = wf_CheckGet(array('alldayroutes')) ? false : true;
            $units = $mapon->getUnits();
            $unitDrivers = array();

            if (!empty($units)) {
                $placemarks = '';
                foreach ($units as $io => $each) {
                    if ($mapsConfig['MAPS_SERVICE'] != 'yandex') {
                        //extended iconset
                        switch ($each['state']) {
                            case 'standing':
                                $icon = 'redCar';
                                break;
                            case'driving':
                                $icon = 'greenCar';
                                break;
                            default :
                                $icon = 'yellowCar';
                                break;
                        }
                    } else {
                        //old school yandex icons
                        switch ($each['state']) {
                            case 'standing':
                                $icon = 'twirl#redIcon';
                                break;
                            case'driving':
                                $icon = 'twirl#greenIcon';
                                break;
                            default :
                                $icon = 'twirl#yellowIcon';
                                break;
                        }
                    }

                    if (!isset($unitDrivers[$each['unitid']])) {
                        $unitDrivers[$each['unitid']] = $each['driver'];
                    }

                    $carName = $each['driver'] . ' - ' . $each['number'];
                    $state = $each['label'] . ' ' . __($each['state']);
                    $mileage = __('Total mileage') . ': ' . ($each['mileage'] / 1000) . ' ' . __('kilometer');
                    $placemarks .= generic_mapAddMark($each['lat'] . ',' . $each['lng'], $state, $carName, $mileage, $icon, '', true);
                }

                $todayRoutes = $mapon->getTodayRoutes();

                $todayStarts = array();


                if (!empty($todayRoutes)) {
                    foreach ($todayRoutes as $io => $route) {

                        $prevCoords = '';
                        $unitId = $io;
                        $unitRouteColor = '#' . wf_genColorCodeFromText($unitId, 'Wrooom');
                        $routesCount = sizeof($route);
                        $i = 1;
                        if (!empty($route)) {
                            foreach ($route as $ia => $eachRoute) {
                                if (!empty($ia)) {
                                    foreach ($eachRoute as $ib => $each) {
                                        //first trip today (ignores first trip today by unknown reason)
                                        if (!isset($todayStarts[$unitDrivers[$unitId]])) {
                                            $todayStarts[$unitDrivers[$unitId]] = $each['time'];
                                        } else {
                                            if ($todayStarts[$unitDrivers[$unitId]] > $each['time']) {
                                                $todayStarts[$unitDrivers[$unitId]] = $each['time'];
                                            }
                                        }
                                        if (!$lastRouteFlag) {
                                            $curCoords = $each['lat'] . ',' . $each['lng'];
                                            if (!empty($prevCoords)) {
                                                $routeLabel = date("Y-m-d H:i:s", $each['time']) . ' ' . @$unitDrivers[$unitId];
                                                $placemarks .= generic_MapAddLine($curCoords, $prevCoords, $unitRouteColor, $routeLabel, 2);
                                            }
                                            $prevCoords = $curCoords;
                                        } else {
                                            //last route
                                            if ($i == $routesCount) {
                                                $curCoords = $each['lat'] . ',' . $each['lng'];
                                                if (!empty($prevCoords)) {
                                                    $routeLabel = date("Y-m-d H:i:s", $each['time']) . ' ' . @$unitDrivers[$unitId];
                                                    $placemarks .= generic_MapAddLine($curCoords, $prevCoords, $unitRouteColor, $routeLabel, 3);
                                                }
                                                $prevCoords = $curCoords;
                                            }
                                        }
                                    }
                                }
                                $i++;
                            }
                        }
                    }
                }
                


                //additional layers here
                if (ubRouting::checkGet('layerswitches')) {
                    $placemarks .= sm_MapDrawSwitches();
                }

                if (ubRouting::checkGet('layerbuilds')) {
                    $placemarks .= um_MapDrawBuilds();
                }

                if (ubRouting::checkGet('layertasks')) {
                    $taskmap = new TasksMap();
                    $placemarks .= $taskmap->getPlacemarks($taskmap->getTodayTasks());
                }


                //render map
                $container = generic_MapContainer('100%', '650px');
                $container .= generic_MapInit($mapsConfig['CENTER'], $mapsConfig['ZOOM'], $mapsConfig['TYPE'], $placemarks, '', $mapsConfig['LANG']);
                show_window(__('Cars'), $container);

                //render controls
                $controls = '';
                $controls .= wf_Link('?module=mapon', wf_img('skins/icon_last_small.png') . ' ' . __('Last trip'), false, 'ubButton') . ' ';
                $controls .= wf_Link('?module=mapon&alldayroutes=true', wf_img('skins/icon_routes_small.png') . ' ' . __('Today trips'), false, 'ubButton');
                $controls .= wf_Link('?module=mapon&layerswitches=true', wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
                $controls .= wf_Link('?module=mapon&layerbuilds=true', wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
                $controls .= wf_Link('?module=mapon&layertasks=true', wf_img('skins/track_icon.png') . ' ' . __('Tasks map'), false, 'ubButton');

                show_window('', $controls);
                zb_BillingStats(true);
            } else {
                show_warning(__('Nothing to show'));
            }
        } catch (Mapon\ApiException $e) {
            show_error(__('Something went wrong') . ': ' . 'API error code: ' . $e->getCode() . ', ' . $e->getMessage());
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>