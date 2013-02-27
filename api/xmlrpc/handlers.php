<?php

function __call($method, $params = null, $debug = false) {

    global $billing_config;

    $request = xmlrpc_encode_request('stargazer.' . $method, $params, array('escaping' => 'markup', 'encoding' => 'utf-8'));
    $context = stream_context_create(
            array('http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: text/xml',
                    'content' => $request)));

    $file = file_get_contents('http://' . $billing_config['STG_HOST'] . ':' . $billing_config['XMLRPC_PORT'] . '/RPC2', false, $context);

    $response = xmlrpc_decode($file);

    if (is_array($response) && xmlrpc_is_fault($response)) {
        trigger_error("xmlrpc: {$response['faultString']} ({$response['faultCode']})");
    }


    if ($debug) {
        echo( print_r($response) );
    }

    return $response;
}

function executor($method, $array) {

    global $billing_config;

    $data = __call('login', array($billing_config['STG_LOGIN'], $billing_config['STG_PASSWD']));

    if (isset($data['cookie'])) {

        array_unshift($array, $data['cookie']);

        __call($method, $array);
    }
    __call('logout', array($data['cookie']));
}

function billing_createuser($login) {
    executor('add_user', array($login));
}

function billing_addcash($login, $cash) {

    executor('add_user_cash', array($login, (float) $cash, "add $cash cash"));
}


function billing_deleteuser($login) {
    executor('del_user', array($login));
}

function billing_setip($login, $ip) {
    executor('chg_user', array($login, array('ips' => $ip)));
}

function billing_setpassword($login, $password) {
    executor('chg_user', array($login, array('password' => $password)));
}


function billing_settariff($login, $tariff) {
    executor('chg_user_tariff', array($login, $tariff, false, 'change tariff'));
}


function billing_settariffnm($login, $tariff) {
    executor('chg_user_tariff', array($login, $tariff, true, 'change tariff'));
}

function billing_setao($login, $state) {
    $state = ($state === 1) ? TRUE : FALSE;
    executor('chg_user', array($login, array('aonline' => $state)));
}

function billing_setcash($login, $cash) {
    executor('set_user_cash', array($login, (float) $cash, "set $cash cash"));
}

function billing_setdstat($login, $state) {
    $state = ($state === 1) ? TRUE : FALSE;
    executor('chg_user', array($login, array('disableddetailstat' => $state)));
}

function billing_setdown($login, $state) {
    $state = ($state === 1) ? TRUE : FALSE;
    executor('chg_user', array($login, array('down' => $state)));
}

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

function billing_setpassive($login, $state) {
    $state = ($state === 1) ? TRUE : FALSE;
    executor('chg_user', array($login, array('passive' => $state)));
}

function billing_createtariff($tariff) {
    executor('add_tariff', array($tariff));
}

function billing_deletetariff($tariff) {
    executor('del_tariff', array($tariff));
}

function billing_getalltariffs() {
    return simple_queryall("SELECT * from `tariffs` ORDER BY `name`");
}

function billing_gettariff($name) {
    return simple_query("SELECT * from `tariffs`  where `name` = '$name'");
}

function billing_setcredit($login, $credit) {
    executor('chg_user', array($login, array('credit' => $credit)));
}

function billing_edittariff($tariff, $options) {

    $dhour = $options ['dhour'];
    $dmin = $options ['dmin'];
    $nhour = $options ['nhour'];
    $nmin = $options ['nmin'];
    $PriceDay = $options ['PriceDay'];
    $PriceNight = $options ['PriceNight'];
    $Fee = (double) $options ['Fee'];
    $Free = (double) $options ['Free'];
    $PassiveCost = (double) $options ['PassiveCost'];
    $Threshold = $options ['Threshold'];
    $SinglePrice = $options ['SinglePrice'];
    $NoDiscount = $options ['NoDiscount'];



    switch ($options ['TraffType']) {
        case 'up':

            $TraffType = 0;

            break;
        case 'down':

            $TraffType = 1;

            break;
        case 'up+down':

            $TraffType = 2;

            break;
        case 'max':

            $TraffType = 3;

            break;

        default:
            $TraffType = 2;
            break;
    }


    for ($i = 0; $i < 10; ++$i) {

        $arr = @explode('/', $PriceDay[$i]);
        if ($arr [0] && $arr [1]) {
            $PriceDayAa = $arr [0];
            $PriceDayBb = $arr [1];
        } else {
            $PriceDayAa = @$PriceDay[$i];
            $PriceDayBb = @$PriceDay[$i];
        }

        $PriceDayAc [$i] = $PriceDayAa;
        $PriceDayBc [$i] = $PriceDayBb;

        $arr1 = @explode('/', $PriceNight[$i]);

        if ($arr1 [0] && $arr1 [1]) {
            $PriceNightAa = $arr1 [0];
            $PriceNightBb = $arr1 [1];
        } else {
            $PriceNightAa = @$PriceNight[$i];
            $PriceNightBb = @$PriceNight[$i];
        }

        $PriceNightAc [$i] = $PriceNightAa;
        $PriceNightBc [$i] = $PriceNightBb;

        $array[$i] = @array('hday' => (int) $dhour[$i], 'mday' => (int) $dmin[$i], 'hnight' => (int) $nhour[$i], 'mnight' => (int) $nmin[$i], 'pricedaya' => (double) $PriceDayAc [$i], 'pricedayb' => (double) $PriceDayBc[$i], 'pricenighta' => (double) $PriceNightAc [$i], 'pricenightb' => (double) $PriceNightBc [$i], 'threshold' => (int) $Threshold[$i], 'singleprice' => (boolean) $SinglePrice[$i], 'nodiscount' => (boolean) $NoDiscount[$i]);
    }

    executor('chg_tariff', array($tariff,
        array('fee' => $Fee, 'freemb' => $Free, 'passivecost' => $PassiveCost, 'traffType' => $TraffType,
            'dirprices' => $array
            ))
    );
}

function getAllDirs() {
    return simple_queryall("SELECT * from `directions` ORDER BY `rulenumber`");
}
?>

