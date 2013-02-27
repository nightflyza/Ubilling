<?php

define('BILLING_CONFIG','config/billing.ini');
$billing_config=parse_ini_file(BILLING_CONFIG);
include($billing_config['baseconf'].'/handlers.php');

/*
 * Low level billing operations wrapper
 * Class just calls ultra low handlers
 */

class ApiBilling {

    function createuser($login) {
        $login=trim($login);
        billing_createuser($login);
    }

    function addcash($login,$cash) {
        $login=trim($login);
        $cash=trim($cash);
        billing_addcash($login, $cash);
    }
    
    function setcash($login,$cash) {
         $login=trim($login);
         $cash=trim($cash);
         billing_setcash($login, $cash);
    }

    function setcredit($login,$credit) {
         $login=trim($login);
         $credit=trim($credit);
         billing_setcredit($login, $credit);
    }

    function setcreditexpire($login,$creditexpire) {
        $login=trim($login);
        $creditexpire=trim($creditexpire);
        billing_setcreditexpire($login, $creditexpire);
    }

    function resetuser($login) {
        $login=trim($login);
        billing_resetuser($login);
    }

    function setao($login,$state) {
        $login=trim($login);
        $state=trim($state);
        billing_setao($login, $state);
    }

    function setdstat($login,$state) {
        $login=trim($login);
        $state=trim($state);
        billing_setdstat($login, $state);
    }

    function setip($login,$ip) {
        $login=trim($login);
        $ip=trim($ip);
        billing_setip($login, $ip);
    }

    function setpassword($login,$password) {
        $login=trim($login);
        $password=trim($password);
        billing_setpassword($login, $password);
    }

    function settariff($login,$tariff) {
        $login=trim($login);
        $tariff=trim($tariff);
        billing_settariff($login, $tariff);
    }

    function settariffnm($login,$tariff) {
        $login=trim($login);
        $tariff=trim($tariff);
        billing_settariffnm($login, $tariff);
    }

    function setdown($login,$state) {
        $login=trim($login);
        $state=trim($state);
        billing_setdown($login, $state);
    }

    function setpassive($login,$state) {
        $login=trim($login);
        $state=trim($state);
        billing_setpassive($login, $state);
    }

    function deleteuser($login) {
        $login=trim($login);
        billing_deleteuser($login);
    }

    function createtariff($tariff) {
        $tariff=trim($tariff);
        billing_createtariff($tariff);
    }

    function deletetariff($tariff) {
        $tariff=trim($tariff);
        billing_deletetariff($tariff);
    }

    function edittariff($tariff, $options) {
        $tariff=trim($tariff);
        billing_edittariff($tariff, $options);
    }

    
    
}

?>
