<?php
$liqConf = parse_ini_file('config/liqpay.ini');

// подключаем API MySQL
include ("../../libs/api.mysql.php");

//вытаскиваем из конфига все что нам нужно в будущем
$ispUrl = $liqConf['TEMPLATE_ISP_URL'];
$ispName = $liqConf['TEMPLATE_ISP'];
$ispLogo = $liqConf['TEMPLATE_ISP_LOGO'];
$merchant_service = $liqConf['MERCHANT_SERVICE'];

/*
 * generates random transaction hash
 * 
 * @return string
 */
function lq_SessionGen($size=16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "LIQPAY_";
    for ($p = 0; $p < $size; $p++) {
        $string.= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return ($string);
}

/*
 * shows payment summ selection form
 * 
 * @return string
 */
function lq_PricesForm() {
    global $liqConf;
    $result = '<form action="" method="POST">';
    $addCommission = (isset($liqConf['ADD_COMMISSION'])) ? $liqConf['ADD_COMMISSION'] : 1;
    if (!empty($liqConf['AVAIL_PRICES'])) {
        $pricesArr = array();
        $pricesRaw = explode(',', $liqConf['AVAIL_PRICES']);
        if (!empty($pricesRaw)) {
           $i=0;
            foreach ($pricesRaw as $eachPrice) {
             $selected = ($i==0) ? 'CHECKED' : '';
             $result.= '<input type="radio" name="amount" value="' . (trim($eachPrice)*($addCommission)) . '" ' . $selected . '> ' . trim($eachPrice) . ' ' . $liqConf['TEMPLATE_CURRENCY'] . '<br>';
             $i++;
            }
        }
    }

    if (isset($liqConf['CUSTOM_PRICE']) AND ! empty($liqConf['CUSTOM_PRICE'])) {
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

        if (!empty($liqConf['AVAIL_PRICES'])) {
            $result.= '<input type="radio" name="amount" value="' . $liqConf['CUSTOM_PRICE'] . '" id="radio_custom_amount" onClick="change_custom_amount()">';
        } else {
            $result.= '<input type="hidden" name="amount" value="' . $liqConf['CUSTOM_PRICE'] . '" id="radio_custom_amount">';
        }

        $result.= '<input onchange="change_custom_amount()" id="input_custom_amount" type="number" style="width: 4em;" value="' . $liqConf['CUSTOM_PRICE'] . '" min="' . $liqConf['CUSTOM_PRICE'] . '" step="any" /> ' . $liqConf['TEMPLATE_CURRENCY'] . '<br>';
    }


    $result.= '<input type="submit" value="' . $liqConf['TEMPLATE_NEXT'] . '">';
    $result.= '</form>';

    return ($result);
}

/*
 * returns LiqPay hashed form 
 * 
 * @param $customer_id string valid Payment ID
 * 
 * @return string
 */
function lq_PaymentForm($customer_id) {
    global $liqConf;
    include('LiqPay.php');

    $method = $liqConf['METHOD'];
    $currency = $liqConf['CURRENCY'];
    $summ = trim($_POST['amount']);
    $resultUrl = $liqConf['RESULT_URL'];
    $session = lq_SessionGen();

    if (isset($liqConf['MERCHANT_ID']['default']) AND isset($liqConf['SIGNATURE']['default']) AND isset($liqConf['SERVER_URL']['default']) ) {
        $avaibleTagsRaw = explode(',', $liqConf['AVAIBLE_TAGS_ID']);
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
                $merchant_id = $liqConf['MERCHANT_ID'][$tag_id];
                $signature = $liqConf['SIGNATURE'][$tag_id];
                $serverUrl = $liqConf['SERVER_URL'][$tag_id];
            } else {
                $merchant_id = $liqConf['MERCHANT_ID']['default'];
                $signature = $liqConf['SIGNATURE']['default'];
                $serverUrl = $liqConf['SERVER_URL']['default'];
            }
        } else {
            $merchant_id = $liqConf['MERCHANT_ID']['default'];
            $signature = $liqConf['SIGNATURE']['default'];
            $serverUrl = $liqConf['SERVER_URL']['default'];
        }
    } else {
        $merchant_id = $liqConf['MERCHANT_ID'];
        $signature = $liqConf['SIGNATURE'];
        $serverUrl = $liqConf['SERVER_URL'];
    }

    $result = "<h2>" . $liqConf['TEMPLATE_ISP_SERVICE'] . " " . $customer_id . "</h2>";
    $liqpay = new LiqPay($merchant_id, $signature);
        $result.= $liqpay->cnb_form(array(
                'action'         => 'pay',
                'amount'         => $summ,
                'currency'       => $currency,
                'description'    => $customer_id,
                'order_id'       => $session,
                'result_url'     => $resultUrl,
                'server_url'     => $serverUrl,
                'paytypes'       => $method,
                'version'        => '3'
                ));

    return ($result);
}

/*
 * main codepart
 */
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
    if (!isset($_POST['amount'])) {
        $paymentForm = lq_PricesForm();
    } else {
        $paymentForm = lq_PaymentForm($customer_id);
    }

    //рендерим все в темплейт
    include('template.html');
} else {
    die('WRONG_CUSTOMERID');
}

?>
