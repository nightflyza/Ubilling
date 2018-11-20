<?php

/**
 * base stargazer handlers as add_cash routines using sgconf via shellscripts
 */
/* *
 * execute sgconf command
 * @param string $command parameters for sgconf
 * 
 * @return void
 */

function executor($command, $debug = false) {
    $globconf = parse_ini_file('config/billing.ini');
    $SGCONF = $globconf['SGCONF'];
    $STG_HOST = $globconf['STG_HOST'];
    $STG_PORT = $globconf['STG_PORT'];
    $STG_LOGIN = $globconf['STG_LOGIN'];
    $STG_PASSWD = $globconf['STG_PASSWD'];
    $configurator = $SGCONF . ' set -s ' . $STG_HOST . ' -p ' . $STG_PORT . ' -a' . $STG_LOGIN . ' -w' . $STG_PASSWD . ' ' . $command;
    if ($debug) {
        print($configurator . "\n");
        print(shell_exec($configurator));
    } else {
        shell_exec($configurator);
    }
}

/**
 * Create stargazer user
 * @param string $login login to register
 * 
 * @return void
 */
function billing_createuser($login) {
    executor('-u' . $login . ' -n');
}

/**
 * Delete stargazer user
 * @param string $login login to delete
 * 
 * @return void
 */
function billing_deleteuser($login) {
    executor('-u' . $login . ' -l');
}

/**
 * Add cash to stargazer user
 * 
 * @param string $login stargazer user login
 * @param string $cash cash float value
 * 
 * @return void
 */
function billing_addcash($login, $cash) {
    executor('-u' . $login . ' -c ' . $cash);
}

/**
 * Set user credit
 * @param string $login stargazer user login
 * @param string $credit cash float value
 * 
 * @return void
 */
function billing_setcredit($login, $credit) {
    executor('-u' . $login . ' -r ' . $credit);
}

/**
 * Set user credit expiration date
 * @param string $login stargazer user login
 * @param string $creditexpire creditexpire date value
 * 
 * @return void
 */
function billing_setcreditexpire($login, $creditexpire) {
    executor('-u' . $login . ' -E ' . $creditexpire);
}

/**
 * Set user down, then up
 * @param string $login stargazer user login
 * 
 * @return void
 */
function billing_resetuser($login) {
    executor('-u' . $login . ' -d 1');
    //sleep(3);
    executor('-u' . $login . ' -d 0');
}

/**
 * Set user AlwaysOnline
 * @param string $login stargazer user login
 * @param string $state always online - 1 or 0
 * 
 * @return void
 */
function billing_setao($login, $state) {
    executor('-u' . $login . ' --always-online ' . $state);
}

/**
 * Set cash to stargazer user
 * @param string $login stargazer user login
 * @param string $cash cash float value
 * 
 * @return void
 */
function billing_setcash($login, $cash) {
    executor('-u' . $login . ' -v ' . $cash);
}

/**
 * Set user DisableDstats
 * @param string $login stargazer user login
 * @param string $state disabledstats - 1 or 0
 * 
 * @return void
 */
function billing_setdstat($login, $state) {
    executor('-u' . $login . ' --disable-stat ' . $state);
}

/**
 * Set IP to stargazer user
 * @param string $login stargazer user login
 * @param string $ip ip value
 * 
 * @return void
 */
function billing_setip($login, $ip) {
    executor('-u' . $login . ' -I ' . $ip);
}

/**
 * Set password to stargazer user
 * @param string $login stargazer user login
 * @param string $password  password string
 * 
 * @return void
 */
function billing_setpassword($login, $password) {
    executor('-u' . $login . ' -o ' . $password);
    ;
}

/**
 * Set tariff to stargazer user right now 
 * @param string $login stargazer user login
 * @param string $tariff tariff name string
 * 
 * @return void
 */
function billing_settariff($login, $tariff) {
    executor('-u' . $login . ' -t ' . $tariff);
}

/**
 * Set tariff to stargazer user next month (native)
 * @param string $login stargazer user login
 * @param string $tariff tariff name string
 * 
 * @return void
 */
function billing_settariffnm($login, $tariff) {
    executor('-u' . $login . ' -t ' . $tariff . ':delayed');
}

/**
 * Set user Down
 * @param string $login stargazer user login
 * @param string $state Down state - 1 or 0
 * 
 * @return void
 */
function billing_setdown($login, $state) {
    executor('-u' . $login . ' -d ' . $state);
}

/**
 * Set user Passive
 * @param string $login stargazer user login
 * @param string $state Passive state - 1 or 0
 * 
 * @return void
 */
function billing_setpassive($login, $state) {
    executor('-u' . $login . ' -i ' . $state);
}

/**
 *  Billing hadlers which is not supported in sgconf layer
 */
function billing_edittariff($tariff, $options) {
    die('Sorry, this feature is not available in sgconf');
}

function billing_deletetariff($tariff) {
    die('Sorry, this feature is not available in sgconf');
}

function billing_createtariff($tariff) {
    die('Sorry, this feature is not available in sgconf');
}

function getAllDirs() {
    return simple_queryall("SELECT * from `directions` ORDER BY `rulenumber`");
}

function billing_getalltariffs() {
    return simple_queryall("SELECT * from `tariffs` ORDER BY `name`");
}

function billing_gettariff($name) {
    return simple_query("SELECT * from `tariffs`  where `name` = '$name'");
}

?>
