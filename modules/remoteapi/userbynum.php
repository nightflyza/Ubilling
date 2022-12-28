<?php

if (ubRouting::get('action') == 'userbynum') {
    if (@$alterconf['USERBYNUM_ENABLED']) {
        if (ubRouting::checkGet('number')) {
            $number = ubRouting::get('number');
            $telepathy = new Telepathy(false, true, true, false);
            $telepathy->usePhones();
            $guessedLogin = $telepathy->getByPhoneFast($number, true, true);
            $result = array('result' => 0);
            if (!empty($guessedLogin)) {
                $allUserData = zb_UserGetAllDataCache();
                if (isset($allUserData[$guessedLogin])) {
                    $result['result'] = 1;
                    $result['userdata'] = $allUserData[$guessedLogin];
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
            die('ERROR: EMPTY NUMBER');
        }
    } else {
        die('ERROR: USERBYNUM DISABLED');
    }
}