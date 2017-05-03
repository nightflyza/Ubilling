<?php

/*
 * Фронтенд для получения оплат от IBOX в виде GET запроса
 * Документация по реализуемому протоколу: http://store.nightfly.biz/st/1389011954/ibox_protocol.docx
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
function ibox_CheckGet($params) {
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
function ibox_CheckTransaction($hash) {
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
function ibox_getIdByHash($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $result = simple_query($query);
    return ($result['id']);
}

/**
 * Get transaction datetime by its hash
 *
 * @param  string $tablename name of the table to extract last id
 * @return  string
 *
 */
function ibox_getDateByHash($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `date` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $result = simple_query($query);
    return ($result['date']);
}

$required = array('command', 'txn_id', 'account', 'sum');

//если нас пнули объязательными параметрами
if (ibox_CheckGet($required)) {

    //это нас Ibox как-бы проверяет на вшивость
    if ($_GET['command'] == 'check') {
        $allcustomers = op_CustomersGetAll();

        $hashClean = trim($_GET['txn_id']);
        $customerid = trim($_GET['account']);

        //нашелся братиша!
        if (isset($allcustomers[$customerid])) {

            $good_reply = '
                    <?xml version="1.0"?>
                    <response>
                       <ibox_txn_id>' . $hashClean . '</ibox_txn_id>
                       <result>0</result>
                    </response>
                    ';
            $good_reply = trim($good_reply);
            die($good_reply);
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

    //Запрос на внесение платежа 
    if ($_GET['command'] == 'pay') {

        $hash = 'IBOX_' . trim($_GET['txn_id']);
        $hashClean = trim($_GET['txn_id']);
        $summ = $_GET['sum'];
        $customerid = trim($_GET['account']);
        $paysys = 'IBOX';
        $note = 'some debug info';

        $allcustomers = op_CustomersGetAll();
        //опять ожидаем подляны и все-таки проверим хотя бы валидность кастомера
        if (isset($allcustomers[$customerid])) {

            //а также уникальность транзакции
            if (ibox_CheckTransaction($hash)) {
                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();

                $newTransactionId = ibox_getIdByHash($hash);
                $newTransactionDate = ibox_getDateByHash($hash);

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
                $newTransactionId = ibox_getIdByHash($hash);
                $newTransactionDate = ibox_getDateByHash($hash);
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
