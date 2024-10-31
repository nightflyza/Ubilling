<?php

/*
 * Интерфейсная часть показывающаяся пользователю перед совершением оплаты 
 * при помощи платежного сервиса City24
 * 
 */

//Ловим методом GET виртуальный идентификатор пользователя
if (isset($_GET['customer_id'])) {
    $customer_id=$_GET['customer_id'];
} else {
    die('customer_id fail');
}

// подгружаем конфиг
$confCity24 = parse_ini_file("config/city24.ini");

// выбираем нужные опции мерчанта
$serviceName = $confCity24['SERVICE'];
$ispName = $confCity24['ISP_NAME'];
$ispUrl = $confCity24['ISP_URL'];
$ispLogo = $confCity24['ISP_LOGO'];
$selectLabel = $confCity24['SELECT_TEXT'];
$form_next = $confCity24['FORM_NEXT'];

function paymentEasyPayForm($customer_id) {
    global $confCity24;
    $customer_id=trim($customer_id);
    $availableAmounts=  explode(',', $confCity24['AVAIL_PRICES']);
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
             $selector .= '<input type="radio" name="amount" value="' . $eachamount . '" ' . $selected . ' id="am_' . $i . '">';
             $selector .= '<label for="am_' . $i . '">' . $eachamount . ' ' . $confCity24['CURRENCY'] . '</label> <br>';
             $i++;
        }
    }
    if (isset($confCity24['CUSTOM_PRICE']) and ! empty($confCity24['CUSTOM_PRICE'])) {
        // Script for change custom amount value
        $selector .= '<script>
                    function change_custom_amount(){
                        var custom_amount = document.getElementById("radio_custom_amount");
                        custom_amount.value = document.getElementById("input_custom_amount").value;
                    }

                </script>
        ';

        $selector .= '<input type="radio" name="amount" value="' . $confCity24['CUSTOM_PRICE'] . '" id="radio_custom_amount">';
        $selector .= '<input onchange="change_custom_amount()" id="input_custom_amount" type="number" style="width: 4em;" value="' . $confCity24['CUSTOM_PRICE'] . '" min="1" step="any" /> ' . $confCity24['CURRENCY'] . '<br>';
    }

    $form = '
        <form action="' . $confCity24['URL'] . '" method="GET">
            <input type="hidden" name="acc_number" value="' . $customer_id . '" >
            <br>
            ' . $selector . '
            <br>
            <input type="submit" value="Далі" >
        </form>
        ';
    return($form);
}

$payment_form = paymentEasyPayForm($customer_id);

//показываем все что нужно в темплейт
include("template.html");
?>
