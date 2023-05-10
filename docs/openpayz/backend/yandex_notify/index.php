<?php
$liqConf = parse_ini_file('config/yandex.ini');

//вытаскиваем из конфига все что нам нужно в будущем
$ispUrl = $liqConf['TEMPLATE_ISP_URL'];
$ispName = $liqConf['TEMPLATE_ISP'];
$ispLogo = $liqConf['TEMPLATE_ISP_LOGO'];
$serviceDesc = $liqConf['SERVICE_DESC'];

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
             $result.= '<input type="radio" name="amount" value="' . trim($eachPrice) . '" ' . $selected . '> ' . trim($eachPrice) . ' ' . '<br>';
             $i++;
            }
        }
    } else {
		$result.= '<input name="amount" type="number" step="100" min="2" max="15000" placeholder="2" maxlength="10" ><br><br>';
	}
    $result.= '<input type="submit" value="' . $liqConf['TEMPLATE_NEXT'] . '">';
    $result.= '</form>';

    return ($result);
}

/*
 * returns YandexMoney hashed form 
 * 
 * @param $customer_id string valid Payment ID
 * https://tech.yandex.ru/money/doc/payment-buttons/reference/forms-docpage/
 * 
 * @return string
 */
function YandexMoneyForm($customer_id) {
    global $liqConf;
    $customer_id=trim($customer_id);

    $form='
        <form method="POST" action="https://money.yandex.ru/quickpay/confirm.xml">
            <input type="hidden" name="receiver" value="' . $liqConf['WALLET_RECEIVER'] . '">
            <input type="hidden" name="formcomment" value="' . $liqConf['TEMPLATE_ISP'] . '">
            <input type="hidden" name="label" value="' . $customer_id . '">
            <input type="hidden" name="quickpay-form" value="small">
            <input type="hidden" name="targets" value="' . $liqConf['TEMPLATE_ISP_SERVICE'] . '">
            <input type="hidden" name="sum" value="' . trim($_POST['amount']) . '" data-type="number">
            <input type="hidden" name="successURL" value="' . $liqConf['TEMPLATE_ISP_LOGO'] . '">
            <input type="hidden" name="need-fio" value="false">
            <input type="hidden" name="need-email" value="false"> 
            <input type="hidden" name="need-phone" value="false">
            <input type="hidden" name="need-address" value="false">
            <h2>' . $liqConf['TEMPLATE_ISP_SERVICE'] . ' ' . $customer_id . '</h2><br />
            ';
    if ($liqConf['PAYMENT_TYPE'] == 'PC') {
        $form.= '<input type="hidden" name="paymentType" value="PC"><br /><br />';
    } elseif ($liqConf['PAYMENT_TYPE'] == 'AC') {
        $form.= '<input type="hidden" name="paymentType" value="AC"><br /><br />';
    } else {
        $form.='
            <label><input type="radio" name="paymentType" value="PC">Яндекс.Деньгами</label>
            <label><input type="radio" name="paymentType" value="AC">Банковской картой</label> <br /><br />
            ';
    }
    $form.='
            <input type="submit" value="Оплатить">
        </form>
        ';
    return($form);
}

/*
 * main codepart
 */
if (isset($_GET['customer_id'])) {
    $customer_id = $_GET['customer_id'];
    if (!isset($_POST['amount'])) {
        $paymentForm = lq_PricesForm();
    } else {
        $paymentForm = YandexMoneyForm($customer_id);
    }

    //рендерим все в темплейт
    include('template.html');
} else {
    die('WRONG_CUSTOMERID');
}

?>
