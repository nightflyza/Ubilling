<?php

// для начала получаем айди кастомера
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
} else {
    die('customer_id fail');
}

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");


// подгружаем конфиг
$conf_oplata = parse_ini_file("config/oplata.ini");

// выбираем нужные нам переменные о мерчанте
$merchant_name = $conf_oplata['MERCHANT_NAME'];
$merchant_url = $conf_oplata['MERCHANT_URL'];
$merchant_service = $conf_oplata['MERCHANT_SERVICE'];
$merchant_logo = $conf_oplata['MERCHANT_LOGO'];
$merchant_currency = $conf_oplata['CURRENCY_NAME'];
$oplata_currency = $conf_oplata['OPLATA_CURRENCY'];
$template_file = $conf_oplata['TEMPLATE'];
$merchant_id = $conf_oplata['MERCHANT_ID'];
$avail_prices = explode(',', $conf_oplata['AVAIL_PRICES']);
$oplata_url = $conf_oplata['OPLATA_URL'];
$frontend_url = $conf_oplata['FRONTEND_URL'];
$response_url = $conf_oplata['RESPONSE_URL'];
$localeSubmit = $conf_oplata['LOCALE_SUBMIT'];

/**
 * Renders JS payment form
 * 
 * @global string $frontend_url
 * @global string $localeSubmit
 * @global int    $customer_id
 * @return string
 */
function renderForm() {
    global $frontend_url,$response_url, $localeSubmit, $customer_id,$merchant_id,$oplata_currency;
    $result = '
<script src="https://api.oplata.com/static_common/v1/checkout/oplata.js"></script>
<script>
	var button = $ipsp.get(\'button\');
	button.setMerchantId('.$merchant_id.');
	button.setAmount(2, \''.$oplata_currency.'\', true);
	button.setResponseUrl(\'http://' . $response_url . '\');
        button.addParam(\'server_callback_url\',\'' . $frontend_url . '\');
	button.setHost(\'api.oplata.com\');
	button.addField({
		label: \'Payment ID\',
		name: \'paymentid\',
                value: \'' . $customer_id . '\',
                readonly :true,
		required: true
	});
</script>
<button onclick="location.href=button.getUrl()">' . $localeSubmit . '</button>
';

    return ($result);
}

// строим форму
$payment_form = renderForm();


//показываем все что нужно в темплейт
include($template_file);
?>
