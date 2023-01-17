<?php

if (cfr('PLFEES')) {

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username');

        $funds = new FundsFlow();
        $allFees = $funds->getFees($login);
        if (!empty($allFees)) {

            $tablecells = wf_TableCell(__('Date'));
            $tablecells .= wf_TableCell(__('Time'));
            $tablecells .= wf_TableCell(__('Sum'));
            $tablecells .= wf_TableCell(__('From'));
            $tablecells .= wf_TableCell(__('To'));
            $tablerows = wf_TableRow($tablecells, 'row1');

            if (!empty($allFees)) {
                foreach ($allFees as $io => $each) {
                    $feeTimestamp = strtotime($each['date']);
                    $feeDate = date("Y-m-d", $feeTimestamp);
                    $feeTime = date("H:i:s", $feeTimestamp);

                    $tablecells = wf_TableCell($feeDate);
                    $tablecells .= wf_TableCell($feeTime);
                    $tablecells .= wf_TableCell($each['summ']);
                    $tablecells .= wf_TableCell($each['from']);
                    $tablecells .= wf_TableCell($each['to']);
                    $tablerows .= wf_TableRow($tablecells, 'row3');
                }
            }

            $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        } else {
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        show_window(__('Money fees'), $result);


        $moduleControls = '';
        //manual fees harvester controls here
        if (cfr('ROOT')) {
            if ($ubillingConfig->getAlterParam('FEES_HARVESTER')) {
                $moduleControls .= wf_Link('?module=feesharvesterimport&username=' . $login, wf_img('skins/icon_restoredb.png') . ' ' . __('Migrate previous fees data into database'), false, 'ubButton');
                $moduleControls .= wf_delimiter();
            }
        }

        $moduleControls .= web_UserControls($login);

        show_window('', $moduleControls);
    } else {
        show_error(__('Strange exception') . ': ' . __('Empty login'));
        show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
    }
} else {
    show_error(__('You cant control this module'));
}
