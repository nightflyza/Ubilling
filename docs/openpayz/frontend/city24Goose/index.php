<?php

/*
 * Фронтенд платежной системы city24.com.ua за основу взят фронтенд platezhka, проверять можно по адресу http://demo.platezhka.com.ua:8081/
 * согласно протокола: http://store.nightfly.biz/st/1430694725/platezhkacomua_interface.rtf
 * 
 * Модуль написаний для логіки модуля "Опір Гусака".
 * Вам необхіжно в вашому білінгу прописати в "Додаткова інформації" "Господорюючого суб'єкта" "Ім`я платіжної системи" у вигляді префіксу "CITY24M_".
 * для кожного "Господорюючого суб'єкта" "Ім`я платіжної системи" повинно бути унікальним, наприклад: "CITY24M_1" для іншого "Господорюючого суб'єкта" "CITY24M_UBILL" і т.д.
 * Також в модулі "Опір Гусака" необхідно прописати користувацький парамертр "serviceDescripton" для кожного агента та в кожній “стратегії”. Цей параметр може повторюватись.
 * він на квитанціях та платіжній системі показує, за що ви платите
 */

// Configuration section
define('DEBUG_MODE', 0);

//Секция конфигурации

$checkMode = true; // включена ли дополнительная авторизация по логину/паролю?
$checkLogin = 'login'; // собственно логин и пароль для доп. авторизации.
$checkPassword = 'password';

//URL вашего работающего Ubilling
define('API_URL', 'http://127.0.0.1/billing/');
//И его серийный номер
define('API_KEY', 'YOUR_API_UBILLING_KEY');
define('SERVICE_DESCRIPTION', 'необхідно уточнити сервіс');
define('PAYSYS_PREFIX', 'CITY24M' . '_');

error_reporting(E_ALL);

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

/**
 * Дополнительная авторизация по логину/паролю. Может быть отключена флагом $checkMode
 * 
 * @global boolean $checkMode
 * @global string $checkLogin
 * @global string $checkPassword
 * @param string $login
 * @param string $password
 * @return bool
 */
function pltz_AuthLogin($login, $password) {
    global $checkMode, $checkLogin, $checkPassword;
    if ($checkMode) {
        if (($login == $checkLogin) AND ( $password == $checkPassword)) {
            $result = true;
        } else {
            $result = false;
        }
    } else {
        $result = true;
    }
    return ($result);
}

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

/*
 * Returns all tariff prices array
 * 
 * @return array
 */

