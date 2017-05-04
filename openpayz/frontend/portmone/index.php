<?php

/*
 * Фронтенд для получения уведомлений от PORTMONE в виде POST XML
 * http://store.nightfly.biz/st/1421855512/XML.Portmone.Req.009.doc
 */


// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// ловим ответ о транзакции в виде POST XML
if (!empty ($_REQUEST['data'])) {
    $xml=$_REQUEST['data'];
} else {
   die("Get POST xml: FAIL");
}

function po_CheckTransaction($hash) {
    $hash='PORT_'.  loginDB_real_escape_string($hash);
    $query="SELECT `id` from `op_transactions` WHERE `hash`='".$hash."'";
    $data=  simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

//дебаг  данные

if (!empty ($xml)) {
    //разбираем на куски пойманный XML
    $xml_arr =  xml2array($xml);
    
        $customerid=$xml_arr['BILLS']['BILL']['PAYER']['CONTRACT_NUMBER'];
        $summ=$xml_arr['BILLS']['BILL']['PAYED_AMOUNT'];
        $bill_id=$xml_arr['BILLS']['BILL']['BILL_ID'];
        $paysys='PORTMONE';
        $note='';
        $hash=md5('PORT_'.$bill_id);

if (po_CheckTransaction($hash)) {
$allcustomers=  op_CustomersGetAll();
if (isset($allcustomers[$customerid])) {

                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();

                   $reply='<?xml version="1.0" encoding="UTF-8"?>
                    <RESULT>
                       <ERROR_CODE>0</ERROR_CODE>
                       <REASON>OK</REASON>
                    </RESULT>
                    ';
                die($reply);

                } else {

                   $reply='<?xml version="1.0" encoding="UTF-8"?>
                    <RESULT>
                       <ERROR_CODE>15</ERROR_CODE>
                       <REASON>User_Not_Found</REASON>
                    </RESULT>
                    ';
		die($reply);
	}

		} else {
		$reply='<?xml version="1.0" encoding="UTF-8"?>
                    <RESULT>
                       <ERROR_CODE>0</ERROR_CODE>
                       <REASON>success</REASON>
                    </RESULT>
		';
                die($reply);

		}
} else {
    die('Input XML: FAIL | EMPTY');
	
}

?>
