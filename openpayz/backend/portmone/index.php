<?php

/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты
 * 
 */

//Ловим методом GET виртуальный идентификатор пользователя


//кусок дебага
//$customer_id='2';

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// подгружаем конфиг
$conf_portmone=parse_ini_file("config/portmone.ini");

// выбираем нужные нам переменные о мерчанте
$merchant_name=$conf_portmone['MERCHANT_NAME'];
$merchant_url=$conf_portmone['MERCHANT_URL'];
$merchant_service=$conf_portmone['MERCHANT_SERVICE'];
$merchant_logo=$conf_portmone['MERCHANT_LOGO'];
$merchant_currency=$conf_portmone['MERCHANT_CURRENCY'];
$template_file=$conf_portmone['TEMPLATE'];
$log_forms=$conf_portmone['LOG_FORMS'];

// разбираемся с настройками самого Portmone 
$debug=$conf_portmone['DEBUG'];
$method=$conf_portmone['SEND_METHOD'];
$link=$conf_portmone['LINK'];
$payee_id=$conf_portmone['PAYEE_ID'];
$lang=$conf_portmone['LANG'];
$good_url=$conf_portmone['GOOD_URL'];
$bad_url=$conf_portmone['BAD_URL'];

function port_form($shop_order_number,$method,$link,$payee_id,$avail_prices,$lang,$good_url,$bad_url,$merchant_currency) {
if (isset($_REQUEST['customer_id'])) {
    $customer_id=(int)$_REQUEST['customer_id'];
} else {
    die('customer_id fail');
}

     $action_url=$link;
     $form='<p> <form action="'.$action_url.'" method="'.$method.'">';
     $form.='<input type="text" name="bill_amount" value=""> '.$merchant_currency.'<br>';
     $form.='<input type="hidden" name="payee_id" value="'.$payee_id.'"/>';
     $form.='<input type="hidden" name="shop_order_number" value="'.time().'"/>';
     $form.='<input type="hidden" name="description" value="'.$customer_id.'"/>';
     $form.='<input type="hidden" name="success_url" value="'.$good_url.'"/>';
     $form.='<input type="hidden" name="failure_url" value="'.$bad_url.'"/>';
     $form.='<input type="hidden" name="lang" value="'.$lang.'"/>';
     $form.='<br> <input type="submit"/>';
     $form.='</form> </p>';
return($form);
}

// строим форму выбора сумы платежа
$payment_form=port_form($customer_id, $method, $link, $payee_id, $avail_prices, $lang, $good_url, $bad_url, $merchant_currency);

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
