<?php

$result = '';

if ($darkVoidContext['ubConfig']->getAlterParam('GENERATORS_ENABLED')) {
    if ($darkVoidContext['ubConfig']->getAlterParam('TB_GENERATORS_NOTIFY')) {
        $generatorsDevicesDb = new NyanORM(Generators::TABLE_DEVICES);
        $generatorsDevicesDb->where('running', '=', 1);

        $generatorsDevices = $generatorsDevicesDb->getAll();
        if (!empty($generatorsDevices)) {
            $runningGeneratorsCount = sizeof($generatorsDevices);
            if ($runningGeneratorsCount > 0) {
                $result .= wf_Link(Generators::URL_ME . '&' . Generators::ROUTE_DEVICES . '=true', wf_img('skins/generator32.png', __('Generators running now') . ': ' . $runningGeneratorsCount));
            }
        }
    }
}

return ($result);