function cpay_TariffGetPricesAll() {
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
 * Checks is reference unique?
 *
 * @param int $rawhash reference number to check
 *
 * @return bool
 */
function city24_CheckHash($rawhash) {
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
 * По идее это обертка для ответов на запросы
 * 
 * @param string $response
 * 
 * @return void
 */
function pltz_sendResponse($response) {
    print("<?xml version=\"1.0\" encoding=\"UTF-8\"?><commandResponse>$response</commandResponse>");
}

/**
 * Эмулируем ихнюю доставалку по тегу, с заточем на нативный парсер.
 * 
 * @param array $array
 * @param string $tag
 * @return string
 */
function pltz_getValueByTag($array, $tag) {
    $result = '';
    if (isset($array['commandCall'])) {
        if (isset($array['commandCall'][$tag])) {
            $result = $array['commandCall'][$tag];
        }
    }
    return ($result);
}

/**
 * А это, такой типа диспатчер экшонов.
 * 
 * @param string $inXmlset
 * @return void
 */
function pltz_parseXML($inXmlset) {
    $xmlArr = xml2array($inXmlset);
    if (!empty($xmlArr)) {
        // print_r($xmlArr);
        foreach ($xmlArr as $i) {
            $command = pltz_getValueByTag($xmlArr, 'command');
            switch ($command) {//Разбираем, какая команда поступила. 
                case 'check' ://Валидация 
                    pltz_Check(pltz_getValueByTag($xmlArr, 'login'), 
                                pltz_getValueByTag($xmlArr, 'password'), 
                                pltz_getValueByTag($xmlArr, 'payElementID'), 
                                pltz_getValueByTag($xmlArr, 'transactionID'), 
                                pltz_getValueByTag($xmlArr, 'account'),  
                                pltz_getValueByTag($xmlArr, 'userEnterAmount')
                                );
                    return;
                case 'pay'://Оплата 
                    pltz_Payment(pltz_getValueByTag($xmlArr, 'login'), 
                                 pltz_getValueByTag($xmlArr, 'password'), 
                                 pltz_getValueByTag($xmlArr, 'transactionID'), 
                                 pltz_getValueByTag($xmlArr, 'payTimestamp'), 
                                 pltz_getValueByTag($xmlArr, 'payID'), 
                                 pltz_getValueByTag($xmlArr, 'payElementID'), 
                                 pltz_getValueByTag($xmlArr, 'account'), 
                                 pltz_getValueByTag($xmlArr, 'amount'), 
                                 pltz_getValueByTag($xmlArr, 'terminalId'), 
                                 pltz_getValueByTag($xmlArr, 'ProductId')
                                );
                    return;
                case 'cancel'://Отмена платежа 
                    pltz_Cancel(pltz_getValueByTag($xmlArr, 'login'), 
                                pltz_getValueByTag($xmlArr, 'password'), 
                                pltz_getValueByTag($xmlArr, 'transactionID'), 
                                pltz_getValueByTag($xmlArr, 'cancelPayID'), 
                                pltz_getValueByTag($xmlArr, 'payElementID'), 
                                pltz_getValueByTag($xmlArr, 'account'), 
                                pltz_getValueByTag($xmlArr, 'amount')
                                );
                    return;
            }
        }
    }
}

function pltz_Check($login, $password, $payElementID, $transactionID, $account, $userEnterAmount = 0) {
    $companyData = '';
    $result = 0; //Поле кода завершения (см. Приложение А. Список кодов завершения)
    $comment = ''; //Необязательном поле,  служебный комментарий.
    //Здесь записываем в базу поступивший запрос, для того что бы потом разобраться какие запросы к Вам приходили. Уникальный индификатор запроса - $transactionID
    $extTransactionID = PAYSYS_PREFIX . $transactionID; //Записываем сюда номер Вашей транзакции.

    if (pltz_AuthLogin($login, $password)) { //Проверяем $login, $password, что бы отсекать чужие запросы
        $allcustomers = op_CustomersGetAll();
        $tariffPrice = cpay_TariffGetPricesAll();
        
            if (isset($allcustomers[$account])) { //Проверяем в БД абонента (по $account)
                //Здесь нужно сохранить платеж в базу, со статусом не оплачен
                $comment = 'Очікування платежу'; //Коментарий не обязателен
                $userlogin = $allcustomers[$account];
                $gooseResult = getGoosData($userlogin,  $userEnterAmount);
                
                    if (!empty($gooseResult)) {
                        $gooseResult = json_decode($gooseResult);
                        $companyData = '
                        <fields>
                            <field1 name="FIO">' . $gooseResult->user->realname . '</field1>
                            <field2 name="Balance">' . round($gooseResult->user->Cash, 2) . '</field2>
                        </fields>';
                        if (!empty($gooseResult->agents)) {
                            $companyData.= '<Products>';
                            foreach ($gooseResult->agents as $id => $agentOblects) {
                                // Первіряємо чи увів суму клієнт на термігалі, якщо не ввів то розділяємо платіж відповідно до його тарифу.
                                if (!empty($gooseResult->payopts->amount)) {
                                    $sum = round($agentOblects->splitamount);
                                    $sumAmountMin = round($sum)-4;
                                    $sumAmountMax = round($sum)+4;
                                } else {
                                    $sum = $agentOblects->splitvalue * $tariffPrice[$gooseResult->user->Tariff]; // Сума в копійках
                                    $sumAmountMin = round($sum)-4;
                                    $sumAmountMax = round($sum)+4;
                                }
                                // Вказуэмо дефолтний Service Descripton
                                if (!empty($agentOblects->customdata->serviceDescripton)) {
                                    $companyData.= '
                                    <Product>
                                        <ProductId>' . $agentOblects->id . '</ProductId>
                                        <ProductName>' . $agentOblects->customdata->serviceDescripton . '</ProductName>
                                        <Amount>' . $sum . '</Amount>
                                        <AmountMin>' . $sumAmountMin . '</AmountMin>
                                        <AmountMax>' . $sumAmountMax . '</AmountMax>
                                        <SubProviderId>' . $agentOblects->bankacc . '</SubProviderId>
                                        <IsRequired>1</IsRequired>
                                    </Product>
                                    ';
                                }
                            }
                          $companyData.= '</Products>';
                        } else {
                            $result = 7; //Прием платежа запрещен провайдером
                            $comment = 'Не знайдено агентів'; //Коментарий не обязателен
                        }
                    } else {
                        die('ERROR:WRONG_API_CONNECTION');
                    }
            } else {
                $result = 5; //Идентификатор абонента не найден (Ошиблись номером). Здесь может быть другая ошибка, например 79 (Счет абонента не активен) 
                $comment = 'Ідентифікатор абонента не знайдено'; //Коментарий не обязателен
            }
    } else {
        $result = 7; //Прием платежа запрещен провайдером
        $comment = 'Невірний логін або пароль'; //Коментарий не обязателен
    }

    pltz_sendResponse('
    <extTransactionID>' . $extTransactionID . '</extTransactionID>
     <account>' . $account . '</account>
     <result>' . $result . '</result>
     ' . $companyData . '
     <comment>' . $comment . '</comment>'
     );
}

function pltz_Payment($login, $password, $transactionID, $payTimestamp, $payID, $payElementID, $account, $amount, $terminalId, $ProductId = '') {
    $extTransactionID = 0; //Платеж в системе Вашей системе
    $result = 0; //Поле кода завершения (см. Приложение А. Список кодов завершения)
    $comment = ''; //Необязательном поле,  служебный комментарий.
    //Здесь записываем в базу поступивший запрос, для того что бы потом разобраться какие запросы к Вам приходили. Уникальный индификатор запроса - $transactionID

    if (pltz_AuthLogin($login, $password)) {//Проверяем $login, $password, что бы отсекать чужие запросы
        if (!empty($ProductId)) {
            $extTransactionID = PAYSYS_PREFIX . $transactionID; //Записываем сюда номер Вашей транзакции
            if (city24_CheckHash($transactionID)) { //Обязательно нужно проверить(по $transactionID) платеж в Вашей системе, если платеж оплачен -  возвращаем result - 0
                $hash = $extTransactionID;
                $summ = $amount / 100; // кошти в копійках
                $customerid = $account;

                $gooseResult = getGoosData($customerid);
                if (!empty($gooseResult)) {
                    $gooseResult = json_decode($gooseResult);
                    if (!empty($gooseResult->agents)) {
                        if (!empty($gooseResult->agents->{$ProductId}->extinfo)) {
                            $paysys = preg_grep("/^" . PAYSYS_PREFIX . ".+/", array_column((array)$gooseResult->agents->{$ProductId}->extinfo, 'internal_paysys_name', 'id'));
                            if (!empty($paysys)) {
                                $paysys = current($paysys);
                                $note = 'transactionID:' . $payID . ' terminalId:' . $terminalId;
            
                                //регистрируем новую транзакцию
                                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                                //вызываем обработчики необработанных транзакций
                                op_ProcessHandlers();
            
                                $result = 0; //ОК
                                $comment = 'Платіж виконано'; //Коментарий не обязателен
                            } else {
                                $result = 8; //ОК
                                $comment = 'Не знайдено додаткової інформації по префіксу агента: ' . PAYSYS_PREFIX; //Коментарий не обязателен
                            }
                        } else {
                            $result = 8; //ОК
                            $comment = 'Не знайдено опис агента'; //Коментарий не обязателен
                        }
                    } else {
                        $result = 8; //ОК
                        $comment = 'Не знайдено агентів'; //Коментарий не обязателен
                    }
                } else {
                    $result = 8; //ОК
                    $comment = 'В білінгу не знайдено контрагента'; //Коментарий не обязателен
                }
            } else {
                $result = 8; //ОК
                $comment = 'Дублікат платежу'; //Коментарий не обязателен
            }
        } else {
            $result = 8; //ОК
            $comment = 'Платіжна система не передала ProductId'; //Коментарий не обязателе
        }
    } else {
        $result = 7; //Прием платежа запрещен провайдером
    }

    pltz_sendResponse("<extTransactionID>$extTransactionID</extTransactionID>
      <account>$account</account>
     <result>$result</result>
     <comment>$comment</comment>");
}

function pltz_Cancel($login, $password, $transactionID, $cancelPayID, $payElementID, $account, $amount) {
    $extTransactionID = 0; //Платеж в системе Вашей системе
    $comment = ''; //Необязательном поле,  служебный комментарий.

    $result = 7; //Проверяем возможность отмены платежа, если нет - отдаем result - 7, дальнейшая реализация не нужна
    pltz_sendResponse("<extTransactionID>$extTransactionID</extTransactionID>
      <account>$account</account>
     <result>$result</result>
     <comment>$comment</comment>");
}

$xml = file_get_contents("php://input");

pltz_parseXML($xml);

//debug mode logging
if (DEBUG_MODE) {
    //$debugSave = print_r($xml, true);
    file_put_contents('debug.log', $xml, FILE_APPEND | LOCK_EX);
}
