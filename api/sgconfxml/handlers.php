<?php

/**
 * 
 * @param string $login
 * 
 * @return void
 */
function billing_createuser($login) {
    setVal($login, "add");
}

/**
 * Creates new stargazer user
 * 
 * @param string $login
 * 
 * @return void
 */
function billing_deleteuser($login) {
    setVal($login, "del");
}

/**
 * Sets user credit limit
 * 
 * @param string $login
 * @param float $credit
 * 
 * @return void
 */
function billing_setcredit($login, $credit) {
    setVal($login, "credit", $credit);
}

/**
 * Sets user credit limit expire date
 * 
 * @param string $login
 * @param string $creditexpire
 * 
 * @return void
 */
function billing_setcreditexpire($login, $creditexpire) {
    $creditexpire = strtotime($creditexpire);
    setVal($login, "CreditExpire", $creditexpire);
}

/**
 * Sets user IP address
 * 
 * @param string $login
 * @param string $ip
 * 
 * @return void
 */
function billing_setip($login, $ip) {
    setVal($login, "ip", $ip);
}

/**
 * Changes user password
 * 
 * @param string $login
 * @param string $password
 * 
 * @return void
 */
function billing_setpassword($login, $password) {
    setVal($login, "password", $password);
}

/**
 * Changes user tariff
 * 
 * @param string $login
 * @param string $tariff
 * 
 * @return void
 */
function billing_settariff($login, $tariff) {
    setVal($login, "tariff", $tariff, "now");
}

/**
 * Changes user tariff from next month
 * 
 * @param string $login
 * @param string $tariff
 * 
 * @return void
 */
function billing_settariffnm($login, $tariff) {
    setVal($login, "tariff", $tariff, "delayed");
}

/**
 * Pushes some money summ to user account
 * 
 * @param string $login
 * @param float $cash
 * 
 * @return void
 */
function billing_addcash($login, $cash) {
    setVal($login, "cash", $cash, "add");
}

/**
 * Changes user AlwaysOnline flag state
 * 
 * @param string $login
 * @param int $state
 * 
 * @return void
 */
function billing_setao($login, $state) {
    setVal($login, "aonline", $state);
}

/**
 * Sets user Cash parameter to some summ value
 * 
 * @param string $login
 * @param float $cash
 * 
 * @return void
 */
function billing_setcash($login, $cash) {
    setVal($login, "cash", $cash, "set");
}

/**
 * Changes user detailed stats flag state
 * 
 * @param string $login
 * @param int $state
 * 
 * @return void
 */
function billing_setdstat($login, $state) {
    setVal($login, "disabledetailstat", $state);
}

/**
 * Changes user Down flag state
 * 
 * @param string $login
 * @param int $state
 * 
 * @return void
 */
function billing_setdown($login, $state) {
    setVal($login, "down", $state);
}

/**
 * Performs sequential call of OnDisconnect and OnConnect init scripts.
 * 
 * @global object $billing_config
 * @param string $login
 * 
 * @return void
 */
function billing_resetuser($login) {
    global $billing_config;
    //rscriptd reset hotfix
    if ($billing_config['RESET_AO']) {
        billing_setao($login, 0);
        billing_setao($login, 1);
    } else {
        billing_setdown($login, 1);
        billing_setdown($login, 0);
    }
}

/**
 * Changes user Passive (aka Frozen) flag state
 * 
 * @param string $login
 * @param int $state
 * 
 * @return void
 */
function billing_setpassive($login, $state) {
    setVal($login, "passive", $state);
}

/**
 * Creates new Stargazer tariff
 * 
 * @param string $tariff
 * 
 * @return void
 */
function billing_createtariff($tariff) {
    setVal(@$login, "addtariff", $tariff);
}

/**
 * Deletes some Stargazer tariff
 * 
 * @param string $tariff
 * 
 * @return void
 */
function billing_deletetariff($tariff) {
    setVal(@$login, "deltariff", $tariff);
}

/**
 * Returns array of all available Stargazer tariffs with all tariff data
 * 
 * @return array
 */
function billing_getalltariffs() {
    return simple_queryall("SELECT * from `tariffs` ORDER BY `name`");
}

/**
 * Returns array of some existing tariff data
 * 
 * @param string $name
 * 
 * @return array
 */
function billing_gettariff($name) {
    return simple_query("SELECT * from `tariffs`  where `name` = '$name'");
}

/**
 * Changes some existing Stargazer tariff parameters
 * 
 * @param string $tariff
 * @param array $options
 * 
 * @return void
 */
