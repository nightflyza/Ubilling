<?php

/**
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты 
 * при помощи платежного сервиса coin.com.ua
 */
//Ловим методом GET виртуальный идентификатор пользователя
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
} else {
    die('customer_id fail');
}

// подгружаем конфиг
$conf = parse_ini_file("config/coin.ini");

// выбираем нужные опции мерчанта
$baseUrl = $conf['URL'];
$serviceName = $conf['SERVICE'];
$ispName = $conf['ISP_NAME'];
$ispUrl = $conf['ISP_URL'];
$ispLogo = $conf['ISP_LOGO'];
$availableAmounts = $conf['AMOUNTS'];
$selectLabel = $conf['SELECT_TEXT'];
$currency = $conf['CURRENCY'];

function paymentForm($customer_id, $availableAmounts, $currency, $baseUrl) {
    $customer_id = trim($customer_id);
    $availableAmounts = explode(',', $availableAmounts);
    $selector = '';
    if (!empty($availableAmounts)) {
        $i = 0;
        foreach ($availableAmounts as $eachamount) {
            $eachamount = trim($eachamount);
            //выставляем первую цену отмеченной
            if ($i == 0) {
                $selected = 'CHECKED';
            } else {
                $selected = '';
            }

            $selector.='<input type="radio" name="amount" value="' . $eachamount . '" ' . $selected . ' id="am_' . $i . '">';
            $selector.='<label for="am_' . $i . '">' . $eachamount . ' ' . $currency . '</label> <br>';
            $i++;
        }
    }

    $form = '
        <form action="' . $baseUrl . '" method="GET">
            <input type="hidden" name="account" value="' . $customer_id . '" >
            <br>
            ' . $selector . '
            <br>
            <input type="submit">
        </form>
        ';
    return($form);
}

$payment_form = paymentForm($customer_id, $availableAmounts, $currency, $baseUrl);

//показываем все что нужно в темплейт
include("template.html");
?>
