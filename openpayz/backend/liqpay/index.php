<?php
$liqConf=  parse_ini_file('config/liqpay.ini');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/libs/api.mysql.php');

//вытаскиваем из конфига все что нам нужно в будущем
$ispUrl=$liqConf['TEMPLATE_ISP_URL'];
$ispName=$liqConf['TEMPLATE_ISP'];
$ispLogo=$liqConf['TEMPLATE_ISP_LOGO'];


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
    $result='<form action="" method="POST">';
    if (!empty($liqConf['AVAIL_PRICES'])) {
        $pricesArr=array();
        $pricesRaw=  explode(',', $liqConf['AVAIL_PRICES']);
        if (!empty($pricesRaw)) {
           $i=0;
            foreach ($pricesRaw as $eachPrice) {
             $selected = ($i==0) ?'CHECKED' : '' ;
             $result.='<input type="radio" name="amount" value="'.$eachPrice.'" '.$selected.'> '.$eachPrice.' '.$liqConf['TEMPLATE_CURRENCY'].'<br>';
             $i++;
            }
        }
    }
    $result.= '<input type="submit" value="'.$liqConf['TEMPLATE_NEXT'].'">';
    $result.='</form>';
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
    
$merchant_id=$liqConf['MERCHANT_ID'];
$signature=$liqConf['SIGNATURE'];
$url=$liqConf['LIQURL'];
$method=$liqConf['METHOD'];
$currency=$liqConf['CURRENCY'];
$summ=loginDB_real_escape_string($_POST['amount']);
$resultUrl=$liqConf['RESULT_URL'];
$serverUrl=$liqConf['SERVER_URL'];
$phone='';
$session=  lq_SessionGen();

    $xml='<request>      
            <version>1.2</version>
            <result_url>'.$resultUrl.'</result_url>
            <server_url>'.$serverUrl.'</server_url>
            <merchant_id>'.$merchant_id.'</merchant_id>
            <order_id>'.$session.'</order_id>
            <amount>'.$summ.'</amount>
            <currency>'.$currency.'</currency>
            <description>'.$customer_id.'</description>
            <default_phone>'.$phone.'</default_phone>
            <pay_way>'.$method.'</pay_way> 
            </request>
            ';
	
	
	$xml_encoded = base64_encode($xml); 
	$lqsignature = base64_encode(sha1($signature.$xml.$signature,1));
	


$result="<h2>".$liqConf['TEMPLATE_ISP_SERVICE']." ".$customer_id."</h2>
      <form action='".$url."' method='POST'>
        <input type='hidden' name='operation_xml' value='$xml_encoded' />
        <input type='hidden' name='signature' value='$lqsignature' />
	<input type='submit' value='".$liqConf['TEMPLATE_GOPAYMENT']."'/>
      </form>";

return ($result);
    
}


/*
 * main codepart
 */
if (isset($_GET['customer_id'])) {
    $customer_id=  loginDB_real_escape_string($_GET['customer_id']);
    if (!isset($_POST['amount'])) {
        $paymentForm= lq_PricesForm();
    } else {
        $paymentForm= lq_PaymentForm($customer_id);
    }

    //рендерим все в темплейт
    include('template.html');
} else {
    die('WRONG_CUSTOMERID');
}

?>
	
