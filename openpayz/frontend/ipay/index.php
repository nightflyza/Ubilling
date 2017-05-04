<?php

/*
 * Фронтенд для получения уведомлений от IPAY в виде POST XML
 * 
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// ловим ответ о транзакции в виде POST XML
if (!empty($_POST['xml'])) {
    $xml=$_POST['xml'];
} else {
   die("Get POST xml: FAIL");
}

//дебаг  данные

/*
$xml='';
*/

// грязный хак для MagicQoutes
//$xml=stripslashes($xml);

if (!empty ($xml)) {
    //разбираем на куски пойманный XML
    $xml_arr =  xml2array($xml);
    
    //если флаг удачности (5) присутствует - создаем транзакцию
    if ($xml_arr['payment']['status']==5) {
        $hash=$xml_arr['payment']['ident'];
        $summ=$xml_arr['payment']['amount'];
        //не забываем что сумма в копейках
        $summ=($summ/100);
        $customerid=vf($xml_arr['payment']['transactions']['transaction'][0]['desc'],3);
        //а это очередной грязный хак для пополнений с их сайта
        if (strlen($customerid)>10) {
        $customerid=substr($customerid,0,10);
        }
        $paysys='IPAY';
        $note=$xml;
        //регистрируем новую транзакцию
        op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
        //вызываем обработчики необработанных транзакций
        op_ProcessHandlers();
        print('Transaction status: OK');
        
    } else {
        die('Transaction status: FAIL | STATUS: '.$xml_arr['payment']['status']);
    }
    
} else {
    die('Input XML: FAIL | EMPTY');
}





?>
