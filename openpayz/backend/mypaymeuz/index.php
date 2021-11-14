<?php
$paymeConf = parse_ini_file('config/mypaymeuz.ini');

// подключаем API MySQL
include ("../../libs/api.mysql.php");

//вытаскиваем из конфига все что нам нужно в будущем
$ispUrl = $paymeConf['TEMPLATE_ISP_URL'];
$ispName = $paymeConf['TEMPLATE_ISP'];
$ispLogo = $paymeConf['TEMPLATE_ISP_LOGO'];
$merchant_service = $paymeConf['MERCHANT_SERVICE'];

/*
 * shows payment summ selection form
 * 
 * @return string
 */
function payme_PricesForm() {
    global $paymeConf;
    $result = '<form action="" method="POST">';
    $addCommission = (isset($paymeConf['ADD_COMMISSION'])) ? $paymeConf['ADD_COMMISSION'] : 1;
    if (!empty($paymeConf['AVAIL_PRICES'])) {
        $pricesArr = array();
        $pricesRaw = explode(',', $paymeConf['AVAIL_PRICES']);
        if (!empty($pricesRaw)) {
           $i=0;
            foreach ($pricesRaw as $eachPrice) {
             $selected = ($i==0) ? 'CHECKED' : '';
             $result.= '<input type="radio" name="amount" value="' . (trim($eachPrice)*($addCommission)) . '" ' . $selected . '> ' . trim($eachPrice) . ' ' . $paymeConf['TEMPLATE_CURRENCY'] . '<br>';
             $i++;
            }
        }
    }

    if (isset($paymeConf['CUSTOM_PRICE']) AND ! empty($paymeConf['CUSTOM_PRICE'])) {
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

        if (!empty($paymeConf['AVAIL_PRICES'])) {
            $result.= '<input type="radio" name="amount" value="' . $paymeConf['CUSTOM_PRICE'] . '" id="radio_custom_amount" onClick="change_custom_amount()">';
        } else {
            $result.= '<input type="hidden" name="amount" value="' . $paymeConf['CUSTOM_PRICE'] . '" id="radio_custom_amount">';
        }

        $result.= '<input onchange="change_custom_amount()" id="input_custom_amount" type="number" style="width: 4em;" value="' . $paymeConf['CUSTOM_PRICE'] . '" min="' . $paymeConf['CUSTOM_PRICE'] . '" step="any" /> ' . $paymeConf['TEMPLATE_CURRENCY'] . '<br>';
    }


    $result.= '<input type="submit" value="' . $paymeConf['TEMPLATE_NEXT'] . '">';
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
function payme_PaymentForm($customer_id) {
    global $paymeConf;

    $lang = $paymeConf['LANG'];
    $paymentDescr = $paymeConf['PAYMENT_DESCR'] . ' ' . $customer_id;
    $genQREnabled = $paymeConf['QR_CODE_ON'];
    $returnURL = $paymeConf['RETURN_URL'];
    $customerIDField =  $paymeConf['CUSTOMERID_FIELD_NAME'];
    $summ = (trim($_POST['amount']) * 100);

    if (isset($paymeConf['MERCHANT_ID']['default'])) {
        $avaibleTagsRaw = explode(',', $paymeConf['AVAIBLE_TAGS_ID']);
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
                $merchant_id = $paymeConf['MERCHANT_ID'][$tag_id];
            } else {
                $merchant_id = $paymeConf['MERCHANT_ID']['default'];
            }
        } else {
            $merchant_id = $paymeConf['MERCHANT_ID']['default'];
        }
    } else {
        $merchant_id = $paymeConf['MERCHANT_ID'];
    }

    $qrControl = '';
    $qrJS = '';
    if ($genQREnabled) {
        $qrJS = 'Paycom.QR(\'#form-payme\', \'#qr-container\')';
        $qrControl = '
    <input type="hidden" name="qr" data-width="250">
    <div id="qr-container"></div>
        ';
    }

    $result = "<h2>" . $paymeConf['TEMPLATE_ISP_SERVICE'] . " " . $customer_id . "</h2>";
    $result.= '<br /><br />';
    $result.= '
<script src="https://cdn.paycom.uz/integration/js/checkout.min.js"></script>
<script type="text/javascript">
    window.onload = function() {
                       Paycom.Button(\'#form-payme\', \'#button-container\');
                       ' . $qrJS . ' 
                    };
</script>
<form id="form-payme" method="POST" action="https://checkout.paycom.uz/">
    <input type="hidden" name="merchant" value="' . $merchant_id . '">
    <input type="hidden" name="account[' . $customerIDField . ']" value="' . $customer_id . '">
    <input type="hidden" name="amount" value="' . $summ . '">
    <input type="hidden" name="lang" value="' . $lang . '">
    <input type="hidden" name="callback" value="' . $returnURL . '"/>
    <input type="hidden" name="button" data-type="svg" value="colored">
    <div id="button-container"></div>
    <br />
    ' . $qrControl . '
</form>    
    ';

    return ($result);
}

/*
 * main codepart
 */
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
    if (!isset($_POST['amount'])) {
        $paymentForm = payme_PricesForm();
    } else {
        $paymentForm = payme_PaymentForm($customer_id);
    }

    //рендерим все в темплейт
    include('template.html');
} else {
    die('WRONG_CUSTOMERID');
}

?>
