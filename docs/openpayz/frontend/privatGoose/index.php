<?php

/*
 * Фронтенд для отримання увідомлень про платежі від Приватбанку
 * Протокол: https://docs.google.com/document/d/1JrH84x2p4FOjm89q3xArvnEfsFXRnbIoa6qJFNq2VYw/edit#
 *
 * Можливе отримання запитів як у вигляді окремої змінної POST, так і у вигляді HTTP_RAW_POST_DATA
 * Ідентифікація абонента за особовим рахунком у вигляді paymentID матеріалізованого з в'юшки виду:
 * CREATE VIEW op_customers (realid,virtualid) AS SELECT users.login, CRC32(users.login) FROM `users`;
 * 
 * Модуль написаний для логіки модуля "Опір Гусака".
 * Вам необхіжно в вашому білінгу прописати в "Додаткова інформації" "Господорюючого суб'єкта" "Ім`я платіжної системи" у вигляді префіксу "PBANKM_".
 * для кожного "Господорюючого суб'єкта" "Ім`я платіжної системи" повинно бути унікальним, наприклад: "PBANKM_1" для іншого "Господорюючого суб'єкта" "PBANKM_UBILL" і т.д.
 * Також для Приват24 обов'зково э параметри "Код контрагента в платіжній системі" та "Код сервісу в платіжній системі" - ці параметри ви в обов'зковому порядку погоджуєте з Приват24
 * "Код сервісу в платіжній системі" - це код, за яким платіжне система розуміє, за шо ви платите.
 * "Код контрагента в платіжній системі" - це унікальний код вашого контрагента в платіжній системі.
 * Також в модулі "Опір Гусака" необхідно прописати користувацький парамертр "serviceDescripton" для кожного агента та в кожній “стратегії”. Цей параметр може повторюватись.
 * він на квитанціях та платіжній системі показує, за що ви платите
 * Також приват мусить у запиті на Pay в розділі UnitCode надавати companyCode, який ви з ним погодили, так який у вас повинен бути введений в розширеній інформації контрагента
*/

/////////// Секция настроек
// Имя POST переменной в которой должны приходить запросы, либо raw в случае получения 
// запросов в виде HTTP_RAW_POST_DATA.
define('PBX_REQUEST_MODE', 'raw');

//Текст уведомлений и екзепшнов
define('USER_BALANCE_DECIMALS', 2);    // Сколько знаков после запятой возвращать в балансе абонента 0 - возвращать только целую часть

//Исключения
define('PBX_EX_NOT_FOUND', 'Абонента не знайдено');
define('PBX_EX_DUPLICATE', 'Дублювання платежу');
define('PBX_AGENT_NOT_FOUND', 'Критична помилка. Не знайдено агента, якому належить абонент');
define('PBX_GOOSE_NOT_FOUND', 'Критична помилка. Опір Гусака не зміг знайти інформацію по абоненту');
define('PBX_GOOSE_AGENT_NOT_FOUND', 'Критична помилка. Для стратегії не знайдено агентів');
define('PBX_AGENT_EXT_INFO_NOT_FOUND', 'Критична помилка. Не знайдено розширеної інформації для агентів');

//URL вашего работающего Ubilling
define('API_URL', 'http://localhost/billing/');
//И его серийный номер
define('API_KEY', 'UBxxxxxxxxxx');
// Префікс платіжної системи в білінгу для роширеної інформації по агентам
define('PAYSYS_PREFIX', 'PBANKM' . '_');

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

error_reporting(E_ALL);

// Send main headers
header('Last-Modified: ' . gmdate('r'));
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache");

/**
* Returns user's assigned agent extended data, if available
*
* @param $gentID
*
* @return array|empty
*/
function getGoosData($customerId, $amountRaw = '') {
    $baseUrl = API_URL . '?module=remoteapi&key=' . API_KEY . '&action=goose';
    $callbackUrl = $baseUrl . '&amount=' . $amountRaw . '&paymentid=' . $customerId;
    $gooseResult = @file_get_contents($callbackUrl);
    
    return ($gooseResult);
}

/**
 * Check for POST have needed variables
 *
 * @param   $params array of POST variables to check
 * @return  bool
 *
 */