function billing_edittariff($tariff, $options) {
    $dhour = $options ['dhour'];
    $dmin = $options ['dmin'];
    $nhour = $options ['nhour'];
    $nmin = $options ['nmin'];
    $PriceDay = $options ['PriceDay'];
    $PriceNight = $options ['PriceNight'];
    $Fee = $options ['Fee'];
    $Free = $options ['Free'];
    $PassiveCost = $options ['PassiveCost'];
    $TraffType = $options ['TraffType'];

    if (isset($options['Period'])) {
        $period = $options['Period'];
    } else {
        $period = '';
    }

    $dirs = getAllDirs();

    $string = "<SetTariff name=\"$tariff\">";
    $string .= "<Fee value=\"$Fee\"/>";
    $string .= "<Free value=\"$Free\"/>";
    $string .= "<PassiveCost value=\"$PassiveCost\"/>";
    $string .= "<TraffType value=\"$TraffType\"/>";
    if (!empty($period)) {
        $string .= "<period value=\"" . $period . "\"/>";
    }

    foreach ($dirs as $dir) {
        $key = $dir['rulenumber'];
        $string .= "<Time$key value=\"$dhour[$key]:$dmin[$key]-$nhour[$key]:$nmin[$key]\"/>";
    }

    $PriceDayA = '';
    $PriceDayB = '';
    $PriceNightA = '';
    $PriceNightB = '';
    $SinglePrice = '';
    $NoDiscount = '';
    $Threshold = '';

    for ($i = 0; $i <= 9; $i++) {
        $delimiter = ($i < 9) ? '/' : '';
        if (isset($options['NoDiscount'][$i])) {
            $NoDiscount .= '1' . $delimiter;
        } else {
            $NoDiscount .= '0' . $delimiter;
        }

        if (isset($options['SinglePrice'][$i])) {
            $SinglePrice .= '1' . $delimiter;
        } else {
            $SinglePrice .= '0' . $delimiter;
        }

        if (isset($options['Threshold'][$i])) {
            $Threshold .= $options['Threshold'][$i] . $delimiter;
        } else {
            $Threshold .= '0' . $delimiter;
        }

        /**
         * Shall fix this in future. May be. No one need it.
         * ..wait.. oh shi...
         */
        $PriceDayA .= '0' . $delimiter;
        $PriceDayB .= '0' . $delimiter;
        $PriceNightA .= '0' . $delimiter;
        $PriceNightB .= '0' . $delimiter;
    }


    $string .= "<PriceDayA value=\"$PriceDayA\"/>";
    $string .= "<PriceDayB value=\"$PriceDayB\"/>";
    $string .= "<PriceNightA value=\"$PriceNightA\"/>";
    $string .= "<PriceNightB value=\"$PriceNightB\"/>";
    $string .= "<SinglePrice value=\"$SinglePrice\"/>";
    $string .= "<NoDiscount value=\"$NoDiscount\"/>";
    $string .= "<Threshold value=\"$Threshold\"/>";
    $string .= "</SetTariff>";

    executor($string);
}

/**
 * Returns array of all available traffic classes
 * 
 * @return array
 */
function getAllDirs() {
    return simple_queryall("SELECT * from `directions` ORDER BY `rulenumber`");
}

/**
 * Performs sgconf_xml call with some XML formatted request
 * 
 * @global object $billing_config
 * @param string $attr
 * 
 * @param bool $debug
 * 
 * @return void
 */
function executor($attr, $debug = false) {
    global $billing_config;
    $cmd = $billing_config['SGCONFXML'] . ' -s ' . $billing_config['STG_HOST'] . ' -p ' . $billing_config['STG_PORT'] . ' -a ' . $billing_config['STG_LOGIN'] . ' -w ' . $billing_config['STG_PASSWD'] . ' -r \'' . $attr . '\'';
    if ($debug) {
        print(htmlspecialchars($cmd) . "\n<br>");
        print(shell_exec($cmd));
        die();
    } else {
        shell_exec($cmd);
    }
}

/**
 * Performs call of executor with some request data
 * 
 * @param string $login
 * @param string $type
 * @param string $value
 * @param string $subtype
 * 
 * @return void
 */
function setVal($login, $type, $value = false, $subtype = false) {
    $maintype = 'SetUser';
    $maintype = ($type == 'add') ? 'AddUser' : $maintype;
    $maintype = ($type == 'del') ? 'DelUser' : $maintype;
    $maintype = ($type == 'addtariff') ? 'AddTariff' : $maintype;
    $maintype = ($type == 'deltariff') ? 'DelTariff' : $maintype;

    $string = "<$maintype><login value=\"$login\" />";

    switch ($type) {
        case (preg_match('#cash|\btariff\b#i', $type) ? $type : !$type) :
            $val = $subtype;
            break;
        case 'del':
            $val = 'login';
            break;
        case (preg_match('#addtariff|deltariff#Uis', $type) ? $type : !$type):
            $val = 'name';
            break;
        default :
            $val = 'value';
            break;
    }

    if ($type != 'add') {
        $string .= "<$type $val=\"$value\" />";
    }

    $string .= "</$maintype>";
    $string = ($type == 'del') ? "<$maintype $val=\"$login\" />" : $string;
    $string = ($type == 'addtariff' || $type == 'deltariff') ? "<$type $val=\"$value\" />" : $string;
    executor($string, false);
}
