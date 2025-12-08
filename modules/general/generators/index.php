<?php

if (cfr('GENERATORS')) {
    if ($ubillingConfig->getAlterParam('GENERATORS_ENABLED')) {
        $generators = new Generators();

        if (ubRouting::checkPost($generators::PROUTE_NEW_DEVICE)) {
            $generators->createDevice();
            ubRouting::nav($generators::URL_ME . '&' . $generators::ROUTE_DEVICES . '=true');
        }
        
        if (ubRouting::checkPost($generators::PROUTE_EDIT_DEVICE)) {
            
            $editDeviceId = ubRouting::post($generators::PROUTE_EDIT_DEVICE, 'int');
            $editResult = $generators->updateDevice($editDeviceId);
            if ($editResult) {
                show_error($editResult);
            } else {
                ubRouting::nav($generators::URL_ME . '&' . $generators::ROUTE_DEVICES . '=true');
            }
        }

        if (ubRouting::checkPost($generators::PROUTE_REFUEL_DEVICE)) {
            $refuelDeviceId = ubRouting::post($generators::PROUTE_REFUEL_DEVICE, 'int');
            $refuelResult = $generators->createRefuel($refuelDeviceId);
            if ($refuelResult) {
                show_error($refuelResult);
            } else {
                ubRouting::nav($generators::URL_ME . '&' . $generators::ROUTE_DEVICES . '=true');
            }
        }

        if (ubRouting::checkGet($generators::ROUTE_START_DEVICE)) {
            $startResult = $generators->startDevice(ubRouting::get($generators::ROUTE_START_DEVICE));
            if ($startResult) {
                show_error($startResult);
            } else {
                ubRouting::nav($generators::URL_ME . '&' . $generators::ROUTE_DEVICES . '=true');
            }
        }

        if (ubRouting::checkGet($generators::ROUTE_STOP_DEVICE)) {
            $stopResult = $generators->stopDevice(ubRouting::get($generators::ROUTE_STOP_DEVICE));
            if ($stopResult) {
                show_error($stopResult);
            } else {
                ubRouting::nav($generators::URL_ME . '&' . $generators::ROUTE_DEVICES . '=true');
            }
        }

        if (ubRouting::checkGet($generators::ROUTE_DELETE_DEVICE)) {
            $deletionResult = $generators->deleteDevice(ubRouting::get($generators::ROUTE_DELETE_DEVICE));
            if ($deletionResult) {
                show_error($deletionResult);
            } else {
                ubRouting::nav($generators::URL_ME . '&' . $generators::ROUTE_DEVICES . '=true');
            }
        }

        show_window('', $generators->renderControls());

        //events view
        if (ubRouting::checkGet($generators::ROUTE_VIEW_EVENTS)) {
            $deviceId = ubRouting::get($generators::ROUTE_VIEW_EVENTS, 'int');
            $deviceInfo = $generators->getDeviceInfo($deviceId);
            $deviceName = '';
            if (!empty($deviceInfo)) {
                $deviceName = $deviceInfo['model'] . ' - ' . $deviceInfo['address'];
            }
            show_window(__('Events') . ': ' . $deviceName, $generators->renderDeviceEvents($deviceId));
        } 
        
        //devices list view
        if (ubRouting::checkGet($generators::ROUTE_DEVICES)) {
            show_window(__('Available generators'), $generators->renderDevicesList());
        }
        
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}