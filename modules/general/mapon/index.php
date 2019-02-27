<?php

$altCfg = $ubillingConfig->getAlter();
if ($altCfg['MAPON_ENABLED']) {
    if (cfr('MAPON')) {
        $mapsConfig = $ubillingConfig->getYmaps();
        $mapon = new MapOn();
        try {
            $units = $mapon->getUnits();
            $unitDrivers = array();

            if (!empty($units)) {
                $placemarks = '';
                foreach ($units as $io => $each) {
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

                    if (!isset($unitDrivers[$each['unitid']])) {
                        $unitDrivers[$each['unitid']] = $each['driver'];
                    }

                    $carName = $each['driver'] . ' - ' . $each['number'];
                    $state = $each['label'] . ' ' . __($each['state']);
                    $mileage = __('Total mileage') . ': ' . ($each['mileage'] / 1000) . ' ' . __('kilometer');
                    $placemarks.=generic_mapAddMark($each['lat'] . ',' . $each['lng'], $state, $carName, $mileage, $icon, '', true);
                }

                $todayRoutes = $mapon->getTodayRoutes();

                if (!empty($todayRoutes)) {
                    foreach ($todayRoutes as $io => $route) {
                        $prevCoords = '';
                        $unitId = $io;
                        $unitRouteColor = '#' . substr(md5($unitId), 0, 6);
                        if (!empty($route)) {
                            foreach ($route as $ia => $each) {
                                $curCoords = $each['lat'] . ',' . $each['lng'];
                                if (!empty($prevCoords)) {
                                    $routeLabel = date("H:i:s", $each['time']) . ' ' . @$unitDrivers[$unitId];
                                    $placemarks.=generic_MapAddLine($curCoords, $prevCoords, $unitRouteColor, $routeLabel, 2);
                                }
                                $prevCoords = $curCoords;
                            }
                        }
                    }
                }

                $container = generic_MapContainer();
                $container.=generic_MapInit($mapsConfig['CENTER'], $mapsConfig['ZOOM'], $mapsConfig['TYPE'], $placemarks, '', $mapsConfig['LANG']);
                //render map
                show_window(__('Cars'), $container);
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