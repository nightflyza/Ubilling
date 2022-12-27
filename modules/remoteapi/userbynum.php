<?php

if (ubRouting::get('action') == 'userbynum') {
    if (@$alterconf['USERBYNUM_ENABLED']) {
        if (ubRouting::checkGet('number')) {
            $number = ubRouting::get('number');
            $telepathy = new Telepathy(false, true, true, true);
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

            print(json_encode($result));
            die();
        } else {
            die('ERROR: EMPTY NUMBER');
        }
    } else {
        die('ERROR: USERBYNUM DISABLED');
    }
}