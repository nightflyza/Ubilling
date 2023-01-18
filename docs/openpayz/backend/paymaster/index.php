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

$tariff=$_GET['tariff'];

//кусок дебага
//$customer_id='vtest';


// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// подгружаем конфиг
$conf_paymaster=parse_ini_file("config/paymaster.ini");

// выбираем нужные нам переменные о мерчанте
$merchant_name=$conf_paymaster['MERCHANT_NAME'];
$merchant_url=$conf_paymaster['MERCHANT_URL'];
$merchant_service=$conf_paymaster['MERCHANT_SERVICE'];
$merchant_logo=$conf_paymaster['MERCHANT_LOGO'];
$merchant_currency=$conf_paymaster['MERCHANT_CURRENCY'];
$template_file=$conf_paymaster['TEMPLATE'];
$log_forms=$conf_paymaster['LOG_FORMS'];

// разбираемся с настройками самого paymaster
$debug=$conf_paymaster['DEBUG'];
$method=$conf_paymaster['SEND_METHOD'];
$paymaster_link=$conf_paymaster['PAYMASTER_LINK'];
$merchant_id=$conf_paymaster['MERCHANT_ID'];
$lang=$conf_paymaster['LANG'];
$good_url=$conf_paymaster['GOOD_URL'];
$bad_url=$conf_paymaster['BAD_URL'];


function paymaster_form($customer_id,$debug,$method,$action_url,$merchant_id, $lang,$good_url,$bad_url,$merchant_currency, $db, $tariff) {

	$tariff_cost='';$form_tariffs='';
     $form='<p> <form action="'.$action_url.'" method="'.$method.'">';
     $arr = simple_queryall("SELECT name,Fee FROM tariffs ORDER BY Fee");
     if ($arr) {
         foreach ($arr as $k => $v) {
			if ($tariff == $v['name']) {
				$tariff_cost=$v['Fee'];
            }
             //не забываем что суммы в копейках
			$form_tariffs.="<input type='radio' name='amount' value='$v[Fee]'><span id='tariff_$v[Fee]'>$v[Fee] $merchant_currency ($v[name])</span><br>";
         }
         
     }/* else {
         $form.='<input type="text" name="LMI_PAYMENT_AMOUNT"> '.$merchant_currency;
     } */
	$form.="<input type='radio' name='amount' value='0' CHECKED><input type='number' name='amount_val' min='1' max='3000' value='$tariff_cost'> $merchant_currency<br>";
	$form.=$form_tariffs;
	//передаем прочие нужные параметры
     $form.='<input type="hidden" name="LMI_PAYMENT_NO" value="'.$customer_id.'">';
	 $form.='<input type="hidden" name="LMI_PAYMENT_AMOUNT" value="'.$tariff_cost.'">';
//     $form.='<input type="hidden" name="good" value="'.$good_url.'">';
//     $form.='<input type="hidden" name="bad" value="'.$bad_url.'">';
	 $form.='<input type="hidden" name="LMI_PAYMENT_DESC">';
     $form.='<input type="hidden" name="LMI_MERCHANT_ID" value="'.$merchant_id.'">';
	 if ($debug) {
		$form.='<input type="hidden" name="LMI_PAYMENT_SYSTEM" value="18">';
	 }
     $form.='<br> <input type="submit" value="Оплатить">';
     $form.='</form> </p>';
     
     return($form);
}

// строим форму выбора сумы платежа
$payment_form=paymaster_form($customer_id, $debug, $method, $paymaster_link, $merchant_id, $lang, $good_url, $bad_url,$merchant_currency, $db, $tariff);

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
