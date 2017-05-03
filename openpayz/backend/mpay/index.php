<?php


/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты 
 * при помощи платежного сервиса mpay.lifecell.com.ua
 * 
 */

//Ловим методом GET виртуальный идентификатор пользователя
if (isset($_GET['customer_id'])) {
    $customer_id=$_GET['customer_id'];
} else {
    die('customer_id fail');
}

// подгружаем конфиг
$conf_mpay=parse_ini_file("config/mpay.ini");

// выбираем нужные опции мерчанта
$baseUrl=$conf_mpay['URL'];
$serviceName=$conf_mpay['SERVICE'];
$ispName=$conf_mpay['ISP_NAME'];
$ispUrl=$conf_mpay['ISP_URL'];
$ispLogo=$conf_mpay['ISP_LOGO'];
$availableAmounts=$conf_mpay['AMOUNTS'];
$selectLabel=$conf_mpay['SELECT_TEXT'];
$currency=$conf_mpay['CURRENCY'];

function paymentEasyPayForm($customer_id, $availableAmounts, $currency, $baseUrl) {
    $customer_id=trim($customer_id);
    $availableAmounts=  explode(',', $availableAmounts);
    $selector='';
    if (!empty($availableAmounts)) {
        $i=0;
        foreach ($availableAmounts as $eachamount) {
            $eachamount=trim($eachamount);
                 //выставляем первую цену отмеченной
             if ($i==0) {
                 $selected='CHECKED';
              } else {
                  $selected='';
              }
              
             //не забываем что суммы в копейках
             $selector.='<input type="radio" name="amount" value="'.$eachamount.'" '.$selected.' id="am_'.$i.'">';
             $selector.='<label for="am_'.$i.'">'.$eachamount.' '.$currency.'</label> <br>';
             $i++;
        }
    }
    
    $form='
        <form action="'.$baseUrl.'" method="GET">
            <input type="hidden" name="account" value="'.$customer_id.'" >
            <br>
            '.$selector.'
            <br>
            <input type="submit">
        </form>
        ';
    return($form);
}

$payment_form=paymentEasyPayForm($customer_id, $availableAmounts,$currency,$baseUrl);

//показываем все что нужно в темплейт
include("template.html");


?>
