<?php

/**
 * Фронтенд для отримання повідомлень про платежі від Приватбанку
 * 
 * Можливе отримання запитів як у вигляді окремої змінної POST, так і у вигляді HTTP_RAW_POST_DATA
 */

/**
 * Секція налаштувань
 */

// Ім`я POST змінної в якій повинні надходити запити, або raw у разі отримання запитів в вигляді HTTP_RAW_POST_DATA.
define('PBX_REQUEST_MODE', 'raw');
// Режим відлагодження - змушує дані підвантажуватись з файлу debug.xml
// (Так-так, кладете туди запит і дивитесь у браузері як на нього відповідає фронтенд)
define('PBX_DEBUG_MODE', false);

// Тексти сповіщень та виключень
define('ISP_CODE', '1'); // Id в платіжній системі
define('ISP_SERVICE_NAME', 'Інтернет'); // Найменування послуги
define('ISP_SERVICE_CODE', '1'); // Код послуги
define('USER_BALANCE_DECIMALS', -1);    // Скільки знаків після коми повертати в балансі абонента 0 - повертати лише цілу частину
define('FULL_DEBTINFO', false); // Чи повертати секцію з DebtInfo включаючи amountToPay та debt?
// Виключення
define('PBX_EX_NOT_FOUND', 'Абонента не знайдено');
define('PBX_EX_DUPLICATE', 'Дублювання оплати');

/**
 * Кінець секції налаштувань, далі нічого не чіпаємо.
 */

error_reporting(E_ALL);
// Підключаємо API OpenPayz
include ("../../libs/api.openpayz.php");

// Трішечки заголовків
header('Last-Modified: ' . gmdate('r'));
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache");

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
 * String entity search
 *
 * @param $string - string variable to compare
 * @param $search - searched substring
 * @return bool
 */
function pbx_ispos($string, $search) {
    if (strpos($string, $search) === false) {
        return(false);
    } else {
        return(true);
    }
}

/**
 * Returns all user RealNames
 *
 * @return array
 */
function pbx_UserGetAllRealnames() {
    $query = "SELECT * from `realname`";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['realname'];
        }
    }
    return($result);
}

/**
 * Returns user stargazer data by login
 *
 * @param string $login existing stargazer login
 *
 * @return array
 */
function pbx_UserGetStargazerData($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `users` WHERE `login`='" . $login . "';";
    $result = simple_query($query);
    return ($result);
}

/**
 * Returns all user mobile phones
 *
 * @return array
 */
function pbx_UserGetAllMobiles() {
    $query = "SELECT * from `phones`";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['mobile'];
        }
    }
    return($result);
}

/**
 * Returns all tariff prices array
 *
 * @return array
 */
function pbx_TariffGetPricesAll() {
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
        $string .= $characters[mt_rand(0, (strlen($characters) - 1))];
    }

    return ($string);
}

/**
 * Returns array of availble user address as login=>address
 * 
 * @return array
 */
function pbx_AddressGetFulladdresslist() {
    $alterconf['ZERO_TOLERANCE'] = 0;
    $alterconf['CITY_DISPLAY'] = 0;
    $result = array();
    $query_full = "
        SELECT `address`.`login`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` FROM `address`
        INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id`
        INNER JOIN `build` ON `apt`.`buildid`=`build`.`id`
        INNER JOIN `street` ON `build`.`streetid`=`street`.`id`
        INNER JOIN `city` ON `street`.`cityid`=`city`.`id`";
    $full_adress = simple_queryall($query_full);
    if (!empty($full_adress)) {
        foreach ($full_adress as $ArrayData) {
            // zero apt handle
            if ($alterconf['ZERO_TOLERANCE']) {
                $apartment_filtered = ($ArrayData['apt'] == 0) ? '' : '/' . $ArrayData['apt'];
            } else {
                $apartment_filtered = '/' . $ArrayData['apt'];
            }
            if ($alterconf['CITY_DISPLAY']) {
                $result[$ArrayData['login']] = $ArrayData['cityname'] . ' ' . $ArrayData['streetname'] . ' ' . $ArrayData['buildnum'] . $apartment_filtered;
            } else {
                $result[$ArrayData['login']] = $ArrayData['streetname'] . ' ' . $ArrayData['buildnum'] . $apartment_filtered;
            }
        }
    }
    return($result);
}

/**
 * Returns presearch reply
 *
 * @return string
 */
function pbx_ReplyPresearch($customerid) {
    $allcustomers = op_CustomersGetAll();

    if (isset($allcustomers[$customerid])) {
        $customerLogin = $allcustomers[$customerid];
        $allrealnames = pbx_UserGetAllRealnames();

        //normal search reply
        $templateOk = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Presearch">
                <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="PayersTable">
                <Headers>
                <Header name="fio"/>
                <Header name="ls"/>
                </Headers>
                <Columns>
                <Column>
                 <Element>' . @$allrealnames[$customerLogin] . '</Element>
                </Column>
                <Column>
                 <Element>' . $customerid . '</Element>
                </Column>
                </Columns>
                </Data>
                </Transfer>';
        $result = $templateOk;
    } else {
        //search fail reply template
        $templateFail = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Presearch">
                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="ErrorInfo" code="99">
                     <Message>' . PBX_EX_NOT_FOUND . '</Message>
                    </Data>
                    </Transfer>';
        $result = $templateFail;
    }
    $result = trim($result);
    return ($result);
}

/**
 * Returns search reply
 *
 * @return string
 */
