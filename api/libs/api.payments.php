<?php

/**
 * Returns balance for some login
 * 
 * @param string $login Existing  user login
 * @return float
 */
function zb_CashGetUserBalance($login) {
    $login = vf($login);
    $query = "SELECT `Cash` from `users` WHERE `login`='" . $login . "'";
    $cash = simple_query($query);
    return ($cash['Cash']);
}

/**
 * Checks is input number valid money format or not?
 * 
 * @param $number an string to check
 * 
 * @return bool 
 */
function zb_checkMoney($number) {
    return preg_match("/^-?[0-9]+(?:\.[0-9]{1,9})?$/", $number);
}

/**
 * Add some cash to user login in stargazer, and creates payment record in registry
 * 
 * @global object $billing   Pre-initialized low-level stargazer handlers
 * @param string  $login     Existing users login
 * @param float   $cash      Amount of money to put/set on user login
 * @param string  $operation Operation  type: add, correct, set, mock, op
 * @param int     $cashtype  Existing cashtype ID for payment registry
 * @param string  $note      Payment notes
 * @param string  $customAdmin Custom administrator login
 * 
 * @return void
 */
function zb_CashAdd($login, $cash, $operation, $cashtype, $note, $customAdmin = '') {
    global $billing;
    $login = mysql_real_escape_string($login);
    $cash = mysql_real_escape_string($cash);
    $cash = preg_replace("#[^0-9\-\.]#Uis", '', $cash);
    $cash = trim($cash);
    $cashtype = vf($cashtype);
    $note = mysql_real_escape_string($note);
    $date = curdatetime();
    $balance = zb_CashGetUserBalance($login);
    $admin = whoami();
    if (!empty($customAdmin)) {
        $admin =  mysql_real_escape_string($customAdmin);
    }
    $noteprefix = '';

    /**
     * They wanna fuck you for free and explode ya
     * I gonna waiting no time let me show ya
     * You gonna be kidding Couse nothing is happening
     * You wanna be happy So follow me
     */
    switch ($operation) {
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
        case 'op':
            $targettable = 'payments';
            $billing->addcash($login, $cash);
            break;
    }
    //push dat payment to payments registry
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

/**
 * Signup payments processing and addcash function inside
 * 
 * @global object $ubillingConfig   Ubilling config helper object
 * @param string  $login     Existing users login
 * @param float   $cash      Amount of money to put/set on user login
 * @param string  $operation Operation  type: add, correct,set,mock
 * @param int     $cashtype  Existing cashtype ID for payment registry
 * @param string  $note      Payment notes
 * 
 * @return void
 */
function zb_CashAddWithSignup($login, $cash, $operation, $cashtype, $note) {
    switch ($operation) {
        case 'add':
            $signup_payment = zb_UserGetSignupPrice($login);
            $signup_paid = zb_UserGetSignupPricePaid($login);
            $signup_left = $signup_payment - $signup_paid;
            if ($signup_left > 0 && $cash > 0) {
                global $ubillingConfig;
                $alter = $ubillingConfig->getAlter();
                if ($cash > $signup_left) {
                    $signup_cash = $signup_left;
                    $balance_cash = $cash - $signup_cash;
                    zb_CashAdd($login, $signup_cash, $operation, $alter['SIGNUP_TYPEID'], __('Signup payment'));
                    zb_CashAdd($login, $balance_cash, $operation, $cashtype, $note);
                } else
                    zb_CashAdd($login, $cash, $operation, $alter['SIGNUP_TYPEID'], __('Signup payment'));
            } else
                zb_CashAdd($login, $cash, $operation, $cashtype, $note);
            break;
        default:
            zb_CashAdd($login, $cash, $operation, $cashtype, $note);
            break;
    }
}

/**
 * Returns all of available cashtypes array
 * 
 * @return array
 */
function zb_CashGetAlltypes() {
    $query = "SELECT * from `cashtype`";
    $alltypes = simple_queryall($query);
    return ($alltypes);
}

/**
 * Returns array of available cashtypes as id=>localized name
 * 
 * @return array
 */
function zb_CashGetTypesNamed() {
    $result = array();
    $allCashTypesRaw = zb_CashGetAlltypes();
    if (!empty($allCashTypesRaw)) {
        foreach ($allCashTypesRaw as $io => $each) {
            $result[$each['id']] = __($each['cashtype']);
        }
    }
    return ($result);
}

/**
 * Returns name of some existing cashtype by its DB id
 * 
 * @param int $typeid Existing cashtype ID
 * @return string
 */
function zb_CashGetTypeName($typeid) {
    $typeid = vf($typeid, 3);
    $query = "SELECT `cashtype` from `cashtype` WHERE `id`='" . $typeid . "'";
    $result = simple_query($query);
    $result = $result['cashtype'];
    return ($result);
}

/**
 * Returns all payments array by some login
 * 
 * @param string $login
 * @return array
 */
function zb_CashGetUserPayments($login) {
    $login = vf($login);
    /**
     * I`m on dead line
     * Keeping fucking funny smile.
     * Do you wanna quit the system
     * Or you wanna break it inside

     * Broken souls people insane
     * People insane people insane
     */
    $query = "SELECT * from `payments` WHERE `login`='" . $login . "' ORDER BY `id` DESC";
    $allpayments = simple_queryall($query);
    return ($allpayments);
}

/**
 * Return array of all available cashtypes as id=>name
 * 
 * @return array
 */
function zb_CashGetAllCashTypes() {
    $query = "SELECT * from `cashtype`";
    $result = array();
    $alltypes = simple_queryall($query);
    if (!empty($alltypes)) {
        foreach ($alltypes as $io => $eachtype) {
            $result[$eachtype['id']] = $eachtype['cashtype'];
        }
    }

    return ($result);
}

/**
 * Creates new cashtype in database
 * 
 * @param string $cashtype Cashtype name to create
 */
function zb_CashCreateCashType($cashtype) {
    $cashtype = mysql_real_escape_string($cashtype);
    $query = "INSERT INTO `cashtype` (`id` , `cashtype`) VALUES (NULL , '" . $cashtype . "'); ";
    nr_query($query);
    log_register("CREATE CashType `" . $cashtype . "`");
}

/**
 * Deletes cashtype from database
 * 
 * @param int $cashtypeid Existing cashtype ID
 */
function zb_CashDeleteCashtype($cashtypeid) {
    $cashtypeid = vf($cashtypeid);
    $query = "DELETE FROM `cashtype` WHERE `id`='" . $cashtypeid . "'";
    nr_query($query);
    log_register("DELETE CashType " . $cashtypeid);
}

/**
 * Returns year payments summ
 * 
 * @param int $year
 * @return float
 */
function zb_PaymentsGetYearSumm($year) {
    $year = vf($year);
    $query = "SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '" . $year . "-%' AND `summ` > 0";
    $result = simple_query($query);
    return ($result['SUM(`summ`)']);
}

/**
 * Returns year-month pair payments summ
 * 
 * @param int $year
 * @param int $month
 * @return float
 */
function zb_PaymentsGetMonthSumm($year, $month) {
    $year = vf($year);
    $query = "SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '" . $year . "-" . $month . "%' AND `summ` > 0";
    $result = simple_query($query);
    return ($result['SUM(`summ`)']);
}

/**
 * Returns payment count for year-month
 * 
 * @param int $year
 * @param int $month
 * @return int
 */
function zb_PaymentsGetMonthCount($year, $month) {
    $year = vf($year);
    $query = "SELECT COUNT(`id`) from `payments` WHERE `date` LIKE '" . $year . "-" . $month . "%' AND `summ` > 0";
    $result = simple_query($query);
    return ($result['COUNT(`id`)']);
}

/**
 * Returns payment ID for some user from op_customers view
 * 
 * @param string $login
 * @return string
 */
function zb_PaymentIDGet($login) {
    global $ubillingConfig;
    $result = '';
    if ($ubillingConfig->getAlterParam('OPENPAYZ_SUPPORT')) {
        $login = mysql_real_escape_string($login);
        $query = "SELECT `virtualid` from `op_customers` WHERE `realid`='" . $login . "'";
        $result = simple_query($query);
        if (!empty($result)) {
            $result = $result['virtualid'];
        }
    }
    return ($result);
}

// SIGNUP_PAYMENTS

/**
 * Returns signup payment summ for some login
 * 
 * @param string $login
 * @return float
 */
function zb_UserGetSignupPrice($login) {
    $login = vf($login);
    $query = "SELECT `price` FROM `signup_prices_users` WHERE `login` = '" . $login . "'";
    $result = simple_query($query);
    if (isset($result['price'])) {
        $price = $result['price'];
    } else {
        $price = 0;
        zb_UserCreateSignupPrice($login, $price);
    }
    return ($price);
}

/**
 * Returns already payed summ of signup payment
 * 
 * @param string $login
 * @return float
 */
function zb_UserGetSignupPricePaid($login) {
    $login = vf($login);
    $alter = parse_ini_file(CONFIG_PATH . 'alter.ini');
    $query = "SELECT SUM(`summ`) AS `paid` FROM `payments` WHERE `login` = '" . $login . "' AND `cashtypeid` = '" . $alter['SIGNUP_TYPEID'] . "'";
    $result = simple_query($query);
    return !empty($result['paid']) ? $result['paid'] : 0;
}

/**
 * Creates user signup price record in database
 * 
 * @param string $login
 * @param float $price
 */
function zb_UserCreateSignupPrice($login, $price) {
    $query = "INSERT INTO `signup_prices_users` (`login`, `price`) VALUES ('" . $login . "', '" . $price . "')";
    nr_query($query);
}

/**
 * Deletes user signup price record from database
 * 
 * @param string $login
 */
function zb_UserDeleteSignupPrice($login) {
    $query = "DELETE FROM `signup_prices_users` WHERE `login` = '" . $login . "'";
    nr_query($query);
}

/**
 * Changes user signup price in database
 * 
 * @param string $login
 * @param float $new_price
 */
function zb_UserChangeSignupPrice($login, $new_price) {
    $old_price = zb_UserGetSignupPrice($login);
    zb_UserDeleteSignupPrice($login);
    zb_UserCreateSignupPrice($login, $new_price);
    log_register('CHANGE SignupPrice (' . $login . ') FROM ' . $old_price . ' TO ' . $new_price);
}
