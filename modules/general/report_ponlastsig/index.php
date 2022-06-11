<?php

if (cfr('PON')) {
    if ($ubillingConfig->getAlterParam('PON_ENABLED')) {
        $signupsDb = new NyanORM('userreg');
        $signupsDb->where('date', 'LIKE', curmonth() . '%');
        $signupsDb->orderBy('date', 'DESC');
        $curMonthSignups = $signupsDb->getAll('id');

        if (!empty($curMonthSignups)) {
            $ponizer = new PONizer();
            $userCities = zb_AddressGetCityUsers();

            $cells = wf_TableCell(__('ONU'));
            $cells .= wf_TableCell(__('Date'));
            $cells .= wf_TableCell(__('Signal'));
            $cells .= wf_TableCell(__('Notes'));
            $cells .= wf_TableCell(__('City'));
            $cells .= wf_TableCell(__('Address'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($curMonthSignups as $io => $eachSignup) {
                $rowClass = 'row5';
                $userOnuId = $ponizer->getOnuIdByUser($eachSignup['login']);
                //is ONU assigned?
                if ($userOnuId) {
                    $onuSignalData = $ponizer->getOnuSignalLevelData($userOnuId);
                    if (!empty($onuSignalData)) {
                        $cells = wf_TableCell(wf_Link($ponizer::URL_ONU . $userOnuId, $userOnuId));
                        $cells .= wf_TableCell($eachSignup['date']);
                        $cells .= wf_TableCell($onuSignalData['styled'], '', '', 'sorttable_customkey="' . $onuSignalData['raw'] . '"');
                        $cells .= wf_TableCell(__($onuSignalData['type']), '', '', 'sorttable_customkey="' . $onuSignalData['raw'] . '"');
                        $cells .= wf_TableCell(@$userCities[$eachSignup['login']]);
                        $profilelink = wf_Link(UserProfile::URL_PROFILE . $eachSignup['login'], web_profile_icon() . ' ' . $eachSignup['address']);
                        $cells .= wf_TableCell($profilelink);
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                }
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            show_window(__('Latest ONU signals'), $result);
        } else {
            show_warning(__('Nothing to show'));
        }

        show_window('', wf_BackLink('?module=report_signup'));
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}