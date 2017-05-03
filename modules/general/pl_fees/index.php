<?php

if (cfr('PLFEES')) {

    if (isset($_GET['username'])) {
        $login = $_GET['username'];

        $funds = new FundsFlow();
        $allFees = $funds->getFees($login);

        $tablecells = wf_TableCell(__('Date'));
        $tablecells.=wf_TableCell(__('Time'));
        $tablecells.=wf_TableCell(__('Sum'));
        $tablecells.=wf_TableCell(__('From'));
        $tablecells.=wf_TableCell(__('To'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($allFees)) {
            foreach ($allFees as $io => $each) {
                $feeTimestamp = strtotime($each['date']);
                $feeDate = date("Y-m-d", $feeTimestamp);
                $feeTime = date("H:i:s", $feeTimestamp);

                $tablecells = wf_TableCell($feeDate);
                $tablecells.=wf_TableCell($feeTime);
                $tablecells.=wf_TableCell($each['summ']);
                $tablecells.=wf_TableCell($each['from']);
                $tablecells.=wf_TableCell($each['to']);
                $tablerows.=wf_TableRow($tablecells, 'row3');
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('Money fees'), $result);


        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>