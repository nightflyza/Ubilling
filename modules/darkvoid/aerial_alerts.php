<?php

$result = '';

if ($darkVoidContext['ubConfig']->getAlterParam('AERIAL_ALERTS_ENABLED')) {
    if ($darkVoidContext['ubConfig']->getAlterParam('AERIAL_ALERTS_NOTIFY')) {
        $monitorRegion = $darkVoidContext['ubConfig']->getAlterParam('AERIAL_ALERTS_NOTIFY');
        $aerialAlerts = new AerialAlerts($monitorRegion);
        $regionAlert = $aerialAlerts->renderRegionNotification($monitorRegion);
        if (!empty($regionAlert)) {
            $result .= $regionAlert;
        }
    }
}

return ($result);
