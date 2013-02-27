<?php
/* 
 * base stargazer handlers as add_cash routines using sgconf via shellscripts
 */



/* execute sgconf command
 * @param string $command <p>
 * parameters for sgconf
 */
function executor($command,$debug=false) {
$globconf=parse_ini_file('config/billing.ini');
$SGCONF=$globconf['SGCONF'];
$STG_HOST=$globconf['STG_HOST'];
$STG_PORT=$globconf['STG_PORT'];
$STG_LOGIN=$globconf['STG_LOGIN'];
$STG_PASSWD=$globconf['STG_PASSWD'];
$configurator=$SGCONF.' set -s '.$STG_HOST.' -p '.$STG_PORT.' -a'.$STG_LOGIN.' -w'.$STG_PASSWD.' '.$command;
if ($debug) {
     print($configurator."\n");
     print(shell_exec($configurator));
    } else {
     shell_exec($configurator);
    }
}


/*
 * Create stargazer user
 * @param string $login <p>
 * login to register
 */
function billing_createuser($login) {
	executor('-u'.$login.' -n');
}


/*
 * Delete stargazer user
 * @param string $login <p>
 * login to delete
 */
function billing_deleteuser($login) {
	executor('-u'.$login.' -l');
}

/*
 * Add cash to stargazer user
 * @param string $login <p>
 * stargazer user login
 * @param string $cash <p>
 * cash float value
 */

function billing_addcash($login,$cash) {
        executor('-u'.$login.' -c '.$cash);
}


/*
 * Set user credit
 * @param string $login <p>
 * stargazer user login
 * @param string $credit <p>
 * cash float value
 */

function billing_setcredit($login,$credit) {
    executor('-u'.$login.' -r '.$credit);
}

/*
 * Set user credit expiration date
 * @param string $login <p>
 * stargazer user login
 * @param string $creditexpire <p>
 * creditexpire date value
 */

function billing_setcreditexpire($login,$creditexpire) {
    executor('-u'.$login.' -E '.$creditexpire);
}

/*
 * Set user down, then up
 * @param string $login <p>
 * stargazer user login
 */

function billing_resetuser($login) {
   executor('-u'.$login.' -d 1');
   //sleep(3);
   executor('-u'.$login.' -d 0');
}

/*
 * Set user AlwaysOnline
 * @param string $login <p>
 * stargazer user login
 * @param string $state <p>
 * always online - 1 or 0
 */
function billing_setao($login,$state) {
   executor('-u'.$login.' --always-online '.$state);
}

/*
 * Set cash to stargazer user
 * @param string $login <p>
 * stargazer user login
 * @param string $cash <p>
 * cash float value
 */
function billing_setcash($login,$cash) {
    executor('-u'.$login.' -v '.$cash);
}

/*
 * Set user DisableDstats
 * @param string $login <p>
 * stargazer user login
 * @param string $state <p>
 * disabledstats - 1 or 0
 */
function billing_setdstat($login,$state) {
   executor('-u'.$login.' --disable-stat '.$state);
}

/*
 * Set IP to stargazer user
 * @param string $login <p>
 * stargazer user login
 * @param string $ip <p>
 * ip value
 */
function billing_setip($login,$ip) {
    executor('-u'.$login.' -I '.$ip);
}

/*
 * Set password to stargazer user
 * @param string $login <p>
 * stargazer user login
 * @param string $password <p>
 * password string
 */
function billing_setpassword($login,$password) {
    executor('-u'.$login.' -o '.$password);;
}

/*
 * Set tariff to stargazer user right now
 * @param string $login <p>
 * stargazer user login
 * @param string $tariff <p>
 * tariff name string
 */
function billing_settariff($login,$tariff) {
    executor('-u'.$login.' -t '.$tariff);
}

/*
 * Set tariff to stargazer user next month (native)
 * @param string $login <p>
 * stargazer user login
 * @param string $tariff <p>
 * tariff name string
 */
function billing_settariffnm($login,$tariff) {
   executor('-u'.$login.' -t '.$tariff.':delayed');
}

/*
 * Set user Down
 * @param string $login <p>
 * stargazer user login
 * @param string $state <p>
 * Down state - 1 or 0
 */
function billing_setdown($login,$state) {
    executor('-u'.$login.' -d '.$state);
}


/*
 * Set user Passive
 * @param string $login <p>
 * stargazer user login
 * @param string $state <p>
 * Passive state - 1 or 0
 */
function billing_setpassive($login,$state) {
    executor('-u'.$login.' -i '.$state);
}

/*
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
