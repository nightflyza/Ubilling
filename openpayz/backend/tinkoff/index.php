<?php

/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты
 *
 */

//Ловим методом GET виртуальный идентификатор пользователя
if (isset($_GET['customer_id'])) {
    $customer_id=$_GET['customer_id'];
} else {
    $customer_id='';
}

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// подгружаем конфиг
$conf_ipay=parse_ini_file("config/config.ini");

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
//$ipay_sandbox=$conf_ipay['IPAY_SANDBOX'];
$support_link=$conf_ipay['SUPPORT_LINK'];
$merchant_id=$conf_ipay['MERCHANT_ID'];
$avail_prices=explode(',',$conf_ipay['AVAIL_PRICES']);


//показываем все что нужно в темплейт
include($template_file);

?>
