<?php

/*
 * Фронтенд для получения уведомлений от PORTMONE в виде POST XML
 * http://store.nightfly.biz/st/1421855512/XML.Portmone.Req.009.doc
 */

DEFINE('PAYEE_ID', 11111);

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// ловим ответ о транзакции в виде POST XML
if (!empty($_REQUEST['data'])) {
    $xml = $_REQUEST['data'];
} else {
    die("Get POST xml: FAIL");
}

function po_CheckTransaction($hash) {
    $hash = 'PORT_' . mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

function po_TariffGetPricesAll() {
    $query = "SELECT `name`,`Fee` from `tariffs`";
    $allprices = simple_queryall($query);
    $result = array();

    if (!empty($allprices)) {
        foreach ($allprices as $io => $eachtariff) {
            $result[$eachtariff['name']] = $eachtariff['Fee'];
        }
    }

    return ($result);
}

function po_UserGetStargazerData($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `users` WHERE `login`='" . $login . "';";
    $result = simple_query($query);
    return ($result);
}

//дебаг  данные

if (!empty($xml)) {
    //разбираем на куски пойманный XML
    $xml_arr = xml2array($xml);

    if (isset($xml_arr['REQUESTS'])) {

        $customerid = $xml_arr['REQUESTS']['PAYER']['CONTRACT_NUMBER'];
        $allcustomers = op_CustomersGetAll();
        if (isset($allcustomers[$customerid])) {
            $customerLogin = $allcustomers[$customerid];
            $userdata = po_UserGetStargazerData($customerLogin);
            $allTariffs = po_TariffGetPricesAll();
            $amount = $allTariffs[$userdata['Tariff']];
            $userBalance = $userdata['Cash'] * -1;

            $reply = '<?xml version="1.0" encoding="UTF-8"?>
		<RESPONSE>
			<BILLS>
				<PAYEE>' . PAYEE_ID . '</PAYEE>
				<BILL_PERIOD>' . date("my") . '</BILL_PERIOD>
				<BILL>
					<PAYER>
						<CONTRACT_NUMBER>' . $customerid . '</CONTRACT_NUMBER>
					</PAYER>
					<BILL_DATE>' . date("Y-m-d") . '</BILL_DATE>
					<BILL_NUMBER>' . microtime(true) . rand(100000000, 999999999) . '</BILL_NUMBER>
					<AMOUNT>' . $amount . '</AMOUNT>
					<DEBT>' . $userBalance . '</DEBT>
				</BILL>
			</BILLS>
		</RESPONSE>';
            die($reply);
        }
    } elseif (isset($xml_arr['BILLS'])) {
        $customerid = $xml_arr['BILLS']['BILL']['PAYER']['CONTRACT_NUMBER'];
        $summ = $xml_arr['BILLS']['BILL']['PAYED_AMOUNT'];
        $bill_id = $xml_arr['BILLS']['BILL']['BILL_ID'];
        $paysys = 'PORTMONE';
        $note = '';
        $hash = md5('PORT_' . $bill_id);

        if (po_CheckTransaction($hash)) {
            $allcustomers = op_CustomersGetAll();
            if (isset($allcustomers[$customerid])) {

                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();

                $reply = '<?xml version="1.0" encoding="UTF-8"?>
       	             <RESULT>
       	                <ERROR_CODE>0</ERROR_CODE>
       	                <REASON>OK</REASON>
       	             </RESULT>
       	             ';
                die($reply);
            } else {

                $reply = '<?xml version="1.0" encoding="UTF-8"?>
        	            <RESULT>
        	               <ERROR_CODE>15</ERROR_CODE>
        	               <REASON>User_Not_Found</REASON>
        	            </RESULT>
        	            ';
                die($reply);
            }
        } else {
            $reply = '<?xml version="1.0" encoding="UTF-8"?>
                    <RESULT>
                       <ERROR_CODE>0</ERROR_CODE>
                       <REASON>success</REASON>
                    </RESULT>
		';
            die($reply);
        }
    } else {
        die('Input XML: FAIL | WRONG');
    }
} else {
    die('Input XML: FAIL | EMPTY');
}
?>
