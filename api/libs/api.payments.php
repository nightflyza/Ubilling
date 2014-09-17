<?php

function zb_CashGetUserBalance($login) {
    $login=vf($login);
    $query="SELECT `Cash` from `users` WHERE `login`='".$login."'";
    $cash=simple_query($query);
    return($cash['Cash']); 
    
}

   /*
    * checks is input number valid money format or not?
    * 
    * @param $number an string to check
    * 
    * @return bool 
    */
   function zb_checkMoney($number) {
        return preg_match("/^-?[0-9]+(?:\.[0-9]{1,9})?$/", $number);
   }

function zb_CashAdd($login, $cash, $operation, $cashtype, $note) {
    global $billing;
    $login = mysql_real_escape_string($login);
    $cash  = mysql_real_escape_string($cash);
    $cash  = preg_replace("#[^0-9\-\.]#Uis", '', $cash);
    $cash  = trim($cash);
    $cashtype = vf($cashtype);
    $note  = mysql_real_escape_string($note);
    $date  = curdatetime();
    $balance = zb_CashGetUserBalance($login);
    $admin = whoami();
    $noteprefix = '';

    switch ( $operation ) {
        case 'add':
            $targettable = 'payments';
            $billing->addcash($login, $cash);
            log_register('BALANCEADD (' . $login . ') ON ' . $cash);
            break;
        case 'correct':
            $targettable = 'paymentscorr';
            $billing->addcash($login, $cash);
            log_register('BALANCECORRECT (' . $login . ') ON ' . $cash);
            break;
        case 'set':
            $targettable = 'payments';
            $billing->setcash($login, $cash);
            log_register("BALANCESET (" . $login . ') ON ' . $cash);
            $noteprefix = 'BALANCESET:';
            break;
        case 'mock':
            $targettable = 'payments';
            log_register("BALANCEMOCK (" . $login . ') ON ' . $cash);
            $noteprefix = 'MOCK:';
            break;
    }

    $query = "INSERT INTO `" . $targettable . "` (
                    `id` ,
                    `login` ,
                    `date` ,
                    `admin` ,
                    `balance` ,
                    `summ` ,
                    `cashtypeid` ,
                    `note`
                    )
                    VALUES (
                    NULL , '" . $login . "', '" . $date . "', '" . $admin . "', '" . $balance . "', '" . $cash . "', '" . $cashtype . "', '" . ($noteprefix . $note) . "'
                    );";
    nr_query($query);
}

function zb_CashAddWithSignup($login, $cash, $operation, $cashtype, $note) {
    switch ( $operation ) {
        case 'add':
            $signup_payment = zb_UserGetSignupPrice($login);
            $signup_paid    = zb_UserGetSignupPricePaid($login);
            $signup_left    = $signup_payment - $signup_paid;
            if ( $signup_left > 0 && $cash > 0 ) {
                global $ubillingConfig;
                $alter = $ubillingConfig->getAlter();
                if ( $cash > $signup_left ) {
                    $signup_cash  = $signup_left;
                    $balance_cash = $cash - $signup_cash;
                    zb_CashAdd($login, $signup_cash, $operation, $alter['SIGNUP_TYPEID'], __('Signup payment'));
                    zb_CashAdd($login, $balance_cash, $operation, $cashtype, $note); 
                } else zb_CashAdd($login, $cash, $operation, $alter['SIGNUP_TYPEID'], __('Signup payment'));
            } else  zb_CashAdd($login, $cash, $operation, $cashtype, $note);
            break;
        default:
            zb_CashAdd($login, $cash, $operation, $cashtype, $note);
            break;
    }
}

function zb_CashGetAlltypes() {
    $query="SELECT * from `cashtype`";
    $alltypes=simple_queryall($query);
    return($alltypes);
}

function zb_CashGetTypeName($typeid) {
    $typeid=vf($typeid,3);
    $query="SELECT `cashtype` from `cashtype` WHERE `id`='".$typeid."'";
    $result=simple_query($query);
    $result=$result['cashtype'];
    return($result);
}

function zb_CashGetUserPayments($login) {
    $login=vf($login);
    $query="SELECT * from `payments` WHERE `login`='".$login."' ORDER BY `id` DESC";
    $allpayments=simple_queryall($query);
    return($allpayments);
   }
   
