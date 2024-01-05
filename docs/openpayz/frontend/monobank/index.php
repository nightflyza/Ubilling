<?php

/*
 * Фронтенд для получения оплат от Монобанка в виде GET запроса
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

/**
 * Check for GET have needed variables
 *
 * @param  array $params array of GET variables to check
 * @return  bool
 *
 */
function mono_CheckGet($params) {
    $result = true;
    if (!empty($params)) {
        foreach ($params as $eachparam) {
            if (isset($_GET[$eachparam])) {
                if (empty($_GET[$eachparam])) {
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
 * Check is transaction unique?
 *
 * @param string $hash string hash to check
 *
 * @return bool
 */
function mono_CheckTransaction($hash) {
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
 * Get transaction id by its hash
 *
 * @param  string $tablename name of the table to extract last id
 * @return  string
 *
 */
function mono_getIdByHash($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $result = simple_query($query);
    return ($result['id']);
}

/**
 * Returns all user RealNames
 * 
 * @return array
 */
function mono_UserGetAllRealnames() {
    $query_fio = "SELECT * from `realname`";
    $allfioz = simple_queryall($query_fio);
    $fioz = array();
    if (!empty($allfioz)) {
        foreach ($allfioz as $ia => $eachfio) {
            $fioz[$eachfio['login']] = $eachfio['realname'];
        }
    }
    return($fioz);
}

/**
 * Returns array of availble user address as login=>address
 * 
 * @return array
 */
function mono_AddressGetFulladdresslist() {
//наглая заглушка
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
 * Get transaction datetime by its hash
 *
 * @param  string $tablename name of the table to extract last id
 * @return  string
 *
 */
function mono_getDateByHash($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `date` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $result = simple_query($query);
    return ($result['date']);
}

$required = array('command', 'txn_id', 'account', 'sum');

//если нас пнули объязательными параметрами
if (mono_CheckGet($required)) {

    //это нас monobank как-бы проверяет на вшивость
    if ($_GET['command'] == 'check') {
        $allcustomers = op_CustomersGetAll();
        $hashClean = trim($_GET['txn_id']);
        $customerid = trim($_GET['account']);

        //нашелся братиша!
        if (isset($allcustomers[$customerid])) {
            $userlogin = $allcustomers[$customerid];
            $alladdress = mono_AddressGetFulladdresslist();
            $allrealnames = mono_UserGetAllRealnames();
            $userData = simple_query("SELECT * from `users` WHERE `login`='" . $userlogin . "'");
            $userMail = simple_query("SELECT * from `emails` WHERE `login`='" . $userlogin . "'");
            $good_reply = '
                    <?xml version="1.0" encoding="UTF-16"?>
                    <response>
                       <ibox_txn_id>' . $hashClean . '</ibox_txn_id>
                           <result>0</result>
                           <fields>
                           <field1 name="balance">' . @$userData['Cash'] . '</field1>
                           <field3 name="name">' . @$allrealnames[$userlogin] . '</field3>
			   <field4 name="address">' . @$alladdress[$userlogin] . '</field4>
			   </fields>
                    </response>
                    ';
            $good_reply = trim($good_reply);
            die($good_reply);
        } else {

            $bad_reply = '
                  <?xml version="1.0" encoding="UTF-8"?>
                    <response>
                       <ibox_txn_id>' . $hashClean . '</ibox_txn_id>
                       <result>5</result>
                  </response>
                ';
            $bad_reply = trim($bad_reply);
            die($bad_reply);
        }
    }

    //Запрос на внесение платежа
    if ($_GET['command'] == 'pay') {

        $hash = 'MONOB_' . trim($_GET['txn_id']);
        $hashClean = trim($_GET['txn_id']);
        $summ = $_GET['sum'];
        $customerid = trim($_GET['account']);
        $paysys = 'MONOBANK';
        $note = 'no debug info here';

        $allcustomers = op_CustomersGetAll();
        //опять ожидаем подляны и все-таки проверим хотя бы валидность кастомера
        if (isset($allcustomers[$customerid])) {

            //а также уникальность транзакции
            if (mono_CheckTransaction($hash)) {
                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();

                $newTransactionId = mono_getIdByHash($hash);
                $newTransactionDate = mono_getDateByHash($hash);

                $good_reply = '
            <?xml version="1.0" encoding="UTF-8"?>
            <response>
            <ibox_txn_id>' . $hashClean . '</ibox_txn_id>
            <prv_txn>' . $newTransactionId . '</prv_txn>
            <prv_txn_date>' . $newTransactionDate . '</prv_txn_date>
            <sum>' . $summ . '</sum>
            <result>0</result>
            <comment>OK</comment>
            </response>
            ';
                $good_reply = trim($good_reply);
                die($good_reply);
            } else {
                //Если транзакция уже зарегистрирована
                $newTransactionId = mono_getIdByHash($hash);
                $newTransactionDate = mono_getDateByHash($hash);
                $transactionDoneReply = '
                    <?xml version="1.0" encoding="UTF-8"?>
                    <response>
                    <ibox_txn_id>' . $hashClean . '</ibox_txn_id>
                    <prv_txn>' . $newTransactionId . '</prv_txn>
                    <prv_txn_date>' . $newTransactionDate . '</prv_txn_date>
                    <sum>' . $summ . '</sum>
                    <result>0</result>
                    <comment>OK</comment>
                    </response>
                    ';

                $transactionDoneReply = trim($transactionDoneReply);
                die($transactionDoneReply);
            }
        } else {
            $bad_reply = '
                  <?xml version="1.0"?>
                    <response>
                       <ibox_txn_id>' . $hashClean . '</ibox_txn_id>
                       <result>5</result>
                  </response>
                ';
            $bad_reply = trim($bad_reply);
            die($bad_reply);
        }
    }
}
?>
