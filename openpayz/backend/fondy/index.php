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
$conf_fondy = parse_ini_file("config/fondy.ini");

// выбираем нужные нам переменные о мерчанте
$merchant_name = $conf_fondy['MERCHANT_NAME'];
$merchant_url = $conf_fondy['MERCHANT_URL'];
$merchant_service = $conf_fondy['MERCHANT_SERVICE'];
$merchant_logo = $conf_fondy['MERCHANT_LOGO'];
$template_file = $conf_fondy['TEMPLATE'];

/**
 * Renders amount select form
 * 
 * @global array $conf_fondy
 * 
 * @return string
 */
function renderAmountForm() {
    global $conf_fondy;
    $avail_prices = explode(',', $conf_fondy['AVAIL_PRICES']);

    $result = '<h3>' . $conf_fondy['LOCALE_AMOUNT'] . '</h3> <br>';
    $result.= '<form method="POST" action="">';
    if (!empty($avail_prices)) {
        $i = 0;
        foreach ($avail_prices as $io => $eachprice) {
            $selected = ($i == 0) ? 'CHECKED' : '';
            $result.='<input type="radio" name="amount" id="am_' . $i . '" value="' . $eachprice . '" ' . $selected . '>';
            $result.='<label for="am_' . $i . '">' . $eachprice . ' ' . $conf_fondy['MERCHANT_CURRENCY'] . '</label> <br>';
            $i++;
        }
    }

    $result.='<br>';
    $result.='<input type="submit" value="' . $conf_fondy['LOCALE_SUBMIT'] . '">';
    $result.='</form>';

    return ($result);
}

/**
 * Renders JS payment form
 * 
 * @param int $amount
 * 
 * @return string
 */
function renderForm($amount) {
    global $conf_fondy, $customer_id;
    $amount = vf($amount, 3);
    $result = $conf_fondy['LOCALE_SUM'] . ': ' . $amount . ' ' . $conf_fondy['MERCHANT_CURRENCY'] . ' ';
    $result.= '
<script src="https://api.fondy.eu/static_common/v1/checkout/oplata.js"></script>
<script>
	var button = $ipsp.get(\'button\');
	button.setMerchantId(' . $conf_fondy['MERCHANT_ID'] . ');
	button.setAmount(' . $amount . ', \'' . $conf_fondy['FONDY_CURRENCY'] . '\', true);
	button.setResponseUrl(\'' . $conf_fondy['RESPONSE_URL'] . '\');
        button.addParam(\'server_callback_url\',\'' . $conf_fondy['FRONTEND_URL'] . '\');
	button.setHost(\'api.fondy.eu\');
	button.addField({
		label: \'Payment ID\',
		name: \'paymentid\',
                value: \'' . $customer_id . '\',
                readonly :true,
		required: true
	});
</script>
<button onclick="location.href=button.getUrl()">' . $conf_fondy['LOCALE_SUBMIT'] . '</button>
';

    return ($result);
}

if (isset($_POST['amount'])) {
// собираем форму для отправки ПС
    $payment_form = renderForm($_POST['amount']);
} else {
    //формочка выбора суммы платежа
    $payment_form = renderAmountForm();
}


//показываем все что нужно в темплейт
include($template_file);
?>
