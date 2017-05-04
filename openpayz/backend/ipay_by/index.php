<?php

/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты
 */

//Ловим виртуальный идентификатор пользователя

if (isset($_GET['customer_id'])) {
    $customer_id=$_GET['customer_id'];
	
} elseif (isset($_POST['customer_id'])){
	$customer_id=$_POST['customer_id'];
} else {
if (!isset($customer_id)) die('customer_id fail');
}


// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// подгружаем конфиг
global $conf_ipay;
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

// Для оплаты нужны только номер заказа или сумма; 
// Cумма может быть и массивом сумм.  
function ipay_form($customer_id,$avail_prices) {
global $conf_ipay;
		 $inputs='';
			$form='';
	if (empty($avail_prices) or is_array($avail_prices)) $title='Выберите сумму';	 
    else $title='Оплата заказа №'.$customer_id.' на сумму '.$avail_prices.' '. $conf_ipay['MERCHANT_CURRENCY'];

	if (!empty ($avail_prices)) {
	  if (is_array($avail_prices)) {
         $i=0;

         foreach ($avail_prices as $eachprice) {
             //выставляем первую цену отмеченной
             if ($i==0) {
                 $selected='CHECKED';
              } else {
                  $selected='';
              }
              
             $inputs.='<input type="radio" name="amount" value="'.$eachprice.'" '.$selected.'> '.($eachprice).' '.$conf_ipay['MERCHANT_CURRENCY'].'<br />'."\n";
             $i++;
         }
         } else {
		 $inputs.='<input type="hidden" name="amount" value="'.$avail_prices.'">' ."\n";
		 }
     } else {
         $inputs.='<input type="text" name="amount"> '.$conf_ipay['MERCHANT_CURRENCY']."\n";
     }
     //передаем прочие нужные параметры
     $inputs.='<input type="hidden" name="pers_acc" value="'.$customer_id.'">'."\n";
     $inputs.='<input type="hidden" name="provider_url" value="'.$conf_ipay['GOOD_URL'].'&id='.$customer_id.'">'."\n";
     $inputs.='<input type="hidden" name="lang" value="'.$conf_ipay['LANG'].'">'."\n";
     $inputs.='<br />
	 <input type="submit">';
// а не в песочнице ли мы?
    if ($conf_ipay['DEBUG']) {
		$form.='<h3 class="title">'.$title."</h3>\n".'<form action="'.$conf_ipay['IPAY_SANDBOX'].'" method="'.$conf_ipay['SEND_METHOD'].'">'."\n";
		$form.=$inputs;
        $inputs.='<input type="hidden" name="srv_no" value="'.$conf_ipay['TEST_MERCHANT_ID'].'">'."\n";
		$form.='</form>';
        } else {	
		$j=0;
		$form.='<h3>'.$conf_ipay['TITLE'].'</h3>';
		if (!empty($conf_ipay['MTS_URL'])&&!empty($conf_ipay['MTS_TITLE'])) {
		$j++;
		$form.='<p><h3 class="title">'.$j++.'. '.$title.$conf_ipay['MTS_TITLE']."</h3>\n".'<form action="'.$conf_ipay['MTS_URL'].'" method="'.$conf_ipay['SEND_METHOD'].'">'."\n";
		$form.=$inputs;
		$inputs.='<input type="hidden" name="srv_no" value="'.$conf_ipay['MERCHANT_ID'].'">'."\n";
		$form.='</form></p>';
        }
		if (!empty($conf_ipay['LIFE_URL'])&&!empty($conf_ipay['LIFE_TITLE'])) {
		$j++;
		$form.='<p><h3 class="title">'.$j++.'. '.$title.$conf_ipay['LIFE_TITLE']."</h3>\n".'<form action="'.$conf_ipay['LIFE_URL'].'" method="'.$conf_ipay['SEND_METHOD'].'">'."\n";
		$form.=$inputs;
        $inputs.='<input type="hidden" name="srv_no" value="'.$conf_ipay['MERCHANT_ID'].'">'."\n";
		$form.='</form></p>';
        }
		if (!empty($conf_ipay['ERIP_TEXT'])&&!empty($conf_ipay['ERIP_TITLE'])) {
		$j++;
		$form.='<p><h3 class="title">'.$j++.'. '.$conf_ipay['ERIP_TITLE']."\n";
		$form.=$conf_ipay['ERIP_TEXT'].'</p>';
		}
		}  
                
		$strtgs=trim(strip_tags($form));
     if ($strtgs==$conf_ipay['TITLE']) $form=__('Nothing found');
	 return($form);
}

// Выводим форму платежа
//$order_array=fn_shop_get_orderdata($customer_id);
$order_array=  op_CustomersGetAll();


$payment_form=ipay_form($customer_id,$avail_prices);
include ($template_file);

?>
