<?php

function executor($attr, $debug = false) {

    global $billing_config;

    $cmd = $billing_config['SGCONFXML'] . ' -s ' . $billing_config['STG_HOST'] . ' -p ' . $billing_config['STG_PORT'] . ' -a ' . $billing_config['STG_LOGIN'] . ' -w ' . $billing_config['STG_PASSWD'] . ' -r \'' . $attr . '\'';

    if ($debug) {

        echo( htmlspecialchars($cmd) . "\n<br>" );

        echo( shell_exec($cmd) );
        die();
    } else {

        shell_exec($cmd);
    }
}

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

    if ($type != 'add')
        $string .= "<$type $val=\"$value\" />";

    $string .= "</$maintype>";

    $string = ($type == 'del') ? "<$maintype $val=\"$login\" />" : $string;

    $string = ($type == 'addtariff' || $type == 'deltariff') ? "<$type $val=\"$value\" />" : $string;
    //file_write_contents("debug.log",$string);
    executor($string,false);
}

function billing_createuser($login) {
    setVal($login, "add");
}

function billing_deleteuser($login) {
    setVal($login, "del");
}

function billing_setcredit($login, $credit) {
    setVal($login, "credit", $credit);
}

function billing_setcreditexpire($login,$creditexpire) {
    $creditexpire=strtotime($creditexpire);
    setVal($login, "CreditExpire", $creditexpire);
}

function billing_setip($login, $ip) {
    setVal($login, "ip", $ip);
}

function billing_setpassword($login, $password) {
    setVal($login, "password", $password);
}

function billing_settariff($login, $tariff) {
    setVal($login, "tariff", $tariff, "now");
}

function billing_settariffnm($login, $tariff) {
    setVal($login, "tariff", $tariff, "delayed");
}

function billing_addcash($login, $cash) {
    setVal($login, "cash", $cash, "add");
}

function billing_setao($login, $state) {
    setVal($login, "aonline", $state);
}

function billing_setcash($login, $cash) {
    setVal($login, "cash", $cash, "set");
}

function billing_setdstat($login, $state) {
    setVal($login, "disabledetailstat", $state);
}

function billing_setdown($login, $state) {
    setVal($login, "down", $state);
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
    setVal($login, "passive", $state);
}

function billing_createtariff($tariff) {
    setVal($login, "addtariff", $tariff);
}

function billing_deletetariff($tariff) {
    setVal($login, "deltariff", $tariff);
}

function billing_getalltariffs() {
    return simple_queryall("SELECT * from `tariffs` ORDER BY `name`");
}

function billing_gettariff($name) {
    return simple_query("SELECT * from `tariffs`  where `name` = '$name'");
}

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
        $period=$options['Period'];
    } else {
        $period='';
    }

    $dirs = getAllDirs();

    $string = "<SetTariff name=\"$tariff\">";
    $string .= "<Fee value=\"$Fee\"/>";
    $string .= "<Free value=\"$Free\"/>";
    $string .= "<PassiveCost value=\"$PassiveCost\"/>";
    $string .= "<TraffType value=\"$TraffType\"/>";
    if (!empty($period)) {
    $string.=  "<period value=\"".$period."\"/>";    
    }

    foreach ($dirs as $dir) {

        $key = $dir['rulenumber'];
        $string .= "<Time$key value=\"$dhour[$key]:$dmin[$key]-$nhour[$key]:$nmin[$key]\"/>";
    }

    foreach ($options ['PriceDay'] as $key => $value) {

        $arr = explode('/', $value);

        if ($arr [0] && $arr [1]) {
            $PriceDayAa = $arr [0];
            $PriceDayBb = $arr [1];
        } else {
            $PriceDayAa = $value;
            $PriceDayBb = $value;
        }

        $PriceDayAc [$key] = $PriceDayAa;
        $PriceDayBc [$key] = $PriceDayBb;
    }
    if (isset($options ['PriceNight'])) {
        foreach ($options ['PriceNight'] as $key => $value) {

            $arr = explode('/', $value);

            if ($arr [0] && $arr [1]) {
                $PriceNightAa = $arr [0];
                $PriceNightBb = $arr [1];
            } else {
                $PriceNightAa = $value;
                $PriceNightBb = $value;
            }

            $PriceNightAc [$key] = $PriceNightAa;
            $PriceNightBc [$key] = $PriceNightBb;
        }
    }
    for ($i = 0; $i <= 9; $i++) {

        if ($PriceDayAc [$i]) {

            if (isset($PriceDayA))
                $sep = '/';
            $PriceDayA .= $sep . $PriceDayAc [$i];
        } else {
            if (isset($PriceDayA))
                $sep = '/';
            $PriceDayA .= $sep . '0';
        }

        if ($PriceDayBc [$i]) {

            if (isset($PriceDayB))
                $sep1 = '/';
            $PriceDayB .= $sep1 . $PriceDayBc [$i];
        } else {
            if (isset($PriceDayB))
                $sep1 = '/';
            $PriceDayB .= $sep1 . '0';
        }

        if ($PriceNightAc [$i]) {

            if (isset($PriceNightA))
                $sep2 = '/';
            $PriceNightA .= $sep2 . $PriceNightAc [$i];
        } else {
            if (isset($PriceNightA))
                $sep2 = '/';
            $PriceNightA .= $sep . '0';
        }

        if ($PriceNightBc [$i]) {

            if (isset($PriceNightB))
                $sep3 = '/';
            $PriceNightB .= $sep3 . $PriceNightBc [$i];
        } else {
            if (isset($PriceNightB))
                $sep3 = '/';
            $PriceNightB .= $sep3 . '0';
        }

        ///////////////////////////////////////


        if ($options ['Threshold'] [$i]) {

            if (isset($Threshold))
                $sep4 = '/';
            $Threshold .= $sep4 . $options ['Threshold'] [$i];
        } else {

            if (isset($Threshold))
                $sep4 = '/';
            $Threshold .= $sep4 . '0';
        }

        ////////////////////////////////////////


        if ($options ['SinglePrice'] [$i]) {

            if (isset($SinglePrice))
                $sep5 = '/';

            $SinglePrice .= $sep5 . $options ['SinglePrice'] [$i];
        } else {

            if (isset($SinglePrice))
                $sep5 = '/';

            $SinglePrice .= $sep5 . "0";
        }

        ////////////////////////////////////////


        if ($options ['NoDiscount'] [$i] != false) {

            if (isset($NoDiscount))
                $sep6 = '/';
            $NoDiscount .= $sep6 . $options ['NoDiscount'] [$i];
        } else {

            if (isset($NoDiscount))
                $sep6 = '/';
            $NoDiscount .= $sep6 . '0';
        }
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

function getAllDirs() {
    return simple_queryall("SELECT * from `directions` ORDER BY `rulenumber`");
}

?>
