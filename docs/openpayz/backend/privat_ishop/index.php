<?php
$pbConf = parse_ini_file('config/privat.ini');

//вытаскиваем из конфига все что нам нужно в будущем
$ispUrl = $pbConf['TEMPLATE_ISP_URL'];
$ispName = $pbConf['TEMPLATE_ISP'];
$ispLogo = $pbConf['TEMPLATE_ISP_LOGO'];
$merchant_service = $pbConf['MERCHANT_SERVICE'];

/*
 * generates random transaction hash
 * 
 * @return string
 */
function pb_SessionGen($size=16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "PB24PAY_";
    for ($p = 0; $p < $size; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return ($string);
 }

/*
 * shows payment summ selection form
 * 
 * @return string
 */
function pb_PricesForm() {
    global $pbConf;
    $result = '<form action="" method="POST">';
    $addCommission = (isset($pbConf['ADD_COMMISSION'])) ? $pbConf['ADD_COMMISSION'] : 1;
    if (!empty($pbConf['AVAIL_PRICES'])) {
        $pricesArr = array();
        $pricesRaw = explode(',', $pbConf['AVAIL_PRICES']);
        if (!empty($pricesRaw)) {
            $i=0;
            foreach ($pricesRaw as $eachPrice) {
             $selected = ($i==0) ?'CHECKED' : '' ;
             $result.= '<input type="radio" name="amount" value="' . (trim($eachPrice)*($addCommission)) . '" ' . $selected . '> ' . trim($eachPrice) . ' ' . $pbConf['TEMPLATE_CURRENCY'] . '<br>';
             $i++;
            }
        }
    }
    if (isset($pbConf['CUSTOM_PRICE']) AND ! empty($pbConf['CUSTOM_PRICE'])) {
        // Script for change custom amount value
        $result.= '<script>
                    function change_custom_amount(){
                        var custom_amount = document.getElementById("radio_custom_amount");
                        custom_amount.value = document.getElementById("input_custom_amount").value;
                        custom_amount.value = (custom_amount.value * ' . $addCommission . ').toFixed(2);
                    }
                </script>
        ';
        $result.= '<input type="radio" name="amount" value="custom_amount" id="radio_custom_amount" onClick="change_custom_amount()">';
        $result.= '<input onchange="change_custom_amount()" id="input_custom_amount" type="number" style="width: 4em;" value="' . $pbConf['CUSTOM_PRICE'] . '" min="' . $pbConf['CUSTOM_PRICE'] . '" step="any" /> ' . $pbConf['TEMPLATE_CURRENCY'] . '<br>';
    }

    $result .= '<input type="submit" value="'.$pbConf['TEMPLATE_NEXT'].'">';
    $result .= '</form>';
    return ($result);
}

/*
 * returns Privat24 hashed form 
 * 
 * @param $customer_id string valid Payment ID
 * 
 * @return string
 */
function pb_PaymentForm($customer_id) {
    global $pbConf;
    
$merchant_id = $pbConf['MERCHANT_ID'];
$summ = $_POST['amount'];
$resultUrl = $pbConf['RESULT_URL'];
$serverUrl = $pbConf['SERVER_URL'];
$session = pb_SessionGen();

$result = '
        <form method="POST" action="' . $pbConf['PBURL'] . '">
                        <input type="hidden" name="amt" value="' . $summ . '"/>
                        <input type="hidden" name="ccy" value="UAH"/>
                        <input type="hidden" name="merchant" value="' . $merchant_id . '"/>
                        <input type="hidden" name="order" value="' . $session . '"/>
                        <input type="hidden" name="details" value="' . $pbConf['TEMPLATE_ISP_SERVICE'] . " " . $customer_id . '"/>
                        <input type="hidden" name="ext_details" value="' . $customer_id . '"/>
                        <input type="hidden" name="return_url" value="' . $resultUrl . '"/>
                        <input type="hidden" name="server_url" value="' . $serverUrl . '"/>
                        <input type="hidden" name="pay_way" value="PRIVAT24"/>
                        <input type="submit" value="' . $pbConf['TEMPLATE_GOPAYMENT'] . '">
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
        $paymentForm = pb_PricesForm();
    } else {
        $paymentForm = pb_PaymentForm($customer_id);
    }

    //рендерим все в темплейт
    include('template.html');

} else {
    die('WRONG_CUSTOMERID');
}

?>
