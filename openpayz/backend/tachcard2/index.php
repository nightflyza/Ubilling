<?php


/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты 
 * при помощи платежного сервиса Tachcard
 * 
 */

//Ловим методом GET виртуальный идентификатор пользователя
if (isset($_GET['customer_id'])) {
    $customer_id=$_GET['customer_id'];
} else {
    die('customer_id fail');
}

// подгружаем конфиг
$conf_tachcard=parse_ini_file("config/tachcard.ini");

// выбираем нужные опции мерчанта
$baseUrl=$conf_tachcard['URL'];
$serviceName=$conf_tachcard['SERVICE'];
$ispName=$conf_tachcard['ISP_NAME'];
$ispUrl=$conf_tachcard['ISP_URL'];
$ispLogo=$conf_tachcard['ISP_LOGO'];
$availableAmounts=$conf_tachcard['AMOUNTS'];
$selectLabel=$conf_tachcard['SELECT_TEXT'];
$currency=$conf_tachcard['CURRENCY'];

function paymentTachcardPayForm($customer_id, $availableAmounts, $currency, $baseUrl) {
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

$payment_form=paymentTachcardPayForm($customer_id, $availableAmounts,$currency,$baseUrl);

//показываем все что нужно в темплейт
include("template.html");


?>