function pbx_ReplySearch($customerid, $UsrBalanceDecimals = -1) {
    $allcustomers = op_CustomersGetAll();
    if (isset($allcustomers[$customerid])) {
        $customerLogin = $allcustomers[$customerid];
        $allrealnames = pbx_UserGetAllRealnames();
        $alladdress = pbx_AddressGetFulladdresslist();
        $allmobiles = pbx_UserGetAllMobiles();
        $userdata = pbx_UserGetStargazerData($customerLogin);
        $userBalance = ($UsrBalanceDecimals < 0) ? $userdata['Cash'] : (($UsrBalanceDecimals == 0) ? intval($userdata['Cash'], 10) : round($userdata['Cash'], $UsrBalanceDecimals, PHP_ROUND_HALF_EVEN));

        if (FULL_DEBTINFO) {
            $recommendedPay = '0.0';
            $debt = '0.0';

            if ($userdata['Cash'] < 0) {
                $recommendedPay = abs($userdata['Cash']);
                $debt = $userdata['Cash'];
            } else {
                $allTariffs = pbx_TariffGetPricesAll();
                $recommendedPay = $allTariffs[$userdata['Tariff']];
                $debt = '-' . $userdata['Cash'];
            }
            $debtInfoSection = '<DebtInfo amounttopay="' . $recommendedPay . '" debt="' . $debt . '">  
                                   <Balance>' . $userBalance . '</Balance>
                        </DebtInfo>';
        } else {
            $debtInfoSection = '<DebtInfo>
                                  <Balance>' . $userBalance . '</Balance>
                        </DebtInfo>';
        }


        //normal reply
        $templateOk = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <Transfer xmlns="http://debt.privatbank.ua/Transfer" interface="Debt" action="Search">
                    <Data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="DebtPack" billPeriod="' . date("Ym") . '">
                    <PayerInfo billIdentifier="' . $customerid . '">
                     <Fio>' . @$allrealnames[$customerLogin] . '</Fio>
                     <Phone>' . @$allmobiles[$customerLogin] . '</Phone>
                     <Address>' . @$alladdress[$customerLogin] . '</Address>
                    </PayerInfo>
                    <ServiceGroup>
                     <DebtService  serviceCode="' . ISP_SERVICE_CODE . '" >
                        <CompanyInfo>
                         <CompanyCode>' . ISP_CODE . '</CompanyCode>
                        </CompanyInfo>
                       ' . $debtInfoSection . '
                       <ServiceName>' . ISP_SERVICE_NAME . '</ServiceName>
                       <PayerInfo billIdentifier="' . $customerid . '" ls="' . $customerid . '">
                         <Fio>' . @$allrealnames[$customerLogin] . '</Fio>
                         <Phone>' . @$allmobiles[$customerLogin] . '</Phone>
                         <Address>' . @$alladdress[$customerLogin] . '</Address>
                        </PayerInfo>
                    </DebtService>
                    </ServiceGroup>
                    </Data>
                    </Transfer>
                    ';
        $result = $templateOk;
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
    $hash = 'PBX_' . $rawhash;
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
function pbx_ReplyPayment($customerid, $summ, $rawhash) {
    $allcustomers = op_CustomersGetAll();
    if (isset($allcustomers[$customerid])) {
        if (pbx_CheckHash($rawhash)) {
            //do the payment
            $hash = 'PBX_' . $rawhash;
            $paysys = 'PBANKX';
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
if (!PBX_DEBUG_MODE) {
    $xmlRequest = pbx_RequestGet();
} else {
    if (file_exists('debug.xml')) {
        $xmlRequest = file_get_contents('debug.xml');
    } else {
        die('PBX_DEBUG_MODE requires existing debug.xml file');
    }
}

//raw xml data received
if (!empty($xmlRequest)) {
    $xmlParse = xml2array($xmlRequest);
    if (!empty($xmlParse)) {


        // Presearch action handling (deprecated?)
        if (isset($xmlParse['Transfer']['Data']['Unit_attr']['name'])) {
            if ($xmlParse['Transfer']['Data']['Unit_attr']['name'] == 'ls') {
                if (isset($xmlParse['Transfer']['Data']['Unit_attr']['value'])) {
                    $customerid = vf($xmlParse['Transfer']['Data']['Unit_attr']['value'], 3);
                    die(pbx_ReplyPresearch($customerid));
                }
            }
        }

        // Main search
        if (isset($xmlParse['Transfer']['Data']['Unit_attr']['name'])) {
            if ($xmlParse['Transfer']['Data']['Unit_attr']['name'] == 'bill_identifier') {
                if (isset($xmlParse['Transfer']['Data']['Unit_attr']['value'])) {
                    if ($xmlParse['Transfer_attr']['action'] == 'Search') {
                        $customerid = vf($xmlParse['Transfer']['Data']['Unit_attr']['value'], 3);
                        die(pbx_ReplySearch($customerid, USER_BALANCE_DECIMALS));
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

                    die(pbx_ReplyPayment($customerid, $summ, $rawhash));
                }
            }
        }
    } else {
        die('XML_PARSER_FAIL');
    }
}

//
//                         _._._                       _._._
//                        _|   |_                     _|   |_
//                        | ... |_._._._._._._._._._._| ... |
//                        | ||| |   o ПРИВАТБАНК o    | ||| |
//                        | """ |  """    """    """  | """ |
//                   ())  |[-|-]| [-|-]  [-|-]  [-|-] |[-|-]|  ())
//                  (())) |     |---------------------|     | (()))
//                 (())())| """ |  """    """    """  | """ |(())())
//                 (()))()|[-|-]|  :::   .-"-.   :::  |[-|-]|(()))()
//                 ()))(()|     | |~|~|  |_|_|  |~|~| |     |()))(()
//                    ||  |_____|_|_|_|__|_|_|__|_|_|_|_____|  ||
//                 ~ ~^^ @@@@@@@@@@@@@@/=======\@@@@@@@@@@@@@@ ^^~ ~
//                      ^~^~                                ~^~^