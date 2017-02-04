<?php

define('BILLING_CONFIG', 'config/billing.ini');
$billing_config = parse_ini_file(BILLING_CONFIG);
include($billing_config['baseconf'] . '/handlers.php');

/**
 * Low level billing operations wrapper
 * Class just calls ultra low handlers
 */
class ApiBilling {

    /**
     * Creates some stargazer user
     * 
     * @param string $login
     * 
     * @return void
     */
    function createuser($login) {
        $login = trim($login);
        billing_createuser($login);
    }

    /**
     * Adds cash to stargazer user account
     * 
     * @param string $login
     * @param float $cash
     * 
     * @return void
     */
    function addcash($login, $cash) {
        $login = trim($login);
        $cash = trim($cash);
        billing_addcash($login, $cash);
    }

    /**
     * Sets user account cash to some value
     * 
     * @param string $login
     * @param float $cash
     * 
     * @return void
     */
    function setcash($login, $cash) {
        $login = trim($login);
        $cash = trim($cash);
        billing_setcash($login, $cash);
    }

    /**
     * Sets credit for some user account
     * 
     * @param string $login
     * @param float $credit
     */
    function setcredit($login, $credit) {
        $login = trim($login);
        $credit = trim($credit);
        billing_setcredit($login, $credit);
    }

    /**
     * Sets credit expiration date
     * 
     * @param string $login
     * @param string $creditexpire
     * 
     * @return void
     */
    function setcreditexpire($login, $creditexpire) {
        $login = trim($login);
        $creditexpire = trim($creditexpire);
        billing_setcreditexpire($login, $creditexpire);
    }

    /**
     * Performs stargazer user reinit
     * 
     * @param string $login
     * 
     * @return void
     */
    function resetuser($login) {
        $login = trim($login);
        billing_resetuser($login);
    }

    /**
     * Sets AlwaysOnline flag to existing user
     * 
     * @param string $login
     * @param int $state
     * 
     * @return void
     */
    function setao($login, $state) {
        $login = trim($login);
        $state = trim($state);
        billing_setao($login, $state);
    }

    /**
     * Sets DisabledDetailStat flag to existing user
     * 
     * @param string $login
     * @param int $state
     * 
     * @return void
     */
    function setdstat($login, $state) {
        $login = trim($login);
        $state = trim($state);
        billing_setdstat($login, $state);
    }

    /**
     * Sets IP for some staragazer user login
     * 
     * @param string $login
     * @param string $ip
     * 
     * @return void
     */
    function setip($login, $ip) {
        $login = trim($login);
        $ip = trim($ip);
        billing_setip($login, $ip);
    }

    /**
     * Sets password for existing user
     * 
     * @param string $login
     * @param string $password
     * 
     * @return void
     */
    function setpassword($login, $password) {
        $login = trim($login);
        $password = trim($password);
        billing_setpassword($login, $password);
    }

    /**
     * Changes tariff right now for some user
     * 
     * @param string $login
     * @param string $tariff
     * 
     * @return void
     */
    function settariff($login, $tariff) {
        $login = trim($login);
        $tariff = trim($tariff);
        billing_settariff($login, $tariff);
    }

    /**
     * Sets TariffChange for next month
     * 
     * @param string $login
     * @param string $tariff
     * 
     * @return void
     */
    function settariffnm($login, $tariff) {
        $login = trim($login);
        $tariff = trim($tariff);
        billing_settariffnm($login, $tariff);
    }

    /**
     * Sets Down flag to existing user
     * 
     * @param string $login
     * @param int $state
     * 
     * @return void
     */
    function setdown($login, $state) {
        $login = trim($login);
        $state = trim($state);
        billing_setdown($login, $state);
    }

    /**
     * Sets Passive aka Frozen flag to existing user
     * 
     * @param string $login
     * @param int $state
     * 
     * @return void
     */
    function setpassive($login, $state) {
        $login = trim($login);
        $state = trim($state);
        billing_setpassive($login, $state);
    }

    /**
     * Deletes existing stargazer user
     * 
     * @param string $login
     * 
     * @return void
     */
    function deleteuser($login) {
        $login = trim($login);
        billing_deleteuser($login);
    }

    /**
     * Creates new stargazer tariff
     * 
     * @param string $tariff
     * 
     * @return void
     */
    function createtariff($tariff) {
        $tariff = trim($tariff);
        billing_createtariff($tariff);
    }

    /**
     * Deletes existing stargazer tariff
     * 
     * @param string $tariff
     * 
     * @return void
     */
    function deletetariff($tariff) {
        $tariff = trim($tariff);
        billing_deletetariff($tariff);
    }

    /**
     * Changes existing stargazer tariff options
     * 
     * @param string $tariff
     * @param array $options
     * 
     * @return void
     */
    function edittariff($tariff, $options) {
        $tariff = trim($tariff);
        billing_edittariff($tariff, $options);
    }

}

?>
