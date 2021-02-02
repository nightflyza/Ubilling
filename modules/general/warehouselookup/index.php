<?php

if (cfr('WAREHOUSELOOKUP')) {
    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['WAREHOUSE_ENABLED']) {
        $greed = new Avarice();
        $avidity = $greed->runtime('WAREHOUSE');
        if (!empty($avidity)) {
            $userLogin = ubRouting::get('username', 'mres');
            if (!empty($userLogin)) {
                $warehouse = new Warehouse();
                $allUserAddress = zb_AddressGetFulladdresslistCached();
                $userAddress = @$allUserAddress[$userLogin];
                $allUserTasks = ts_PreviousUserTasksRender($userLogin, $userAddress, false, true);

                $materialsReport = $warehouse->userSpentMaterialsReport($allUserTasks, $userLogin);
                show_window(__('Additionally spent materials') . ' ' . __('for') . ' ' . $userAddress, $materialsReport);
                show_window('', web_UserControls($userLogin));
            } else {
                show_error(__('Something went wrong') . ': EX_EMPTY_LOGIN');
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}