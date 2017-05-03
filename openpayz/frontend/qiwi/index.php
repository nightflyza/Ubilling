<?php

/*
 * Фронтенд для получения оплат от QIWI в виде GET запроса
 * Написан в соответствии с: http://store.nightfly.biz/st/1388225556/qiwi_custom_providers.pdf
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

/**
 *
 * Check for GET have needed variables
 *
 * @param   $params array of GET variables to check
 * @return  bool
 *
 */
function qiwi_CheckGet($params) {
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
 * @param $hash - transaction hash
 * 
 * @return bool
 */
function qiwi_CheckTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

$required = array('command', 'txn_id', 'account', 'sum');

//если нас пнули объязательными параметрами
if (qiwi_CheckGet($required)) {

    //это нас киви как-бы проверяет на вшивость
    if ($_GET['command'] == 'check') {
        $allcustomers = op_CustomersGetAll();

        $hash = $_GET['txn_id'];
        $summ = $_GET['sum'];
        $customerid = trim($_GET['account']);
        $paysys = 'QIWI';
        $hashStore = $paysys . '_' . $hash;
        $note = 'some debug info:' . $hash . ' ' . $summ . ' ' . $customerid . ' ' . $command;

        //нашелся братиша!
        if (isset($allcustomers[$customerid])) {
            //проверяем транзакцию на уникальность   
            if (qiwi_CheckTransaction($hashStore)) {
                //и если все ок - регистрируем новую  
                //регистрируем новую транзакцию
                op_TransactionAdd($hashStore, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();
            }

            //в любом случае отвечаем, что у нас все хорошо в этой жизни
            $good_reply = '
                    <?xml version="1.0"?>
                    <response>
                       <osmp_txn_id>' . $hash . '</osmp_txn_id>
                       <result>0</result>
                    </response>
                    ';
            $good_reply = trim($good_reply);
            die($good_reply);
        } else {
            $bad_reply = '
                        <?xml version="1.0" encoding="UTF-8"?>
                        <response>
                        <osmp_txn_id>' . $hash . '</osmp_txn_id>
                        <result>5</result>
                        <comment>The subscriber has gone to Bobruisk...</comment>
                        </response>
                        ';
            $bad_reply = trim($bad_reply);
            die($bad_reply);
        }
    }
}
?>