// deprecated in 0.5.8   
//function zb_CashGetUserLastPayment($login) {
//    $login=vf($login);
//    $query="SELECT * from `payments` where `login`='".$login."' ORDER BY `date` DESC LIMIT 1";
//    $payment=simple_query($query);
//    if (!empty ($payment)) {
//    $result=__('Last payment').' '.$payment['summ'].' '.$payment['date'];
//    } else {
//    $result=__('Any payments yet');    
//    }
//    return($result);
//}
   
function zb_CashGetAllCashTypes() {
    $query="SELECT * from `cashtype`";
    $result=array();
    $alltypes=simple_queryall($query);
    if (!empty ($alltypes)) {
        foreach ($alltypes as $io=>$eachtype) {
            $result[$eachtype['id']]=$eachtype['cashtype'];
        }
    }
   
    return($result);
}

function zb_CashCreateCashType($cashtype) {
    $cashtype=mysql_real_escape_string($cashtype);
    $query="INSERT INTO `cashtype` (
                `id` ,
                `cashtype`
                )
                VALUES (
                NULL , '".$cashtype."'); ";
    nr_query($query);
    log_register("CREATE CashType ".$cashtype);
}

function zb_CashDeleteCashtype($cashtypeid) {
    $cashtypeid=vf($cashtypeid);
    $query="DELETE FROM `cashtype` WHERE `id`='".$cashtypeid."'";
    nr_query($query);
    log_register("DELETE CashType ".$cashtypeid);
}

function zb_PaymentsGetYearSumm($year) {
    $year=vf($year);
    $query="SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '".$year."-%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['SUM(`summ`)']);
}

function zb_PaymentsGetMonthSumm($year,$month) {
    $year=vf($year);
    $query="SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '".$year."-".$month."%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['SUM(`summ`)']);
}

function zb_PaymentsGetMonthCount($year,$month) {
    $year=vf($year);
    $query="SELECT COUNT(`id`) from `payments` WHERE `date` LIKE '".$year."-".$month."%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['COUNT(`id`)']);
}

//payment id handling
function zb_PaymentIDGet($login) {
    $login=mysql_real_escape_string($login);
    $query="SELECT `virtualid` from `op_customers` WHERE `realid`='".$login."'";
    $result=  simple_query($query);
    if (!empty($result)) {
        $result=$result['virtualid'];
    } else {
        $result='';
    }
    return ($result);
 }

// SIGNUP_PAYMENTS:
function zb_UserGetSignupPrice($login) {
    $login  = vf($login);
    $query  = "SELECT `price` FROM `signup_prices_users` WHERE `login` = '".$login."'";
    $result = simple_query($query);
    if ( isset($result['price']) ) {
        $price = $result['price'];
    } else {
        $price = 0;
        zb_UserCreateSignupPrice($login, $price);
    }
    return ($price);
}

function zb_UserGetSignupPricePaid($login) {
    $login  = vf($login);
    $alter  = parse_ini_file(CONFIG_PATH . 'alter.ini');
    $query  = "SELECT SUM(`summ`) AS `paid` FROM `payments` WHERE `login` = '".$login."' AND `cashtypeid` = '" . $alter['SIGNUP_TYPEID'] . "'";
    $result = simple_query($query);
    return !empty($result['paid']) ? $result['paid'] : 0;
}

function zb_UserCreateSignupPrice($login, $price) {
    $query = "INSERT INTO `signup_prices_users` (`login`, `price`) VALUES ('" . $login . "', '" . $price . "')";
    nr_query($query);
}

function zb_UserDeleteSignupPrice($login) {
    $query = "DELETE FROM `signup_prices_users` WHERE `login` = '" . $login . "'";
    nr_query($query);
}

function zb_UserChangeSignupPrice($login, $new_price) {
    $old_price = zb_UserGetSignupPrice($login);
    zb_UserDeleteSignupPrice($login);
    zb_UserCreateSignupPrice($login, $new_price);
    log_register('CHANGE SignupPrice (' . $login . ') FROM ' . $old_price . ' TO ' . $new_price);
}

?>
