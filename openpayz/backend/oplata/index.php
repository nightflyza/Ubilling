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
$template_file = $conf_oplata['TEMPLATE'];

/**
 * Renders amount select form
 * 
 * @global array $conf_oplata
 * 
 * @return string
 */
function renderAmountForm() {
    global $conf_oplata;
    $avail_prices = explode(',', $conf_oplata['AVAIL_PRICES']);

    $result = '<h3>' . $conf_oplata['LOCALE_AMOUNT'] . '</h3> <br>';
    $result.= '<form method="POST" action="">';
    if (!empty($avail_prices)) {
        $i = 0;
        foreach ($avail_prices as $io => $eachprice) {
            $selected = ($i == 0) ? 'CHECKED' : '';
            $result.='<input type="radio" name="amount" id="am_' . $i . '" value="' . $eachprice . '" ' . $selected . '>';
            $result.='<label for="am_' . $i . '">' . $eachprice . ' ' . $conf_oplata['MERCHANT_CURRENCY'] . '</label> <br>';
            $i++;
        }
    }

    $result.='<br>';
    $result.='<input type="submit" value="' . $conf_oplata['LOCALE_SUBMIT'] . '">';
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
    global $conf_oplata, $customer_id;
    $amount = vf($amount, 3);
    $result = $conf_oplata['LOCALE_SUM'] . ': ' . $amount . ' ' . $conf_oplata['MERCHANT_CURRENCY'] . ' ';
    $result.= '
<script src="https://api.oplata.com/static_common/v1/checkout/oplata.js"></script>
<script>
	var button = $ipsp.get(\'button\');
	button.setMerchantId(' . $conf_oplata['MERCHANT_ID'] . ');
	button.setAmount(' . $amount . ', \'' . $conf_oplata['OPLATA_CURRENCY'] . '\', true);
	button.setResponseUrl(\'' . $conf_oplata['RESPONSE_URL'] . '\');
        button.addParam(\'server_callback_url\',\'' . $conf_oplata['FRONTEND_URL'] . '\');
	button.setHost(\'api.oplata.com\');
	button.addField({
		label: \'Payment ID\',
		name: \'paymentid\',
                value: \'' . $customer_id . '\',
                readonly :true,
		required: true
	});
</script>
<button onclick="location.href=button.getUrl()">' . $conf_oplata['LOCALE_SUBMIT'] . '</button>
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
