<?php

$mypConf = parse_ini_file('config/mypayprivat.ini');

// подключаем API MySQL
include ("../../libs/api.mysql.php");

//вытаскиваем из конфига все что нам нужно в будущем
$ispUrl = $mypConf['TEMPLATE_ISP_URL'];
$ispName = $mypConf['TEMPLATE_ISP'];
$ispLogo = $mypConf['TEMPLATE_ISP_LOGO'];
$merchant_service = $mypConf['MERCHANT_SERVICE'];

if (!function_exists('rcms_redirect')) {

    /**
     * Shows redirection javascript. 
     * 
     * @param string $url
     * @param bool $header
     */
    function rcms_redirect($url, $header = false) {
        if ($header) {
            @header('Location: ' . $url);
        } else {
            echo '<script language="javascript">document.location.href="' . $url . '";</script>';
        }
    }

}

/*
 * shows payment summ selection form
 * 
 * @return string
 */

function myp_PricesForm() {
    global $mypConf;
    $result = '<form action="" method="POST">';
    $addCommission = (isset($mypConf['ADD_COMMISSION'])) ? $mypConf['ADD_COMMISSION'] : 1;
    if (!empty($mypConf['AVAIL_PRICES'])) {
        $pricesArr = array();
        $pricesRaw = explode(',', $mypConf['AVAIL_PRICES']);
        if (!empty($pricesRaw)) {
            $i = 0;
            foreach ($pricesRaw as $eachPrice) {
                $selected = ($i == 0) ? 'CHECKED' : '';
                $result .= '<input type="radio" name="amount" value="' . (trim($eachPrice) * ($addCommission)) . '" ' . $selected . '> ' . trim($eachPrice) . ' ' . $mypConf['TEMPLATE_CURRENCY'] . '<br>';
                $i++;
            }
        }
    }

    if (isset($mypConf['CUSTOM_PRICE']) AND ! empty($mypConf['CUSTOM_PRICE'])) {
        // Script for change custom amount value
        $result .= '<script>
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
        if (!empty($mypConf['AVAIL_PRICES'])) {
            $result .= '<input type="radio" name="amount" value="' . $mypConf['CUSTOM_PRICE'] . '" id="radio_custom_amount" onClick="change_custom_amount()">';
        } else {
            $result .= '<input type="hidden" name="amount" value="' . $mypConf['CUSTOM_PRICE'] . '" id="radio_custom_amount">';
        }
        $result .= '<input onchange="change_custom_amount()" id="input_custom_amount" type="number" style="width: 4em;" value="' . $mypConf['CUSTOM_PRICE'] . '" min="' . $mypConf['CUSTOM_PRICE'] . '" step="any" /> ' . $mypConf['TEMPLATE_CURRENCY'] . '<br>';
    }

    $result .= '<input type="submit" value="' . $mypConf['TEMPLATE_NEXT'] . '">';
    $result .= '</form>';

    return ($result);
}

/*
 * returns my-payments link
 * 
 * @param $customer_id string valid Payment ID
 * 
 * @return string
 */

function myp_PaymentForm($customer_id) {
    global $mypConf;

    $summ = trim($_POST['amount']);
    if (is_array($mypConf['STATIC_TOKEN'])) {
        if (array_key_exists('default', $mypConf['STATIC_TOKEN'])) {
            $avaibleTagsRaw = explode(',', $mypConf['AVAIBLE_TAGS_ID']);
            if (!empty($avaibleTagsRaw)) {
                $where = '';
                foreach ($avaibleTagsRaw as $tag) {
                    if ($tag != end($avaibleTagsRaw)) {
                        $where .= "`tagid` = '" . trim($tag) . "' OR ";
                    } else {
                        $where .= "`tagid` = '" . trim($tag) . "'";
                    }
                }

                $customer_id_m = mysql_real_escape_string($customer_id);
                $query = "SELECT `tagid` FROM `tags` INNER JOIN `op_customers` ON (`tags`.`login`= `op_customers`.`realid`) WHERE `op_customers`.`virtualid` = '" . $customer_id_m . "' AND (" . $where . ")";
                $data = simple_query($query);
            
                if (!empty($data)) {
                    $tag_id = $data['tagid'];
                    $staticToken = $mypConf['STATIC_TOKEN'][$tag_id];
                } else {
                    $staticToken = $mypConf['STATIC_TOKEN']['default'];
                }
            } else {
                $staticToken = $mypConf['STATIC_TOKEN']['default'];
            }
        } else {
            $staticToken = $mypConf['STATIC_TOKEN'];
        }
    } else {
        $staticToken = $mypConf['STATIC_TOKEN'];
    }

    $result = 'https://my-payments.privatbank.ua/mypayments/customauth/identification/fp/static?staticToken=' . $staticToken . '&acc=' . $customer_id . '&amount=' . $summ;
    return ($result);
}

/*
 * main codepart
 */
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
    if (!isset($_POST['amount'])) {
        $paymentForm = myp_PricesForm();
    } else {
        $paymentForm = myp_PaymentForm($customer_id);
        rcms_redirect($paymentForm);
    }

    //рендерим все в темплейт
    include('template.html');
} else {
    die('WRONG_CUSTOMERID');
}
?>
