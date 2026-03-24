<?php

$result = '';

if ($darkVoidContext['ubConfig']->getAlterParam('GENERATORS_ENABLED')) {
    if ($darkVoidContext['ubConfig']->getAlterParam('TB_GENERATORS_LOW_FUEL_PERCENT')) {
        $lowFuelLevel = ubRouting::filters($darkVoidContext['ubConfig']->getAlterParam('TB_GENERATORS_LOW_FUEL_PERCENT'), 'int');
        if ($lowFuelLevel > 0) {
         $lowFuelCount=0;
         $generators=new Generators();
         $allDevices=$generators->getAllDevices();
         if (!empty($allDevices)) {
            $allDevicesFuelPercent=$generators->getAllDevicesFuelPercent();
            if (!empty($allDevicesFuelPercent)) {
                foreach ($allDevicesFuelPercent as $deviceId => $fuelPercent) {
                    if ($fuelPercent < $lowFuelLevel) {
                        $lowFuelCount++;
                    }
                }
            }
        }

        if ($lowFuelCount > 0) {
            $result .= wf_Link(Generators::URL_ME . '&' . Generators::ROUTE_DEVICES . '=true', wf_img('skins/fuel32.png', __('Low fuel level') . ': ' . $lowFuelCount));
        }
    }
 }
}

return ($result);