function pbx_CheckPost($params) {
    $result = true;
    if (!empty($params)) {
        foreach ($params as $eachparam) {
            if (isset($_POST[$eachparam])) {
                if (empty($_POST[$eachparam])) {
                    $result = false;
                }
            } else {
                $result = false;
            }
        }
    }
    return ($result);
}

/**
 * Returns request data
 *
 * @return string
 */
function pbx_RequestGet() {
    $result = '';
    if (PBX_REQUEST_MODE != 'raw') {
        if (pbx_CheckPost(array(PBX_REQUEST_MODE))) {
            $result = $_POST[PBX_REQUEST_MODE];
        }
    } else {
        //$result = $HTTP_RAW_POST_DATA;
        $result = file_get_contents('php://input');
    }
    return ($result);
}

/**
 * Returns random numeric string, which will be used as unique transaction hash
 *
 * @param int $size
 * @return int
 */
function pbx_GenerateHash($size = 12) {
    $characters = '0123456789';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string.= $characters[mt_rand(0, (strlen($characters) - 1))];
    }

    return ($string);
}

/**
 * Returns search reply
 *
 * @return string
 */
function pbx_ReplySearch($customerid, $userEnterAmount = 0) {
    $allcustomers = op_CustomersGetAll();
    if (isset($allcustomers[$customerid])) {
        $customerLogin = $allcustomers[$customerid];
        $gooseResult = getGoosData($customerLogin,  $userEnterAmount);            
        if (!empty($gooseResult)) {
           $gooseResult = json_decode($gooseResult);
           if (!empty($gooseResult->agents)) {
               if (!empty($gooseResult->agentsextinfo)) {
                   $agentsExtInfo = preg_grep("/^" . PAYSYS_PREFIX . ".+/", array_column((array)$gooseResult->agentsextinfo, 'internal_paysys_name', 'id'));
                    // Перевіряємо чи заповнена розширена інформація по агенту. Бо для взаємодією с приват необхідні додаткові параметри
                   if (!empty($agentsExtInfo)) {          
                        //normal reply
                        $template = '
                                <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                                    <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Search">
                                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="DebtPack" billPeriod="' . date("Ym") . '">
                                    <PayerInfo billIdentifier="' . $gooseResult->user->paymentid . '">
                                         <Fio>' . @$gooseResult->user->realname . '</Fio>
                                         <Phone>' . @$gooseResult->user->mobile . '</Phone>
                                         <Address>' . @$gooseResult->user->fulladress . '</Address>
                                    </PayerInfo>
                                    ';        
                        $template.= '<ServiceGroup>';
                        foreach ($agentsExtInfo as $id => $paysysPrefix) {
                            $agentId = $gooseResult->agentsextinfo->{$id}->agentid;
                            $userBalance = (USER_BALANCE_DECIMALS < 0) 
                                            ? $gooseResult->user->Cash * $gooseResult->agents->{$agentId}->splitvalue / 100
                                            : ((USER_BALANCE_DECIMALS == 0) 
                                            ? intval($gooseResult->user->Cash * $gooseResult->agents->{$agentId}->splitvalue / 100, 10) 
                                            : round($gooseResult->user->Cash * $gooseResult->agents->{$agentId}->splitvalue / 100, USER_BALANCE_DECIMALS, PHP_ROUND_HALF_EVEN));
                            $template.= '
                                    <DebtService serviceCode="' . $gooseResult->agents->{$agentId}->extinfo->{$id}->internal_paysys_srv_id . '">
                                          <CompanyInfo mfo="' . $gooseResult->agents->{$agentId}->bankcode . '" okpo="' . $gooseResult->agents->{$agentId}->edrpo . '" account="' . $gooseResult->agents->{$agentId}->bankacc . '" >
                                           <CompanyCode>' . $gooseResult->agents->{$agentId}->extinfo->{$id}->internal_paysys_id . '</CompanyCode>
                                           <CompanyName>' . $gooseResult->agents->{$agentId}->contrname . '</CompanyName>
                                         </CompanyInfo>
                                    <DebtInfo amountToPay="' . $gooseResult->agents->{$agentId}->splitamount . '">
                                        <Balance>' . $userBalance . '</Balance>
                                    </DebtInfo>
                                   <ServiceName>' . $gooseResult->agents->{$agentId}->customdata->serviceDescripton . '</ServiceName>
                                   <PayerInfo billIdentifier="' . $gooseResult->user->paymentid . '" ls="' . $gooseResult->user->paymentid . '">
                                     <Fio>' . @$gooseResult->user->realname . '</Fio>
                                     <Phone>' . @$gooseResult->user->mobile . '</Phone>
                                     <Address>' . @$gooseResult->user->fulladress . '</Address>
                                    </PayerInfo>
                                    </DebtService>
                                    ';
                        }
                        $template.= '</ServiceGroup>';
    
                        $template.= '
                            </Data>
                        </Transfer>
                        ';
                   } else {
                       $template = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                       <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                           <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="8">
                               <Message>' . PBX_AGENT_EXT_INFO_NOT_FOUND . '</Message>
                           </Data>
                       </Transfer>';
                   }
               } else {
                   $template = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                   <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                       <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="8">
                           <Message>' . PBX_AGENT_EXT_INFO_NOT_FOUND . '</Message>
                       </Data>
                   </Transfer>';   
               }
           } else {
               $template = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
               <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                   <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="8">
                       <Message>' . PBX_GOOSE_AGENT_NOT_FOUND . '</Message>
                   </Data>
               </Transfer>';
           }
            $result = $template;
        } else {
                $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                                <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="8">
                                        <Message>' . PBX_GOOSE_NOT_FOUND . '</Message>
                                    </Data>
                                </Transfer>';
                $result = $templateFail;
        }
    } else {
        //reply fail
        $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Search">
                        <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="2">
                        <Message>' . PBX_EX_NOT_FOUND . '</Message>
                        </Data>
                        </Transfer>';
        $result = $templateFail;
    }
    $result = trim($result);
    return ($result);
}

