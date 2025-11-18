<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['MAPON_ENABLED']) {
    if (cfr('MAPON')) {
        $mapsConfig = $ubillingConfig->getYmaps();
        $mapon = new MapOn();

        try {
            $unitIdFilter = (ubRouting::checkGet('filterunit')) ? ubRouting::get('filterunit') : '';
            $lastRouteFlag = ubRouting::checkGet('alldayroutes') ? false : true;
            $dateFrom = (ubRouting::checkPost('datefrom')) ? ubRouting::post('datefrom') : curdate();
            $dateTo = (ubRouting::checkPost('dateto')) ?  ubRouting::post('dateto') : curdate();
            $units = $mapon->getUnits();
            $unitDrivers = array();

            if (!empty($units)) {
                $placemarks = '';
                foreach ($units as $io => $each) {
                    if (!empty($unitIdFilter) and $each['unitid'] != $unitIdFilter) {
                        continue;
                    }
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
                    $state = $each['label'] . ' - ' . __($each['state']);
                    $mileage = __('Total mileage') . ': ' . ($each['mileage'] / 1000) . ' ' . __('kilometer');
                    $speed = ($each['speed']) ? $each['speed'] : 0;
                    $voltage = $each['supply_voltage'];
                    $carParams = __('Speed') . ': ' . $speed . ' ' . __('km/h') . wf_tag('br');
                    $carParams .= __('Voltage') . ': ' . $voltage . ' ' . __('Volt');
                    $carParams .= wf_delimiter(1) . $each['lat'] . ',' . $each['lng'];
                    $carLabel = $mileage . wf_tag('br') . $carParams;
                    $carLinkLast = '?module=mapon&filterunit=' . $each['unitid'];
                    $carLinkToday = '?module=mapon&filterunit=' . $each['unitid'] . '&alldayroutes=true';
                    $carSearchControls = trim(wf_Link($carLinkLast, wf_img('skins/icon_search_small.gif',__('Last trip')))) . ' ';
                    $carSearchControls .= trim(wf_Link($carLinkToday, wf_img('skins/icon_time_small.png',__('All trips'))));
                    
                    $carLabel.= wf_delimiter(0).$carSearchControls;
                    $placemarks .= generic_mapAddMark($each['lat'] . ',' . $each['lng'], $state, $carName, $carLabel, $icon, '', true);
                }

                $filteredRoutes = $mapon->getDatesRoutes($dateFrom, $dateTo);

                $todayStarts = array();


                if (!empty($filteredRoutes)) {
                    foreach ($filteredRoutes as $io => $route) {
                        if (!empty($unitIdFilter) and $io != $unitIdFilter) {
                            continue;
                        }

                        $prevCoords = '';
                        $unitId = $io;
                        $unitRouteColor = '#' . wf_genColorCodeFromText($unitId, 'Wrooom!');
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

                if (ubRouting::checkGet('layeranyonetasks')) {
                    if ($ubillingConfig->getAlterParam('TASKMAN_ANYONE_EMPLOYEEID')) {
                        $anyoneEmployeeId = $ubillingConfig->getAlterParam('TASKMAN_ANYONE_EMPLOYEEID');
                        $taskmap = new TasksMap();
                        $placemarks .= $taskmap->getPlacemarks($taskmap->getTodayTasks($anyoneEmployeeId));
                    }
                }

                
                //render controls
                $controls = '';
                 //date selection form
                 $dateInputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
                 $dateInputs .= wf_DatePickerPreset('datefrom', $dateFrom) . ' ' .__('Date from').' ';
                 $dateInputs .= wf_DatePickerPreset('dateto', $dateTo) . ' ' .__('Date to').' ';
                 $dateInputs .= wf_Submit(__('Show'));
                 $dateForm = wf_Form('', 'POST', $dateInputs, 'glamour');
                 $controls .= wf_modalAuto(web_icon_calendar() . ' ' . __('Date'), __('Date'), $dateForm, 'ubButton');
                 
                if (ubRouting::checkGet('filterunit')) {
                    $controls .= wf_Link('?module=mapon', wf_img('skins/car_small.png') . ' ' . __('All').' '.__('Cars'), false, 'ubButton') . ' ';
                }
                $controls .= wf_Link('?module=mapon', wf_img('skins/icon_last_small.png') . ' ' . __('Last trip'), false, 'ubButton') . ' ';
                $controls .= wf_Link('?module=mapon&alldayroutes=true', wf_img('skins/icon_routes_small.png') . ' ' . __('All trips'), false, 'ubButton');
                $controls .= wf_Link('?module=mapon&layerswitches=true', wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
                $controls .= wf_Link('?module=mapon&layerbuilds=true', wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
                $controls .= wf_Link('?module=mapon&layertasks=true', wf_img('skins/track_icon.png') . ' ' . __('Tasks'), false, 'ubButton');
             
                //tasks for anyone optional control here
                if ($ubillingConfig->getAlterParam('TASKMAN_ANYONE_EMPLOYEEID')) {
                    $controls .= wf_Link('?module=mapon&layeranyonetasks=true', wf_img('skins/backprofile.png') . ' ' . __('Unallocated tasks'), false, 'ubButton');
                }

                show_window('', $controls);

                //render map
                $container = generic_MapContainer('100%', '650px');
                $editor = generic_MapEditor('maponpointlocation', __('Place coordinates'), '');
                $container .= generic_MapInit($mapsConfig['CENTER'], $mapsConfig['ZOOM'], $mapsConfig['TYPE'], $placemarks, $editor, $mapsConfig['LANG']);
                
                show_window(__('Cars'), $container);

                zb_BillingStats(true);
            } else {
                show_warning(__('Nothing to show'));
            }
        } catch (ApiException $e) {
            show_error(__('Something went wrong') . ': ' . 'API error code: ' . $e->getCode() . ', ' . $e->getMessage());
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
