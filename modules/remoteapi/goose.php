<?php
if (ubRouting::get('action') == 'goose') {
    if ($alterconf['GOOSE_RESISTANCE']) {
        $userLogin = ubRouting::get('username');
        if (ubRouting::checkGet('username') or ubRouting::checkGet('paymentid')) {
            $userLogin = ubRouting::get('username', 'login');
            $userPaymentId = (ubRouting::checkGet('paymentid')) ?  ubRouting::get('paymentid') : '';
            $incomeAmount = (ubRouting::checkGet('amount')) ? ubRouting::get('amount') : 0;
            $explictStratId = (ubRouting::checkGet('stratid')) ? ubRouting::get('stratid') : 0;
            $runtime = (ubRouting::checkGet('runtime')) ? ubRouting::get('runtime') : '';

            $gr = new GRes();
            $gr->setUserLogin($userLogin);
            if (!empty($userPaymentId)) {
                $gr->setPaymentId($userPaymentId);
            }
            $gr->setAmount($incomeAmount);
            $gr->setRuntime($runtime);
            $strategyData = $gr->getStrategyData($explictStratId);
            header('Content-Type: application/json');
            die(json_encode($strategyData));
        } else {
            die('ERROR:NO_USERNAME_AND_PAYMENTID_PARAM');
        }
    } else {
        die('ERROR:GOOSE_RESISTANCE_DISABLED');
    }
}
