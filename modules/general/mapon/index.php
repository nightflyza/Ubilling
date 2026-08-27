<?php

$altCfg = $ubillingConfig->getAlter();
$ajaxUnits = ubRouting::checkGet(MapOn::ROUTE_AJAX_UNITS);
if ($altCfg['MAPON_ENABLED']) {
    if (cfr('MAPON')) {
        $mapsConfig = $ubillingConfig->getYmaps();
        $mapon = new MapOn();

        try {
            $unitIdFilter = (ubRouting::checkGet(MapOn::ROUTE_FILTER_UNIT)) ? ubRouting::get(MapOn::ROUTE_FILTER_UNIT) : '';
            if ($ajaxUnits) {
                header('Content-Type: application/json');
                die(json_encode($mapon->getLiveUnitsData($unitIdFilter)));
            }
            $lastRouteFlag = ubRouting::checkGet(MapOn::ROUTE_ALLDAY_ROUTES) ? false : true;
            $dateFrom = (ubRouting::checkPost(MapOn::PROUTE_DATE_FROM)) ? ubRouting::post(MapOn::PROUTE_DATE_FROM) : curdate();
            $dateTo = (ubRouting::checkPost(MapOn::PROUTE_DATE_TO)) ?  ubRouting::post(MapOn::PROUTE_DATE_TO) : curdate();
            $units = $mapon->getUnits();
            $unitDrivers = array();
            $lastDrivingCoords = '';

            if (!empty($units)) {
                $mapCore = new MapCore('maponvehicles');
                $mapCore->setZoom($mapsConfig['ZOOM']);
                $mapCore->setType($mapsConfig['TYPE']);

                if (!empty($mapsConfig['CENTER'])) {
                    $mapCore->setCenter($mapsConfig['CENTER']);
                }
                

                //additional layers should be rendered first, under vehicles/routes
                if (ubRouting::checkGet(MapOn::ROUTE_LAYER_SWITCHES)) {
                    $switchMap = new SwitchMap();
                    $mapCore->injectPlacemarks($switchMap->getSwitchesPlacemarks());
                }

                if (ubRouting::checkGet(MapOn::ROUTE_LAYER_BUILDS)) {
                    $buildsMap = new BuildsMap();
                    $mapCore->injectPlacemarks($buildsMap->getBuildsPlacemarks());
                }

                if (ubRouting::checkGet(MapOn::ROUTE_LAYER_TASKS)) {
                    $taskmap = new TasksMap();
                    $mapCore->injectPlacemarks($taskmap->getPlacemarks($taskmap->getTodayTasks()));
                }

                if (ubRouting::checkGet(MapOn::ROUTE_LAYER_ANYONE_TASKS)) {
                    if ($ubillingConfig->getAlterParam('TASKMAN_ANYONE_EMPLOYEEID')) {
                        $anyoneEmployeeId = $ubillingConfig->getAlterParam('TASKMAN_ANYONE_EMPLOYEEID');
                        $taskmap = new TasksMap();
                        $mapCore->injectPlacemarks($taskmap->getPlacemarks($taskmap->getTodayTasks($anyoneEmployeeId)));
                    }
                }

                foreach ($units as $io => $each) {
                    if (!empty($unitIdFilter) and $each['unitid'] != $unitIdFilter) {
                        continue;
                    }

                    if (!isset($unitDrivers[$each['unitid']])) {
                        $unitDrivers[$each['unitid']] = $each['driver'];
                    }

                    $markerData = $mapon->getUnitMarkerData($each);
                    $markerOptions = array(
                        'id' => $markerData['id'],
                        'icon' => $markerData['icon'],
                        'popupTitle' => $markerData['popupTitle'],
                        'popupFooter' => $markerData['popupFooter']
                    );
                    $mapCore->addMarker($markerData['lat'] . ',' . $markerData['lng'], $markerData['popupContent'], $markerOptions);

                    if ($each['state'] == 'driving') {
                        $lastDrivingCoords = array(
                            'lat' => floatval($each['lat']),
                            'lng' => floatval($each['lng']),
                            'name' => $markerData['popupContent']
                        );
                    }
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
                                                $mapCore->addLine($curCoords, $prevCoords, array('color' => $unitRouteColor, 'hint' => $routeLabel, 'width' => 2));
                                            }
                                            $prevCoords = $curCoords;
                                        } else {
                                            //last route
                                            if ($i == $routesCount) {
                                                $curCoords = $each['lat'] . ',' . $each['lng'];
                                                if (!empty($prevCoords)) {
                                                    $routeLabel = date("Y-m-d H:i:s", $each['time']) . ' ' . @$unitDrivers[$unitId];
                                                    $mapCore->addLine($curCoords, $prevCoords, array('color' => $unitRouteColor, 'hint' => $routeLabel, 'width' => 3));
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

                //render controls
                $controls = '';
                 //date selection form
                 $dateInputs = '<!--ugly hack to prevent datepicker autoopen --> <input type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
                 $dateInputs .= wf_DatePickerPreset(MapOn::PROUTE_DATE_FROM, $dateFrom) . ' ' .__('Date from').' ';
                 $dateInputs .= wf_DatePickerPreset(MapOn::PROUTE_DATE_TO, $dateTo) . ' ' .__('Date to').' ';
                 $dateInputs .= wf_Submit(__('Show'));
                 $dateForm = wf_Form('', 'POST', $dateInputs, 'glamour');
                 $controls .= wf_modalAuto(web_icon_calendar() . ' ' . __('Date'), __('Date'), $dateForm, 'ubButton');
                 
                if (ubRouting::checkGet(MapOn::ROUTE_FILTER_UNIT)) {
                    $controls .= wf_Link(MapOn::URL_ME, wf_img('skins/car_small.png') . ' ' . __('All').' '.__('Cars'), false, 'ubButton') . ' ';
                }
                $controls .= wf_Link(MapOn::URL_ME, wf_img('skins/icon_last_small.png') . ' ' . __('Last trip'), false, 'ubButton') . ' ';
                $controls .= wf_Link(MapOn::URL_ME . '&' . MapOn::ROUTE_ALLDAY_ROUTES . '=true', wf_img('skins/icon_routes_small.png') . ' ' . __('All trips'), false, 'ubButton');
                $controls .= wf_Link(MapOn::URL_ME . '&' . MapOn::ROUTE_LAYER_SWITCHES . '=true', wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
                $controls .= wf_Link(MapOn::URL_ME . '&' . MapOn::ROUTE_LAYER_BUILDS . '=true', wf_img('skins/ymaps/build.png') . ' ' . __('Builds map'), false, 'ubButton');
                $controls .= wf_Link(MapOn::URL_ME . '&' . MapOn::ROUTE_LAYER_TASKS . '=true', wf_img('skins/track_icon.png') . ' ' . __('Tasks'), false, 'ubButton');
             
                //tasks for anyone optional control here
                if ($ubillingConfig->getAlterParam('TASKMAN_ANYONE_EMPLOYEEID')) {
                    $controls .= wf_Link(MapOn::URL_ME . '&' . MapOn::ROUTE_LAYER_ANYONE_TASKS . '=true', wf_img('skins/backprofile.png') . ' ' . __('Unallocated tasks'), false, 'ubButton');
                }

                show_window('', $controls);

                //random cake
                
                if (!empty($lastDrivingCoords)) {
                    $mapCore->registerIcon('marker.cake', 'skins/cake32.png');
                    $maxOffset = 1000;
                    $latOffset = mt_rand(-$maxOffset, $maxOffset) / 10000;
                    $lngOffset = mt_rand(-$maxOffset, $maxOffset) / 10000;
                    $cakeLat = $lastDrivingCoords['lat'] + $latOffset;
                    $cakeLng = $lastDrivingCoords['lng'] + $lngOffset;
                    $cakeCoords = $cakeLat . ',' . $cakeLng;
                    $cakeTitle = __('Cake nearby') . ': ' . $lastDrivingCoords['name'];
                    $cakeOptions = array(
                        'icon' => 'marker.cake',
                    );
                    $mapCore->addMarker($cakeCoords, __('The cake is a lie'), $cakeOptions);
                }
                

                //render map
                $mapCore->addLocationEditor(MapOn::PROUTE_POINT_LOCATION, __('Place coordinates'), '');
                if ($mapon->isLiveRefreshEnabled()) {
                    $mapCore->addRawJs($mapon->getLiveUpdateJs($mapon->getLiveAjaxUrl($unitIdFilter)));
                }
                $container = $mapCore->renderContainer('100%', '650px');
                $container .= $mapCore->render();
                
                show_window(__('Cars'), $container);

                zb_BillingStats(true);
            } else {
                show_warning(__('Nothing to show'));
            }
        } catch (ApiException $e) {
            if ($ajaxUnits) {
                header('Content-Type: application/json');
                die(json_encode(array()));
            }
            show_error(__('Something went wrong') . ': ' . 'API error code: ' . $e->getCode() . ', ' . $e->getMessage());
        }
    } else {
        if ($ajaxUnits) {
            header('Content-Type: application/json');
            die(json_encode(array()));
        }
        show_error(__('Access denied'));
    }
} else {
    if ($ajaxUnits) {
        header('Content-Type: application/json');
        die(json_encode(array()));
    }
    show_error(__('This module is disabled'));
}
