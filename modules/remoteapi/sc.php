<?php

//remote userstats credit setting requests processing
if (ubRouting::get('action') == 'sc') {
    if (ubRouting::checkGet(array('login', 'cr', 'end', 'ct'))) {
        $userLogin = ubRouting::get('login');
        $newCredit = ubRouting::get('cr');
        $creditExpire = ubRouting::get('end');
        $creditFee = ubRouting::get('fee'); //may be zero
        $creditCashType = ubRouting::get('ct', 'int');
        //setting credit and expire
        $billing->setcredit($userLogin, $newCredit);
        $billing->setcreditexpire($userLogin, $creditExpire);
        //charging some money
        zb_CashAdd($userLogin, '-' . $creditFee, 'add', $creditCashType, 'SCFEE');
        die('SC:OK');
    } else {
        die('ERROR:PARAMS_MISSED');
    }
}