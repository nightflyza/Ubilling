<?php

$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();

//paymentcards  options
$pc_enabled=$us_config['PC_ENABLED'];
$pc_brute=$us_config['PC_BRUTE'];

/**
 * Returns payment card input form
 * 
 * @return string
 */
function zbs_PaycardsShowForm() {
    $inputs=  la_tag('br');
    $inputs.= __('Payment card number').' ';
    $inputs.= la_TextInput('paycard', '', '', false, 25);
    $inputs.= la_Submit(__('Use this card'));
    $inputs.= la_delimiter();
    $form=  la_Form('', 'POST', $inputs, '');
    
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
    $cardnumber=vf($cardnumber);
    $ctime=curdatetime();
    $query="INSERT INTO `cardbrute` (`id` , `serial` , `date` , `login` , `ip` )
        VALUES (
        NULL , '".$cardnumber."', '".$ctime."', '".$user_login."', '".$user_ip."');";
    nr_query($query);
}

/**
 * Checks is card-number valid?
 * 
 * @param string $cardnumber
 * @return bool
 */
function zbs_PaycardCheck($cardnumber) {
    $cardnumber=vf($cardnumber);
    $query="SELECT `id` from `cardbank` WHERE `serial`='".$cardnumber."' AND `active`='1' AND `used`='0'";
    $cardcheck=simple_query($query);
    if (!empty ($cardcheck)) {
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
    $cardnumber=vf($cardnumber);
    $carddata=array();
    $query="SELECT * from `cardbank` WHERE `serial`='".$cardnumber."'";
    $carddata=simple_query($query);
    return ($carddata);
}

/**
 * Marks payment card ad used in database and pushes its price to user account
 * 
 * @global string $user_ip
 * @global string $user_login
 * @param string $cardnumber
 */
function zbs_PaycardUse($cardnumber) {
    global $user_ip;
    global $user_login;
    $cardnumber=vf($cardnumber);
    $us_config=  zbs_LoadConfig();
    $carddata=zbs_PaycardGetParams($cardnumber);
    $cardcash=$carddata['cash'];
    $ctime=curdatetime();
    $carduse_q="UPDATE `cardbank` SET
        `usedlogin` = '".$user_login."',
        `usedip` = '".$user_ip."',
        `usedate`= '".$ctime."',
        `used`='1'
         WHERE `serial` ='".$cardnumber."';
        ";
    nr_query($carduse_q);
    zbs_PaymentLog($user_login, $cardcash, $us_config['PC_CASHTYPEID'], "CARD:".$cardnumber);
    billing_addcash($user_login, $cardcash);
    rcms_redirect("index.php");
}

/**
 * Check card brute attempts by user`s IP
 * 
 * @param string $user_ip
 * @param string $pc_brute
 * @return bool
 */
function zbs_PayCardCheckBrute($user_ip, $pc_brute) {
    $attempts=0;
    $query="SELECT COUNT(`id`) FROM `cardbrute` WHERE `ip`='".$user_ip."'";
    $brutecount=simple_query($query);
    if (!empty ($brutecount)){
        $attempts=$brutecount['COUNT(`id`)'];
    }
    if ($attempts>=$pc_brute) {
        return(true);
    } else {
        return (false);
    }
}


if ($pc_enabled) {
    //check is that user idiot? 
    if (!zbs_PayCardCheckBrute($user_ip,$pc_brute)) {
    //add cash routine with checks
     if (isset($_POST['paycard'])) {
         if (!empty ($_POST['paycard'])) {
             //use this card
             if (zbs_PaycardCheck($_POST['paycard'])) {
                 zbs_PaycardUse($_POST['paycard']);
             } else {
                 show_window(__('Error'), __('Payment card invalid'));
             }
         }
     }
    //show form
    show_window(__('Payment cards'), zbs_PaycardsShowForm());
    } else {
        //yeh, he is an idiot
      show_window(__('Error'),__('Sorry, but you have a limit number of attempts'));
    }
} else {
    show_window(__('Sorry'), __('Payment cards are disabled at this moment'));
}



?>
