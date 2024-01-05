<?php
$clickuzConf = parse_ini_file('config/myclickuz.ini');

// подключаем API MySQL
include ("../../libs/api.mysql.php");

//вытаскиваем из конфига все что нам нужно в будущем
$ispUrl = $clickuzConf['TEMPLATE_ISP_URL'];
$ispName = $clickuzConf['TEMPLATE_ISP'];
$ispLogo = $clickuzConf['TEMPLATE_ISP_LOGO'];
$merchant_service = $clickuzConf['MERCHANT_SERVICE'];

/*
 * shows payment summ selection form
 * 
 * @return string
 */
function clickuz_PricesForm() {
    global $clickuzConf;
    $result = '<form action="" method="POST">';
    $addCommission = (isset($clickuzConf['ADD_COMMISSION'])) ? $clickuzConf['ADD_COMMISSION'] : 1;
    if (!empty($clickuzConf['AVAIL_PRICES'])) {
        $pricesArr = array();
        $pricesRaw = explode(',', $clickuzConf['AVAIL_PRICES']);
        if (!empty($pricesRaw)) {
           $i=0;
            foreach ($pricesRaw as $eachPrice) {
             $selected = ($i==0) ? 'CHECKED' : '';
             $result.= '<input type="radio" name="amount" value="' . (trim($eachPrice)*($addCommission)) . '" ' . $selected . '> ' . trim($eachPrice) . ' ' . $clickuzConf['TEMPLATE_CURRENCY'] . '<br>';
             $i++;
            }
        }
    }

    if (isset($clickuzConf['CUSTOM_PRICE']) AND ! empty($clickuzConf['CUSTOM_PRICE'])) {
        // Script for change custom amount value
        $result.= '<script>
                    function change_custom_amount(){
                        var custom_amount = document.getElementById("radio_custom_amount");
                        custom_amount.value = document.getElementById("input_custom_amount").value;
                        custom_amount.value = (custom_amount.value * ' . $addCommission . ').toFixed(2);
                    }
                    
                     document.addEventListener(\'DOMContentLoaded\', function() {
                        // just to apply $addCommission after the page loads
                        change_custom_amount();
                     }, false);
                </script>
        ';

        if (!empty($clickuzConf['AVAIL_PRICES'])) {
            $result.= '<input type="radio" name="amount" value="' . $clickuzConf['CUSTOM_PRICE'] . '" id="radio_custom_amount" onClick="change_custom_amount()">';
        } else {
            $result.= '<input type="hidden" name="amount" value="' . $clickuzConf['CUSTOM_PRICE'] . '" id="radio_custom_amount">';
        }

        $result.= '<input onchange="change_custom_amount()" id="input_custom_amount" type="number" style="width: 4em;" value="' . $clickuzConf['CUSTOM_PRICE'] . '" min="' . $clickuzConf['CUSTOM_PRICE'] . '" step="any" /> ' . $clickuzConf['TEMPLATE_CURRENCY'] . '<br>';
    }


    $result.= '<input type="submit" value="' . $clickuzConf['TEMPLATE_NEXT'] . '">';
    $result.= '</form>';

    return ($result);
}

/*
 * returns form with ClickUZ payment button
 * 
 * @param $customer_id string valid Payment ID
 * 
 * @return string
 */
function clickuz_PaymentForm($customer_id) {
    global $clickuzConf;

    $cardType = $clickuzConf['CARD_TYPE'];
    $summ = trim($_POST['amount']);
    $returnURL = $clickuzConf['RETURN_URL'];

    if (isset($clickuzConf['MERCHANT_ID']['default']) AND isset($clickuzConf['MERCHANT_SERVICE_ID']['default']) ) {
        $avaibleTagsRaw = explode(',', $clickuzConf['AVAIBLE_TAGS_ID']);
        if (!empty($avaibleTagsRaw)) {
            $where = '';
            foreach ($avaibleTagsRaw as $tag) {
                if($tag != end($avaibleTagsRaw)) {
                    $where.= "`tagid` = '" . trim($tag) . "' OR ";
                } else {
                    $where.= "`tagid` = '" . trim($tag) . "'";
                }
            }

            $customer_id_m = mysql_real_escape_string($customer_id);
            $query = "SELECT `tagid` FROM `tags` INNER JOIN `op_customers` ON (`tags`.`login`= `op_customers`.`realid`) WHERE `op_customers`.`virtualid` = '" . $customer_id_m . "' AND (" . $where . ")";
            $data = simple_query($query);
            if (!empty($data)) {
                $tag_id = $data['tagid'];
                $merchant_id = $clickuzConf['MERCHANT_ID'][$tag_id];
                $merchant_service_id = $clickuzConf['MERCHANT_SERVICE_ID'][$tag_id];
            } else {
                $merchant_id = $clickuzConf['MERCHANT_ID']['default'];
                $merchant_service_id = $clickuzConf['MERCHANT_SERVICE_ID']['default'];
            }
        } else {
            $merchant_id = $clickuzConf['MERCHANT_ID']['default'];
            $merchant_service_id = $clickuzConf['MERCHANT_SERVICE_ID']['default'];
        }
    } else {
        $merchant_id = $clickuzConf['MERCHANT_ID'];
        $merchant_service_id = $clickuzConf['MERCHANT_SERVICE_ID'];
    }

    $result = "<h2>" . $clickuzConf['TEMPLATE_ISP_SERVICE'] . " " . $customer_id . "</h2>";
    $result.= '    
<form method="post" action="'. $returnURL . '">
    <script src="https://my.click.uz/pay/checkout.js"
        class="uzcard_payment_button"
        data-service-id="' . $merchant_service_id . '"
        data-merchant-id="' . $merchant_id . '"
        data-transaction-param="' . $customer_id . '"
        data-amount="' . $summ . '"
        data-card-type="' . $cardType . '"
        data-label="Оплатить" <!-- Текст кнопки оплаты -->>
    </script>
</form> ';

    return ($result);
}

/*
 * main codepart
 */
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
    if (!isset($_POST['amount'])) {
        $paymentForm = clickuz_PricesForm();
    } else {
        $paymentForm = clickuz_PaymentForm($customer_id);
    }

    //рендерим все в темплейт
    include('template.html');
} else {
    die('WRONG_CUSTOMERID');
}

?>
