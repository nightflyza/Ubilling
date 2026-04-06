<?php


$clapTrapEnabled = $ubillingConfig->getAlterParam('CLAPTRAPBOT_ENABLED');
if ($clapTrapEnabled) {
    if (cfr('ROOT')) {
        $clapTrapMgr = new ClapTrapMgr();

        //Main controls here
        show_window('', $clapTrapMgr->renderControls());

        if (ubRouting::checkGet($clapTrapMgr::ROUTE_CONFIG)) {
            if (ubRouting::checkPost($clapTrapMgr::PROUTE_HOOK_URL)) {
                $installResult = $clapTrapMgr->installHook(ubRouting::post($clapTrapMgr::PROUTE_HOOK_URL));
                if (!empty($installResult)) {
                    show_window(__('Hook installation result'), $installResult);
                }
            }

        show_window(__('Actual bot hook state'), $clapTrapMgr->renderHookInfo($clapTrapMgr->getActualHookInfo()));
        show_window(__('Install hook'), $clapTrapMgr->renderInstallHookForm());
        } else {
            show_window(__('Users'), $clapTrapMgr->renderAuthData());
        }
        zb_BillingStats();
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}

