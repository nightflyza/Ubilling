<?php
$liqConf = parse_ini_file('config/liqpay.ini');

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
    if (!empty($liqConf['AVAIL_PRICES'])) {
        $pricesArr = array();
        $pricesRaw = explode(',', $liqConf['AVAIL_PRICES']);
        if (!empty($pricesRaw)) {
           $i=0;
            foreach ($pricesRaw as $eachPrice) {
             $selected = ($i==0) ?'CHECKED' : '' ;
             $result.= '<input type="radio" name="amount" value="' . $eachPrice . '" ' . $selected . '> ' . $eachPrice . ' ' . $liqConf['TEMPLATE_CURRENCY'] . '<br>';
             $i++;
            }
        }
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

    $merchant_id = $liqConf['MERCHANT_ID'];
    $signature = $liqConf['SIGNATURE'];
    $method = $liqConf['METHOD'];
    $currency = $liqConf['CURRENCY'];
    $summ = $_POST['amount'];
    $resultUrl = $liqConf['RESULT_URL'];
    $serverUrl = $liqConf['SERVER_URL'];
    $session = lq_SessionGen();

    $result = "<h2>" . $liqConf['TEMPLATE_ISP_SERVICE'] . " " . $customer_id . "</h2>";
    $liqpay = new LiqPay($merchant_id, $signature);
        $result.= $liqpay->cnb_form(array(
                'action'         => 'pay',
                'amount'         => $summ,
                'currency'       => $currency,
                'description'    => $customer_id,
                'order_id'       => $session,
                'result_url'       => $resultUrl,
                'server_url'       => $serverUrl,
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
