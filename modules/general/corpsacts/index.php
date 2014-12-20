<?php

if (cfr('CORPS')) {
    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['CORPS_ENABLED']) {
        $greed = new Avarice();
        $beggar = $greed->runtime('CORPS');
        if (!empty($beggar)) {

            $corps = new Corps();
            $funds = new FundsFlow();

            //all that we need
            $corpsData = $corps->getCorps();
            $corpUsers = $corps->getUsers();
            $allUserContracts = zb_UserGetAllContracts();
            $allUserContracts = array_flip($allUserContracts);
            $allUsersCash = zb_UserGetAllBalance();
            $rows = '';
            $count = 0;

            //showing date search form
            show_window(__('Corporate users') . ' - ' . __('Funds flow'), $funds->renderCorpsFlowsDateForm());

            if (!wf_CheckPost(array('yearsel', 'monthsel'))) {
                $needYear = curyear();
                $needMonth = date("m");
            } else {
                $needYear = $_POST['yearsel'];
                $needMonth = $_POST['monthsel'];
            }

            //setting date filter
            $date = $needYear . '-' . $needMonth . '-';


            if (!empty($corpUsers)) {
                $rows = $funds->renderCorpsFlowsHeaders($needYear, $needMonth);

                foreach ($corpUsers as $eachlogin => $eachcorpid) {
                    $count++;
                    $fees = $funds->getFees($eachlogin);
                    $payments = $funds->getPayments($eachlogin);
                    $paymentscorr = $funds->getPaymentsCorr($eachlogin);
                    $fundsflow = $fees + $payments + $paymentscorr;
                    $dateFunds = $funds->filterByDate($fundsflow, $date);

                    $rows.=$funds->renderCorpsFlows($count, $dateFunds, $corpsData, $corpUsers, $allUserContracts, $allUsersCash);
                }

                $report = wf_TableBody($rows, '100%', 0, '');
                show_window(__('Report'), $report);
            } else {
                show_window(__('Error'), __('Nothing found'));
            }
        } else {
            show_window(__('Error'), __('No license key available'));
        }
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
} else {
    show_window(__('Error'), __('Access denied'));
}
?>