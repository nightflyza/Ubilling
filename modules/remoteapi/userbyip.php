<?php

if (ubRouting::get('action') == 'userbyip') {
    if (@$alterconf['USERBYIP_ENABLED']) {
        if (ubRouting::checkGet('ip')) {
            $ip = ubRouting::get('ip', 'mres');
            $guessedLogin = zb_UserGetLoginByIp($ip);
            $result = array('result' => 0);
            if (!empty($guessedLogin)) {
                $allUserData = zb_UserGetAllDataCache();
                if (isset($allUserData[$guessedLogin])) {
                    $result['result'] = 1;
                    $result['userdata'] = $allUserData[$guessedLogin];
                    if ($alterconf['OPENPAYZ_SUPPORT']) {
                        $openPayz = new OpenPayz(false, false);
                        $result['userdata']['paymentid'] = $openPayz->getCustomerPaymentId($guessedLogin);
                    }
                }
            }


            header('Last-Modified: ' . gmdate('r'));
            header('Content-Type: application/json; charset=UTF-8');
            header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
            header("Pragma: no-cache");
            header('Access-Control-Allow-Origin: *');

            print(json_encode($result));
            die();
        } else {
            die('ERROR: EMPTY IP');
        }
    } else {
        die('ERROR: USERBYIP DISABLED');
    }
}
