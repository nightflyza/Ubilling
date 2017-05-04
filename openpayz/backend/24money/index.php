<?php


/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты 
 * при помощи платежного сервиса 24money.com.ua
 * 
 */

//Ловим методом GET виртуальный идентификатор пользователя
if (isset($_GET['customer_id'])) {
    $customer_id=$_GET['customer_id'];
} else {
    die('customer_id fail');
}

// подгружаем конфиг
$conf_24money=parse_ini_file("config/24money.ini");

// выбираем нужные опции мерчанта
$baseUrl=$conf_24money['URL'];
$serviceName=$conf_24money['SERVICE'];
$ispName=$conf_24money['ISP_NAME'];
$ispUrl=$conf_24money['ISP_URL'];
$ispLogo=$conf_24money['ISP_LOGO'];
$availableAmounts=$conf_24money['AMOUNTS'];
$selectLabel=$conf_24money['SELECT_TEXT'];
$currency=$conf_24money['CURRENCY'];

function payment24mForm($customer_id, $availableAmounts, $currency, $baseUrl) {
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

$payment_form=payment24mForm($customer_id, $availableAmounts,$currency,$baseUrl);

//показываем все что нужно в темплейт
include("template.html");


?>
