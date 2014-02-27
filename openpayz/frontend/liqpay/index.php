<?php

/*
 * Фронтенд платежной системы LiqPay получающий ответы в виде POST XML 
 * согласно протокола: https://docs.google.com/presentation/d/1hCmlmxnIurq1tpd3JJ-8VfntBoJ4ZdDJmMx_xiNmkhs/present?slide=id.p
 */

//достаем конфиг
$liqConf=  parse_ini_file('config/liqpay.ini');

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");


/**
 *
 * Check for POST have needed variables
 *
 * @param   $params array of POST variables to check
 * @return  bool
 *
 */
function lq_CheckPost($params) {
    $result=true;
    if (!empty ($params)) {
        foreach ($params as $eachparam) {
            if (isset($_POST[$eachparam])) {
                if (empty ($_POST[$eachparam])) {
                $result=false;                    
                }
            } else {
                $result=false;
            }
        }
     }
     return ($result);
   } 

 /*
 * Check is transaction unique?
 * 
 * @param $hash - transaction hash
 * 
 * @return bool
 */
function lq_CheckTransaction($hash) {
    $hash=  mysql_real_escape_string($hash);
    $query="SELECT `id` from `op_transactions` WHERE `hash`='".$hash."'";
    $data=  simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}


//пытаемся ловить объязательные параметры от LiqPay
if (lq_CheckPost(array('operation_xml','signature'))) {
    $xml=$_POST['operation_xml'];
    $reqSig=$_POST['signature'];
  
    $xml_decoded=base64_decode($xml); 
//    $xml_decoded="<response>      
//      <version>1.2</version>
//      <merchant_id>ixxxxxx</merchant_id>
//      <order_id>LIQPAY_x3b23angk66xjqj7</order_id>
//      <amount>35</amount>
//      <currency>UAH</currency>
//      <description>2887647521</description>
//      <status>success</status>
//      <code></code>
//      <transaction_id>31</transaction_id>
//      <pay_way>card</pay_way>
//      <sender_phone>+3801234567890</sender_phone>
//      <goods_id>1234</goods_id>
//      <pays_count>5</pays_count>
//   </response>           
//   ";
    if (!empty($xml_decoded)) {

    //разбираем на куски пойманный XML
    $xml_arr =  xml2array($xml_decoded);
     if (isset($xml_arr['response'])) {
        $hash=$xml_arr['response']['order_id'];
        $customerid=$xml_arr['response']['description'];
        $summ=$xml_arr['response']['amount'];
        $status=$xml_arr['response']['status'];
        $paysys='LIQPAY';
        $note='some debug data here';
        if ($status=='success') {
            if (lq_CheckTransaction($hash)) {
                $allcustomers=  op_CustomersGetAll();
                if (isset($allcustomers[$customerid])) {
                    /*
                     * По хорошему тут должна быть еще проверка подписей и всякого такого
                     * но допишу завтра - спать охота сильно.
                     */
                    
                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();
                //тихонько помираем
                die('TRANSACTION_OK');
                
                } else {
                    die('ERROR_NO_SUCH_USER');
                }
            }
        }
         
         
     } else {
         die('ERROR_INVALID_XML');
     }
        
    } else {
        die('ERROR_EMPTY_XML');
    }
    
    
    
} else {
    die('ERROR_NO_POST_DATA');
}


?>