<?php

if (cfr('SWITCHESEDIT')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['SWITCHES_AUTH_ENABLED']) {

        if (ubRouting::checkGet(SwitchAuth::ROUTE_DEVID)) {
            $editSwitchId = ubRouting::get(SwitchAuth::ROUTE_DEVID, 'int');
            $switchAuth = new SwitchAuth($editSwitchId);
            //editing form data received
            if (ubRouting::checkPost($switchAuth::PROUTE_DEVID)) {
                $switchAuth->setAuthData(
                    $editSwitchId,
                    ubRouting::post($switchAuth::PROUTE_LOGIN),
                    ubRouting::post($switchAuth::PROUTE_PASSWORD),
                    ubRouting::post($switchAuth::PROUTE_ENABLE)
                );
                ubRouting::nav($switchAuth::URL_ME . '&' . $switchAuth::ROUTE_DEVID . '=' . $editSwitchId);
            }

            show_window(__('Device authorization data'), $switchAuth->renderEditForm());
            show_window('', wf_BackLink($switchAuth::URL_SWPROFILE . $editSwitchId));
        } else {
            show_error(__('Something went wrong') . ': EX_NO_SWITCHID');
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
