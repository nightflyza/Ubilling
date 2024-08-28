<?php

/**
 * Returns payment card input form
 * 
 * @return string
 */
function zbs_PaycardsShowForm() {
    $inputs = la_tag('br');
    $inputs .= __('Payment card number') . ' ';
    $inputs .= la_TextInput('paycard', '', '', false, 25);
    $inputs .= la_Submit(__('Use this card'));
    $inputs .= la_delimiter();
    $form = la_Form('', 'POST', $inputs, '');

    return ($form);
}

/**
 * Logs card brute-frorce attempt into database
 * 
 * @global string $user_login
 * @global string $user_ip
 * @param string $cardnumber
 */
function zbs_PaycardBruteLog($cardnumber) {
    global $user_login;
    global $user_ip;
    $cardnumber = vf($cardnumber);
    $ctime = curdatetime();

    $bruteDb = new NyanORM('cardbrute');
    $bruteDb->data('serial', $cardnumber);
    $bruteDb->data('date', $ctime);
    $bruteDb->data('login', $user_login);
    $bruteDb->data('ip', $user_ip);
    $bruteDb->create();
}

/**
 * Checks is card-number valid?
 * 
 * @param string $cardnumber
 * @return bool
 */
function zbs_PaycardCheck($cardnumber, $series = false) {
    $result = false;
    $cardnumber = vf($cardnumber);
    if (!empty($cardnumber)) {
        $cardbankDb = new NyanORM('cardbank');
        $cardbankDb->selectable('id');
        $cardbankDb->where('serial', '=', $cardnumber);
        $cardbankDb->where('active', '=', '1');
        $cardbankDb->where('used', '=', '0');
        $cardbankDb->where('usedlogin', '=', '');

        if ($series) {
            $series = vf($series);
            $cardbankDb->where('part', '=', $series);
        }

        $cardCheck = $cardbankDb->getAll();

        if (!empty($cardCheck)) {
            $result = true;
        } else {
            $result = false;
            zbs_PaycardBruteLog($cardnumber);
        }
    }
    return ($result);
}

/**
 * Returns array of existing payment card props
 * 
 * @param string $cardnumber
 * 
 * @return array
 */
function zbs_PaycardGetParams($cardnumber) {
    $cardnumber = vf($cardnumber);
    $result = array();
    $cardbankDb = new NyanORM('cardbank');
    $cardbankDb->where('serial', '=', $cardnumber);
    $rawData = $cardbankDb->getAll();
    if (!empty($rawData)) {
        $result = $rawData[0];
    }
    return ($result);
}

/**
 * Marks payment card as used in database and pushes its price to user account
 * 
 * @global string $user_ip
 * @global string $user_login
 * @global array $us_config
 * 
 * @param string $cardnumber
 * @param bool $pcAgentCall
 * 
 * @return void
 */
function zbs_PaycardUse($cardnumber, $pcAgentCall = false) {
    global $user_ip;
    global $user_login;
    global $us_config;

    $cardnumber = vf($cardnumber);
    $carddata = zbs_PaycardGetParams($cardnumber);
    $cardcash = $carddata['cash'];
    $ctime = curdatetime();

    $cardbankDb = new NyanORM('cardbank');
    $cardbankDb->data('usedlogin', $user_login);
    $cardbankDb->data('usedip', $user_ip);
    $cardbankDb->data('usedate', $ctime);
    $cardbankDb->data('used', '1');
    $cardbankDb->where('serial', '=', $cardnumber);
    $cardbankDb->save();

    zbs_PaymentLog($user_login, $cardcash, $us_config['PC_CASHTYPEID'], "CARD:" . $cardnumber);
    billing_addcash($user_login, $cardcash);

    if (!$pcAgentCall) {
        ubRouting::nav('index.php');
    }
}

/**
 * Marks payment card as used in database and stores it for future processing
 * 
 * @global string $user_ip
 * @global string $user_login
 * 
 * @param string $cardnumber
 * @param bool $pcAgentCall
 * 
 * @return void
 */
function zbs_PaycardQueue($cardnumber, $pcAgentCall = false) {
    global $user_ip;
    global $user_login;
    $cardnumber = vf($cardnumber);
    $carddata = zbs_PaycardGetParams($cardnumber);
    $cardcash = $carddata['cash'];
    $ctime = curdatetime();

    $cardbankDb = new NyanORM('cardbank');
    $cardbankDb->data('usedlogin', $user_login);
    $cardbankDb->data('usedip', $user_ip);
    $cardbankDb->data('usedate', $ctime);
    $cardbankDb->data('used', '0');
    $cardbankDb->where('serial', '=', $cardnumber);
    $cardbankDb->save();

    if (!$pcAgentCall) {
        ubRouting::nav('index.php');
    }
}

/**
 * Check card brute attempts by user`s IP
 * 
 * @param string $user_ip
 * @param string $pc_brute
 * 
 * @return bool
 */
function zbs_PayCardCheckBrute($user_ip, $pc_brute) {
    $result = false;

    $user_ip = vf($user_ip);
    $bruteDb = new NyanORM('cardbrute');
    $bruteDb->where('ip', '=', $user_ip);
    $attempts = $bruteDb->getFieldsCount();

    if ($attempts >= $pc_brute) {
        $result = true;
    }
    return ($result);
}