/**
 * Function that gets last id from table
 *
 * @param string $tablename
 * @return int
 */
function pbx_simple_get_lastid($tablename) {
    $tablename = mysql_real_escape_string($tablename);
    $query = "SELECT `id` from `" . $tablename . "` ORDER BY `id` DESC LIMIT 1";
    $result = simple_query($query);
    return ($result['id']);
}

/**
 * Returns payment possibility reply
 *
 * @return string
 */
function pbx_ReplyCheck($customerid) {
    $allcustomers = op_CustomersGetAll();
    if (isset($allcustomers[$customerid])) {
        $customerLogin = $allcustomers[$customerid];
        $reference = pbx_GenerateHash();
        // following method may cause reference ID collisions
        // $reference = pbx_simple_get_lastid('op_transactions') + 1;

        $templateOk = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Check">
                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="Gateway" reference="' . $reference . '" />
                    </Transfer>
                    ';
        $result = $templateOk;
    } else {
        $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Check">
                        <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="2">
                        <Message>' . PBX_EX_NOT_FOUND . '</Message>
                        </Data>
                        </Transfer>
                        ';
        $result = $templateFail;
    }
    $result = trim($result);
    return ($result);
}

/**
 * Checks is reference unique?
 *
 * @param int $rawhash reference number to check
 *
 * @return bool
 */
