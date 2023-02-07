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
            $allUserTariffs = zb_TariffsGetAllUsers();
            $allTariffPrices = zb_TariffGetPricesAll();

            $rows = '';
            $count = 0;

            //showing date search form
            show_window(__('Corporate users') . ' - ' . __('Funds flow'), $funds->renderCorpsFlowsDateForm());

            if (!ubRouting::checkPost(array('yearsel', 'monthsel'))) {
                $needYear = curyear();
                $needMonth = date("m");
            } else {
                $needYear = ubRouting::post('yearsel');
                $needMonth = ubRouting::post('monthsel');
            }

            //setting date filter
            $date = $needYear . '-' . $needMonth . '-';


            if (!empty($corpUsers)) {
                $rows = $funds->renderCorpsFlowsHeaders($needYear, $needMonth);

                //contragent filter
                if (ubRouting::checkPost('agentsel')) {
                    $agentFilter = ubRouting::post('agentsel');
                    $allassigns = zb_AgentAssignGetAllData();
                    $allassignsStrict = zb_AgentAssignStrictGetAllData();
                    $alladdress = zb_AddressGetFulladdresslistCached();
                } else {
                    $agentFilter = '';
                }

                $funds->setDateFilter($date);
                $allCashFlows = $funds->getAllCashFlows();
                foreach ($corpUsers as $eachlogin => $eachcorpid) {
                    $count++;
                    //$fees = $funds->getFees($eachlogin);
                    //$payments = $funds->getPayments($eachlogin);
                    // $paymentscorr = $funds->getPaymentsCorr($eachlogin);

                    if (isset($allCashFlows[$eachlogin])) {
                        $dateFunds = $allCashFlows[$eachlogin];
                    } else {
                        $dateFunds = array();
                    }
                    
                    //$fundsflow = $fees + $payments + $paymentscorr;
                    //$dateFunds = $funds->filterByDate($fundsflow, $date);

                    if (!$agentFilter) {
                        $rows .= $funds->renderCorpsFlows($count, $dateFunds, $corpsData, $corpUsers, $allUserContracts, $allUsersCash, $allUserTariffs, $allTariffPrices);
                    } else {
                        @$userAddress = $alladdress[$eachlogin];
                        $assigned_agent = zb_AgentAssignCheckLoginFast($eachlogin, $allassigns, $userAddress, $allassignsStrict);
                        if ($assigned_agent == $agentFilter) {
                            $rows .= $funds->renderCorpsFlows($count, $dateFunds, $corpsData, $corpUsers, $allUserContracts, $allUsersCash, $allUserTariffs, $allTariffPrices);
                        }
                    }
                }
                $rows .= $funds->renderCorpsFlowsTotal();
                $report = wf_TableBody($rows, '100%', 0, '');
                show_window(__('Report'), $report);
            } else {
                show_warning(__('Nothing found'));
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