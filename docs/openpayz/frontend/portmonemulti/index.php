<?php

/*
 * Фронтенд для получения уведомлений от PORTMONE в виде POST XML
 * http://store.nightfly.biz/st/1421855512/XML.Portmone.Req.009.doc
 */

// Configuration section
define('DEBUG_MODE', 0);

// URL вашего працюючого Ubilling
define('API_URL', 'http://localhost/billing/');
// API KEY Ubilling
define('API_KEY', 'UBxxxxxxxxxxxxxxxx');

// Load API OpenPayz
include ("../../libs/api.openpayz.php");

error_reporting(E_ALL);

// Send main headers
header('Last-Modified: ' . gmdate('r'));
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache");

/**
 * Gets user associated agent data JSON
 * 
 * @param string $userlogin
 * 
 * @return string
 */
function getAgentData($userlogin) {
    $action = API_URL . '?module=remoteapi&key=' . API_KEY . '&action=getagentdata&param=' . $userlogin;
    @$result = file_get_contents($action);
    return ($result);
}

/**
 * Returns user's assigned agent extended data, if available
 *
 * @param $gentID
 *
 * @return array|empty
 */
function getUBAgentDataExten($agentID, $paysysName = '') {
    $result     = array();
    $whereStr   = (empty($paysysName) ? '' : ' and `internal_paysys_name` = "' . $paysysName . '"');

    if (!empty($agentID)) {
        $query       = 'select * from `contrahens_extinfo` where `agentid` = ' . $agentID . $whereStr;
        $queryResult = simple_queryall($query);

        $result = (empty($queryResult) ? array() : $queryResult);
    }

    return ($result);
}

/**
 * Checks is reference unique?
 *
 * @param int $rawhash reference number to check
 *
 * @return bool
 */
function po_CheckHash($rawhash) {
    $rawhash = mysql_real_escape_string($rawhash);
    $hash = 'PORT_' . $rawhash;
    $query = "SELECT * from `op_transactions` WHERE `hash`='" . $hash . "';";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
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

// ловим ответ о транзакции в виде POST XML
if (!empty($_REQUEST['data'])) {
    $xml = $_REQUEST['data'];
} else {
    die("Get POST xml: FAIL"); 
}

if (!empty($xml)) {
    //debug mode logging
    if (DEBUG_MODE) {
        //$debugSave = print_r($xml, true);
        file_put_contents('debug.log', $xml, FILE_APPEND | LOCK_EX);
    }

    //разбираем на куски пойманный XML
    $xml_arr = xml2array($xml);
    $allcustomers = op_CustomersGetAll();
    if (isset($xml_arr['REQUESTS'])) {
        $customerid = $xml_arr['REQUESTS']['PAYER']['CONTRACT_NUMBER']; 
        if (isset($allcustomers[$customerid])) {
            $customerLogin = $allcustomers[$customerid];
            
            $agentData = getAgentData($customerLogin);
            $agentData = json_decode($agentData);
            $agentID = $agentData->id;
            $agentDataExten   = getUBAgentDataExten($agentID, 'PORTMONE');
            if (!empty($agentDataExten) AND count($agentDataExten) == 1 ) {
                @$poInternalServiceId = $agentDataExten[0]['internal_paysys_srv_id'];
                if ($xml_arr['REQUESTS']['PAYEE'] == $poInternalServiceId) {
                    $poPaysysId = $agentDataExten[0]['internal_paysys_id'];

                    $userdata = po_UserGetStargazerData($customerLogin);
                    $allTariffs = po_TariffGetPricesAll();
                    $amount = $allTariffs[$userdata['Tariff']];
                    $userBalance = $userdata['Cash'] * -1;

                    $reply = '<?xml version="1.0" encoding="UTF-8"?>
                        <RESPONSE>
                                <BILLS>
                                        <PAYEE>' . $xml_arr['REQUESTS']['PAYEE'] . '</PAYEE>
                                        <BILL_PERIOD>' . date("my") . '</BILL_PERIOD>
                                        <BILL>
                                        <PAYEE>
                                        <CODE>' . $poPaysysId . '</CODE> 
                                        </PAYEE>
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
            }
        }
    } elseif (isset($xml_arr['BILLS'])) {
        $customerid = $xml_arr['BILLS']['BILL']['PAYER']['CONTRACT_NUMBER'];
        if (isset($allcustomers[$customerid])) {
            $customerLogin = $allcustomers[$customerid];
            
            $agentData = getAgentData($customerLogin);
            $agentData = json_decode($agentData);
            $agentID = $agentData->id;
            $agentDataExten   = getUBAgentDataExten($agentID, 'PORTMONE');
            if (!empty($agentDataExten) AND count($agentDataExten) == 1 ) {
                @$poPaysysId = $agentDataExten[0]['internal_paysys_id'];
                if ($xml_arr['BILLS']['BILL']['PAYEE']['CODE'] == @$poPaysysId) {
                    $poPaysysId = $agentDataExten[0]['internal_paysys_id'];
                    $summ = $xml_arr['BILLS']['BILL']['PAYED_AMOUNT'];
                    $rawhash = $xml_arr['BILLS']['BILL']['BILL_ID'];

                    if (po_CheckHash($rawhash)) {
                        //do the payment
                        $hash = 'PORT_' . $rawhash;
                        $paysys = 'PORTMONE' . $poPaysysId;
                        $note = 'inputreference: ' . $rawhash;
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
                                   <ERROR_CODE>0</ERROR_CODE>
                                   <REASON>PAYMENT_DUPLICATE</REASON>
                                </RESULT>
                    ';
                        die($reply);
                    }
                } else {
                    $reply = '<?xml version="1.0" encoding="UTF-8"?>
                            <RESULT>
                               <ERROR_CODE>4</ERROR_CODE>
                               <REASON>WRONG_PAYEE</REASON>
                            </RESULT>
                            ';
                    die($reply);
                }
            } else {
                    $reply = '<?xml version="1.0" encoding="UTF-8"?>
                            <RESULT>
                               <ERROR_CODE>15</ERROR_CODE>
                               <REASON>BILLING_NO_EXTENTED_PAYEE_INFO</REASON>
                            </RESULT>
                            ';
                    die($reply);
            }
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
        die('Input XML: FAIL | WRONG');
    }
} else {
    die('Input XML: FAIL | EMPTY');
}
?>