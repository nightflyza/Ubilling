<?php

/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты
 * 
 */

//Ловим методом GET виртуальный идентификатор пользователя

if (isset($_GET['customer_id'])) {
    $customer_id=$_GET['customer_id'];
} else {
    die('customer_id fail');
}

//кусок дебага
//$customer_id='vtest';


// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// подгружаем конфиг
$conf_ipay=parse_ini_file("config/ipay.ini");

// выбираем нужные нам переменные о мерчанте
$merchant_name=$conf_ipay['MERCHANT_NAME'];
$merchant_url=$conf_ipay['MERCHANT_URL'];
$merchant_service=$conf_ipay['MERCHANT_SERVICE'];
$merchant_logo=$conf_ipay['MERCHANT_LOGO'];
$merchant_currency=$conf_ipay['MERCHANT_CURRENCY'];
$template_file=$conf_ipay['TEMPLATE'];
$log_forms=$conf_ipay['LOG_FORMS'];

// разбираемся с настройками самого IPAY
$debug=$conf_ipay['DEBUG'];
$method=$conf_ipay['SEND_METHOD'];
$ipay_sandbox=$conf_ipay['IPAY_SANDBOX'];
$ipay_link=$conf_ipay['IPAY_LINK'];
$merchant_id=$conf_ipay['MERCHANT_ID'];
$avail_prices=explode(',',$conf_ipay['AVAIL_PRICES']);
$lang=$conf_ipay['LANG'];
$good_url=$conf_ipay['GOOD_URL'];
$bad_url=$conf_ipay['BAD_URL'];


function ipay_form($customer_id,$debug,$method,$ipay_sandbox,$ipay_link,$merchant_id,$avail_prices,$lang,$good_url,$bad_url,$merchant_currency) {
// а не в песочнице ли мы?
    if ($debug) {
     $action_url=$ipay_sandbox;
        } else {
     $action_url=$ipay_link;
     }   
     $form='<p> <form action="'.$action_url.'" method="'.$method.'">';
     if (!empty ($avail_prices)) {
         $i=0;
         foreach ($avail_prices as $eachprice) {
             //выставляем первую цену отмеченной
             if ($i==0) {
                 $selected='CHECKED';
              } else {
                  $selected='';
              }
              
             //не забываем что суммы в копейках
             $form.='<input type="radio" name="amount" value="'.$eachprice.'" '.$selected.'> '.($eachprice/100).' '.$merchant_currency.'<br>';
             $i++;
         }
         
     } else {
         $form.='<input type="text" name="amount"> '.$merchant_currency;
     }
     //передаем прочие нужные параметры
     $form.='<input type="hidden" name="desc" value="'.$customer_id.'">';
     $form.='<input type="hidden" name="good" value="'.$good_url.'">';
     $form.='<input type="hidden" name="bad" value="'.$bad_url.'">';
     $form.='<input type="hidden" name="lang" value="'.$lang.'">';
     $form.='<input type="hidden" name="id" value="'.$merchant_id.'">';
     $form.='<br> <input type="submit">';
     $form.='</form> </p>';
     
     return($form);
}

// строим форму выбора сумы платежа
$payment_form=ipay_form($customer_id, $debug, $method, $ipay_sandbox, $ipay_link, $merchant_id, $avail_prices, $lang, $good_url, $bad_url,$merchant_currency);

//если надо логаем формочку со всеми потрохами
if ($log_forms) {
    $datetime=curdatetime();
    $log_file="config/forms.log";
    $remote_ip=$_SERVER['REMOTE_ADDR'];
    $log_data='======================='.$datetime."\n";
    $log_data.=$payment_form;
    $log_data.="\n".'======================='."\n";
    file_put_contents($log_file, $log_data,FILE_APPEND);
}

//показываем все что нужно в темплейт
include($template_file);


?>
