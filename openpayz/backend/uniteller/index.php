<?php

/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты
 * при помощи Uniteller
 * 
 */

//Ловим методом GET виртуальный идентификатор пользователя

if (isset($_GET['customer_id'])) {
    $customer_id=trim($_GET['customer_id']);
} else {
    die('customer_id fail');
}


// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");


function getSignature($Shop_IDP, $Order_IDP, $Subtotal_P, $MeanType, $EMoneyType, $Lifetime, $Customer_IDP, $Card_IDP, $IData, $PT_Code, $password) {
    
$Signature = strtoupper(
   md5(
      md5($Shop_IDP) . "&" .
      md5($Order_IDP) . "&" .
      md5($Subtotal_P) . "&" .
      md5($MeanType) . "&" .
      md5($EMoneyType) . "&" .
      md5($Lifetime) . "&" .
      md5($Customer_IDP) . "&" .
      md5($Card_IDP) . "&" .
      md5($IData) . "&" .
      md5($PT_Code) . "&" .
      md5($password)
      )
   );
   
   return $Signature;
}

// подгружаем конфиг
$confUniteller=parse_ini_file("config/uniteller.ini");

$debugMode=$confUniteller['DEBUG_MODE'];
$template_file=$confUniteller['TEMPLATE'];
$merchant_name=$confUniteller['MERCHANT_NAME'];
$merchant_url=$confUniteller['MERCHANT_URL'];
$merchant_service=$confUniteller['MERCHANT_SERVICE'];
$merchant_logo=$confUniteller['MERCHANT_LOGO'];
$merchant_currency=$confUniteller['MERCHANT_CURRENCY'];
$raw_nominals=$confUniteller['NOMINALS'];

if (!$debugMode) {
    $uniteller_link=$confUniteller['UNITELLER_LINK'];
} else {
    $uniteller_link=$confUniteller['UNITELLER_TEST_LINK'];
}


$nominals=array();
if (!empty($raw_nominals)) {
    $nominals=  explode(',', $raw_nominals);
}


//выцепляем нужные параметры для Uniteller
$Shop_IDP = $confUniteller['SHOP_IDP'];
$Lifetime = $confUniteller['FORM_LIFETIME'];
$Customer_IDP = $customer_id;

//выцепляем сумму платежа
if (isset($_POST['PaySumm'])) {
    $Subtotal_P=vf(trim($_POST['PaySumm']));
} else {
    $Subtotal_P=1200;
}

  //либо кастомная сумма
    if (isset($_POST['PaySummCustom'])) {
        if ($_POST['PaySummCustom']!=0) {
            $Subtotal_P=vf(trim($_POST['PaySummCustom']));
        }
    }
    
$Order_IDP = 'UNT|'.$customer_id.'|'.$Subtotal_P.'|'.time();
$MeanType = $confUniteller['MEAN_TYPE']; 
$EMoneyType = $confUniteller['EMONEY_TYPE'];

//void _TYPE opts in testing mode
if ($debugMode) {
$MeanType = ''; 
$EMoneyType = '';
}

$URL_RETURN_OK = $confUniteller['URL_RETURN_OK'];
$URL_RETURN_NO = $confUniteller['URL_RETURN_NO'];
$password = $confUniteller['PASSWORD']; 



$Signature = getSignature($Shop_IDP, $Order_IDP, $Subtotal_P, $MeanType, $EMoneyType, $Lifetime, $Customer_IDP, $Card_IDP, $IData, $PT_Code, $password);

function paysumm_form($nominals,$merchant_currency) {
    //сборка выбиралки
    if (!empty($nominals)) {
        $moneySub='';
        foreach ($nominals as $each) {
            $moneySub.='<input name="PaySumm" id="cash'.$each.'" value="'.$each.'" checked="" type="radio"> <label for="cash'.$each.'">'.$each.' '.$merchant_currency.'</label><br>'."\n";
        }
        $moneySub.='<input type="text" name="PaySummCustom" value="0" size="5"> другая сумма <br>';
        
        
    } else {
        $moneySub='<input type="text" name="PaySumm" value="" size="5"> Введите сумму платежа';
    }
    
    $result='
        <form action="" method="POST">
        '.$moneySub.'
        <br>
        <input type="submit" value="Перейти к оплате">
        </form>
        ';
    return ($result);
}

function uniteller_form($Subtotal_P,$uniteller_link,$Shop_IDP,$Order_IDP,$Lifetime,$Customer_IDP,$Signature,$URL_RETURN_OK,$URL_RETURN_NO,$MeanType,$EMoneyType,$merchant_currency) {
    global $debugMode;
    //skipping _TYPE opts for testing
    if (!$debugMode) {
        $types='
            <input type="hidden" name="MeanType" value="'.$MeanType.'">
            <input type="hidden" name="EMoneyType" value="'.$EMoneyType.'">
            ';
    } else {
        $types='';
    }
    
    $form='
        <br>
        <h3>На сумму '.$Subtotal_P.' '.$merchant_currency.'</h3>
        <form action="'.$uniteller_link.'" method="POST">
        <input type="hidden" name="Shop_IDP" value="'.$Shop_IDP.'">
        <input type="hidden" name="Order_IDP" value="'.$Order_IDP.'">
        <input type="hidden" name="Subtotal_P" value="'.$Subtotal_P.'">
        <input type="hidden" name="Lifetime" value="'.$Lifetime.'">
        <input type="hidden" name="Customer_IDP" value="'.$Customer_IDP.'">
        <input type="hidden" name="Signature" value="'.$Signature.'">
        <input type="hidden" name="URL_RETURN_OK" value="'.$URL_RETURN_OK.'">
        <input type="hidden" name="URL_RETURN_NO" value="'.$URL_RETURN_NO.'">
        '.$types.'
        <br>
        <input type="submit" name="Submit" value="Оплатить">
        </form>
        ';
     return($form);
}

// строим форму выбора сумы платежа
if (!isset($_POST['PaySumm'])) {
    $payment_form= paysumm_form($nominals,$merchant_currency);
} else {
    // ну либо уже говорим, что пора бы оплатить все что мы навыбирали
    $payment_form= uniteller_form($Subtotal_P, $uniteller_link, $Shop_IDP, $Order_IDP, $Lifetime, $Customer_IDP, $Signature, $URL_RETURN_OK, $URL_RETURN_NO, $MeanType, $EMoneyType,$merchant_currency);
}

//показываем все что нужно в темплейт
include($template_file);


?>