function pbx_CheckHash($rawhash) {
    $rawhash = mysql_real_escape_string($rawhash);
    $hash = PAYSYS_PREFIX . $rawhash;
    $query = "SELECT * from `op_transactions` WHERE `hash`='" . $hash . "';";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Returns payment processing reply
 *
 * @return string
 */
function pbx_ReplyPayment($customerid, $summ, $rawhash, $serviceCode) {
    $allcustomers = op_CustomersGetAll();
    if (isset($allcustomers[$customerid])) {
        if (pbx_CheckHash($rawhash)) {
            $gooseResult = getGoosData($customerid);
            if (!empty($gooseResult)) {
                $gooseResult = json_decode($gooseResult);
                $paysys = preg_grep("/^" . PAYSYS_PREFIX . ".+/", array_column((array)$gooseResult->agentsextinfo, 'internal_paysys_name', 'internal_paysys_id'));
                if (isset($paysys[$serviceCode]) and ! empty($paysys[$serviceCode])) {
                    //do the payment
                    $hash = PAYSYS_PREFIX . $rawhash;
                    $paysys = $paysys[$serviceCode];
                    $note = 'inputreference: ' . $rawhash;
                    op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                    op_ProcessHandlers();
                    $templateOk = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                            <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                             <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="Gateway" reference="' . $rawhash . '">
                            </Data>
                            </Transfer>';
                    $result = $templateOk;
                } else {
                    $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="8">
                    <Message>' . PBX_AGENT_EXT_INFO_NOT_FOUND . '</Message>
                    </Data>
                    </Transfer>';
                    $result = $templateFail;
                }
            } else {
                $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                                <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                                <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="8">
                                <Message>' . PBX_AGENT_NOT_FOUND . '</Message>
                                </Data>
                                </Transfer>';
                $result = $templateFail;
            }
        } else {
            $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                        <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="7">
                        <Message>' . PBX_EX_DUPLICATE . '</Message>
                        </Data>
                        </Transfer>';
            $result = $templateFail;
        }
    } else {
        $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Pay">
                        <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="2">
                        <Message>' . PBX_EX_NOT_FOUND . '</Message>
                        </Data>
                        </Transfer>';
        $result = $templateFail;
    }

    $result = trim($result);
    return ($result);
}

/*
 *  Controller part
 */

//raw xml data received
if (!empty($xmlRequest)) {
    $xmlParse = xml2array($xmlRequest);
    if (!empty($xmlParse)) {

        // Main search
        if (isset($xmlParse['Transfer']['Data']['Unit_attr']['name'])) {
            if ($xmlParse['Transfer']['Data']['Unit_attr']['name'] == 'bill_identifier') {
                if (isset($xmlParse['Transfer']['Data']['Unit_attr']['value'])) {
                    if ($xmlParse['Transfer_attr']['action'] == 'Search') {
                        $customerid = vf($xmlParse['Transfer']['Data']['Unit_attr']['value'], 3);
                        die(pbx_ReplySearch($customerid));
                    }
                }
            }
        }

        // Main search with custom summ from user
        if (isset($xmlParse['Transfer']['Data']['Unit']['0_attr']['name'])) {
            if ($xmlParse['Transfer']['Data']['Unit']['0_attr']['name'] == 'bill_identifier') {
                if (isset($xmlParse['Transfer']['Data']['Unit']['0_attr']['value'])) {
                    if ($xmlParse['Transfer_attr']['action'] == 'Search') {
                        $userEnterAmount = 0; 
                        $customerid = vf($xmlParse['Transfer']['Data']['Unit']['0_attr']['value'], 3);
                        if (isset($xmlParse['Transfer']['Data']['Unit']['1_attr']['value'])) {
                            if ($xmlParse['Transfer']['Data']['Unit']['1_attr']['name'] == 'summ') {
                                $userEnterAmount = $xmlParse['Transfer']['Data']['Unit']['1_attr']['value'];
                            }
                        }
                        die(pbx_ReplySearch($customerid, $userEnterAmount));
                    }
                }
            }
        }
        
        // Check payment possibility
        if (isset($xmlParse['Transfer_attr']['action'])) {
            if ($xmlParse['Transfer_attr']['action'] == 'Check') {
                if (isset($xmlParse['Transfer']['Data']['PayerInfo_attr']['billIdentifier'])) {
                    $customerid = vf($xmlParse['Transfer']['Data']['PayerInfo_attr']['billIdentifier'], 3);
                    die(pbx_ReplyCheck($customerid));
                }
            }
        }
        // Pay transaction handling
        if (isset($xmlParse['Transfer_attr']['action'])) {
            if ($xmlParse['Transfer_attr']['action'] == 'Pay') {
                if (isset($xmlParse['Transfer']['Data']['PayerInfo_attr']['billIdentifier'])) {
                    $customerid = vf($xmlParse['Transfer']['Data']['PayerInfo_attr']['billIdentifier'], 3);
                    $summ = $xmlParse['Transfer']['Data']['TotalSum'];
                    $summ = str_replace(',', '.', $summ);
                    $rawhash = $xmlParse['Transfer']['Data']['CompanyInfo']['CheckReference'];
                    $serviceCode = vf($xmlParse['Transfer']['Data']['CompanyInfo']['UnitCode'], 3);
                    die(pbx_ReplyPayment($customerid, $summ, $rawhash, $serviceCode));
                }
            }
        }
    } else {
        die('XML_PARSER_FAIL');
    }
}
?>
