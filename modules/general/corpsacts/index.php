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
            $allUsersCash = zb_UserGetAllBalance();
            $allUserTariffs= zb_TariffsGetAllUsers();
            $allTariffPrices=  zb_TariffGetPricesAll();
            
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
                
                //contragent filter
                if (wf_CheckPost(array('agentsel'))) {
                   $agentFilter=$_POST['agentsel'];
                    $allassigns=zb_AgentAssignGetAllData();
                    $allassignsStrict= zb_AgentAssignStrictGetAllData();
                    $alladdress=  zb_AddressGetFulladdresslistCached();
                } else {
                    $agentFilter='';
                }

                foreach ($corpUsers as $eachlogin => $eachcorpid) {
                    $count++;
                    $fees = $funds->getFees($eachlogin);
                    $payments = $funds->getPayments($eachlogin);
                    $paymentscorr = $funds->getPaymentsCorr($eachlogin);
                    $fundsflow = $fees + $payments + $paymentscorr;
                    $dateFunds = $funds->filterByDate($fundsflow, $date);
                    
                    if (!$agentFilter) { 
                    $rows.=$funds->renderCorpsFlows($count, $dateFunds, $corpsData, $corpUsers, $allUserContracts, $allUsersCash,$allUserTariffs,$allTariffPrices);
                    } else {
                        @$userAddress=$alladdress[$eachlogin];
                        $assigned_agent=  zb_AgentAssignCheckLoginFast($eachlogin, $allassigns, $userAddress,$allassignsStrict);
                        if ($assigned_agent==$agentFilter) {
                            $rows.=$funds->renderCorpsFlows($count, $dateFunds, $corpsData, $corpUsers, $allUserContracts, $allUsersCash,$allUserTariffs,$allTariffPrices);
                        }
                    }
                }
                $rows.=$funds->renderCorpsFlowsTotal();
                $report = wf_TableBody($rows, '100%', 0, '');
                show_window(__('Report'), $report);
            } else {
                show_warning(__('Nothing found'));
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error( __('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>