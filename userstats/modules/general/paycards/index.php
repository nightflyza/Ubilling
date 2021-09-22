<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

//paymentcards  options
$pc_enabled = $us_config['PC_ENABLED'];
$pc_brute = $us_config['PC_BRUTE'];

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
    $query = "INSERT INTO `cardbrute` (`id` , `serial` , `date` , `login` , `ip` )
        VALUES (
        NULL , '" . $cardnumber . "', '" . $ctime . "', '" . $user_login . "', '" . $user_ip . "');";
    nr_query($query);
}

/**
 * Checks is card-number valid?
 * 
 * @param string $cardnumber
 * @return bool
 */
function zbs_PaycardCheck($cardnumber, $series = false) {
    $cardnumber = vf($cardnumber);
    $query = "SELECT `id` from `cardbank` WHERE `serial`='" . $cardnumber . "' AND `active`='1' AND `used`='0' AND `usedlogin` = ''";
    if ($series) {
        $series = vf($series);
        $query .= ' AND `part`="' . $series . '"';
    }

    $cardcheck = simple_query($query);
    if (!empty($cardcheck)) {
        return (true);
    } else {
        zbs_PaycardBruteLog($cardnumber);
        return(false);
    }
}

/**
 * Returns array of existing payment card props
 * 
 * @param string $cardnumber
 * @return array
 */
function zbs_PaycardGetParams($cardnumber) {
    $cardnumber = vf($cardnumber);
    $carddata = array();
    $query = "SELECT * from `cardbank` WHERE `serial`='" . $cardnumber . "'";
    $carddata = simple_query($query);
    return ($carddata);
}

/**
 * Marks payment card ad used in database and pushes its price to user account
 * 
 * @global string $user_ip
 * @global string $user_login
 * @global array $us_config
 * 
 * @param string $cardnumber
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
    $carduse_q = "UPDATE `cardbank` SET
        `usedlogin` = '" . $user_login . "',
        `usedip` = '" . $user_ip . "',
        `usedate`= '" . $ctime . "',
        `used`='1'
         WHERE `serial` ='" . $cardnumber . "';
        ";
    nr_query($carduse_q);
    zbs_PaymentLog($user_login, $cardcash, $us_config['PC_CASHTYPEID'], "CARD:" . $cardnumber);
    billing_addcash($user_login, $cardcash);

    if (!$pcAgentCall) {
        rcms_redirect("index.php");
    }
}

/**
 * Marks payment card ad used in database and stores it for future processing
 * 
 * @global string $user_ip
 * @global string $user_login
 * 
 * @param string $cardnumber
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
    $carduse_q = "UPDATE `cardbank` SET
        `usedlogin` = '" . $user_login . "',
        `usedip` = '" . $user_ip . "',
        `usedate`= '" . $ctime . "',
        `used`='0'
         WHERE `serial` ='" . $cardnumber . "';
        ";
    nr_query($carduse_q);

    if (!$pcAgentCall) {
        rcms_redirect("index.php");
    }
}

/**
 * Check card brute attempts by user`s IP
 * 
 * @param string $user_ip
 * @param string $pc_brute
 * @return bool
 */
function zbs_PayCardCheckBrute($user_ip, $pc_brute) {
    $attempts = 0;
    $query = "SELECT COUNT(`id`) FROM `cardbrute` WHERE `ip`='" . $user_ip . "'";
    $brutecount = simple_query($query);
    if (!empty($brutecount)) {
        $attempts = $brutecount['COUNT(`id`)'];
    }
    if ($attempts >= $pc_brute) {
        return(true);
    } else {
        return (false);
    }
}

if (ubRouting::checkGet('agentpaycards')) {
    $pcAgentCall = true;
    $pcAgentResult = [];
    $pcAgentOutputFormat = 'xml';

    if (ubRouting::checkGet('json')) {
        $pcAgentOutputFormat = 'json';
    }
} else {
    $pcAgentCall = false;
}

// Check if Paycards module is enabled
if ($pc_enabled) {
    //check is that user idiot?
    if (!zbs_PayCardCheckBrute($user_ip, $pc_brute)) {
        //add cash routine with checks
        if (isset($_POST['paycard'])) {
            if (!empty($_POST['paycard'])) {
                $series = false;
                if ($us_config['PC_SERIES_AND_SN']) {
                    $serialNumber = substr($_POST['paycard'], $us_config['PC_SERIES_LENGTH']);
                    $series = str_replace($serialNumber, '', $_POST['paycard']);
                    $_POST['paycard'] = $serialNumber;
                }

                //use this card
                if (zbs_PaycardCheck($_POST['paycard'], $series)) {
                    if (!@$us_config['PC_QUEUED']) {
                        zbs_PaycardUse($_POST['paycard'], $pcAgentCall);
                    } else {
                        //or mark it for queue processing
                        zbs_PaycardQueue($_POST['paycard'], $pcAgentCall);
                    }

                    if ($pcAgentCall) {
                        $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "true" : true);
                        $pcAgentResult[] = array("message" => "Card is successfully used");
                    }
                } else {
                    if ($pcAgentCall) {
                        $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "false" : false);
                        $pcAgentResult[] = array("message" => "Invalid card");
                    } else {
                        show_window(__('Error'), __('Payment card invalid'));
                    }
                }
            }
        } else if ($pcAgentCall) {
            $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "false" : false);
            $pcAgentResult[] = array("message" => "No card number provided");
        } else {
            //show form
            show_window(__('Payment cards'), zbs_PaycardsShowForm());
        }
    } else {
        //yeh, he is an idiot
        if ($pcAgentCall) {
            $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "false" : false);
            $pcAgentResult[] = array("message" => "Too many attempts");
        } else {
            show_window(__('Error'), __('Sorry, but you have a limit number of attempts'));
        }
    }
} else {
    if ($pcAgentCall) {
        $pcAgentResult[] = array("result" => $pcAgentOutputFormat === 'xml' ? "false" : false);
        $pcAgentResult[] = array("message" => "Paycards module is disabled");
    } else {
        show_window(__('Sorry'), __('Payment cards are disabled at this moment'));
    }
}

if ($pcAgentCall) {
    zbs_XMLAgentRender($pcAgentResult, 'data', '', $pcAgentOutputFormat, false);
}
