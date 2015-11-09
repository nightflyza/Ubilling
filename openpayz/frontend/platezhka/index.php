<?php

/*
 * Фронтенд платежной системы www.platezhka.com.ua получающий ответы в виде POST XML 
 * согласно протокола: http://store.nightfly.biz/st/1430694725/platezhkacomua_interface.rtf
 */

//Секция конфигурации

$serviceId = 1; //номер сервиса по которому мы принимаем платежи
$checkMode = true; // включена ли дополнительная авторизация по логину/паролю?
$checkLogin = 'testme'; // собственно логин и пароль для доп. авторизации.
$checkPassword = '12345';



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
 * Check is transaction unique?
 * 
 * @param $hash - transaction hash
 * 
 * @return bool
 */
function pltz_CheckTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

/**
 * Returns next free transaction ID
 * 
 * @return int
 */
function pltz_GetFreeId() {
    $query = "SELECT `id` from `op_transactions` ORDER BY `id` DESC LIMIT 1";
    $result = simple_query($query);
    if (!empty($result)) {
        $result = $result['id'] + 1;
    } else {
        $result = 1;
    }
    return ($result);
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
                    pltz_Check(pltz_getValueByTag($xmlArr, 'login'), pltz_getValueByTag($xmlArr, 'password'), pltz_getValueByTag($xmlArr, 'payElementID'), pltz_getValueByTag($xmlArr, 'transactionID'), pltz_getValueByTag($xmlArr, 'account'));
                    return;
                case 'pay'://Оплата 
                    pltz_Payment(pltz_getValueByTag($xmlArr, 'login'), pltz_getValueByTag($xmlArr, 'password'), pltz_getValueByTag($xmlArr, 'transactionID'), pltz_getValueByTag($xmlArr, 'payTimestamp'), pltz_getValueByTag($xmlArr, 'payID'), pltz_getValueByTag($xmlArr, 'payElementID'), pltz_getValueByTag($xmlArr, 'account'), pltz_getValueByTag($xmlArr, 'amount'), pltz_getValueByTag($xmlArr, 'terminalId'));
                    return;
                case 'cancel'://Отмена платежа 
                    pltz_Cancel(pltz_getValueByTag($xmlArr, 'login'), pltz_getValueByTag($xmlArr, 'password'), pltz_getValueByTag($xmlArr, 'transactionID'), pltz_getValueByTag($xmlArr, 'cancelPayID'), pltz_getValueByTag($xmlArr, 'payElementID'), pltz_getValueByTag($xmlArr, 'account'), pltz_getValueByTag($xmlArr, 'amount'));
                    return;
            }
        }
    }
}

function pltz_Check($login, $password, $payElementID, $transactionID, $account) {
    global $serviceId;
    $extTransactionID = 0; //Платеж в системе Вашей системе
    $result = 0; //Поле кода завершения (см. Приложение А. Список кодов завершения)
    $comment = ''; //Необязательном поле,  служебный комментарий.
    //Здесь записываем в базу поступивший запрос, для того что бы потом разобраться какие запросы к Вам приходили. Уникальный индификатор запроса - $transactionID

    if (pltz_AuthLogin($login, $password)) { //Проверяем $login, $password, что бы отсекать чужие запросы
        if ($payElementID == $serviceId) { //Ищем сервис для оплаты (по $payElementID) в Вашей БД
            $allcustomers = op_CustomersGetAll();
            if (isset($allcustomers[$account])) { //Проверяем в БД абонента (по $account)
                //Здесь нужно сохранить платеж в базу, со статусом не оплачен
                $extTransactionID = 'PLTZ_' . pltz_GetFreeId(); //Записываем сюда номер Вашей транзакции.  
                $comment = 'Ожидание платежа'; //Коментарий не обязателен
            } else {
                $result = 5; //Идентификатор абонента не найден (Ошиблись номером). Здесь может быть другая ошибка, например 79 (Счет абонента не активен) 
                $comment = 'Идентификатор абонента не найден'; //Коментарий не обязателен
            }
        } else {
            $result = 7; //Прием платежа запрещен провайдером
        }
    } else {
        $result = 7; //Прием платежа запрещен провайдером
    }

    pltz_sendResponse("<extTransactionID>$extTransactionID</extTransactionID>
     <account>$account</account>
     <result>$result</result>
     <comment>$comment</comment>");
}

function pltz_Payment($login, $password, $transactionID, $payTimestamp, $payID, $payElementID, $account, $amount, $terminalId) {
    $extTransactionID = 0; //Платеж в системе Вашей системе
    $result = 0; //Поле кода завершения (см. Приложение А. Список кодов завершения)
    $comment = ''; //Необязательном поле,  служебный комментарий.
    //Здесь записываем в базу поступивший запрос, для того что бы потом разобраться какие запросы к Вам приходили. Уникальный индификатор запроса - $transactionID

    if (pltz_AuthLogin($login, $password)) {//Проверяем $login, $password, что бы отсекать чужие запросы
        $extTransactionID = 'PLTZ_' . pltz_GetFreeId(); //Записываем сюда номер Вашей транзакции
        if (pltz_CheckTransaction($extTransactionID)) { //Обязательно нужно проверить(по $payID) платеж в Вашей системе, если платеж оплачен -  возвращаем result - 0
            $hash = $extTransactionID;
            $summ = $amount / 100; // деньги то в копейках
            $customerid = $account;
            $paysys = 'PLATEZHKA';
            $note = 'transactionID:' . $transactionID . ' amount:' . $amount;

            //регистрируем новую транзакцию
            op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
            //вызываем обработчики необработанных транзакций
            op_ProcessHandlers();

            $result = 0; //ОК
            $comment = 'Платеж выполнен'; //Коментарий не обязателен
        } else {
            $result = 0; //ОК  
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

pltz_parseXML(file_get_contents("php://input"));
?>
