<?php

/*
 * Фронтенд для получения оплат от pay-logic / ОСМП в виде GET запроса
 * Документация по реализуемому протоколу: http://store.nightfly.biz/st/1386893103/interface_podkl_usl.pdf
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
function osmp_CheckGet($params) {
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

/*
 * Check is transaction unique?
 * 
 * @param $hash string hash to check
 * 
 * @return bool
 */

function osmp_CheckTransaction($hash) {
    $hash = loginDB_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

/**
 *
 * Get transaction id by its hash
 *
 * @param   $tablename name of the table to extract last id
 * @return  string
 *
 */
function  osmp_getIdByHash($hash) {
    $hash=loginDB_real_escape_string($hash);
    $query="SELECT `id` from `op_transactions` WHERE `hash`='".$hash."'";
    $result=simple_query($query);
    return ($result['id']);
}

$required = array('command', 'txn_id', 'account', 'sum');

//если нас пнули объязательными параметрами
if (osmp_CheckGet($required)) {

    //это нас ОСМП как-бы проверяет на вшивость
    if ($_GET['command'] == 'check') {
        $allcustomers = op_CustomersGetAll();

        $hashClean=trim($_GET['txn_id']);
        $customerid = trim($_GET['account']);

        //нашелся братиша!
        if (isset($allcustomers[$customerid])) {

            $good_reply ='
                    <?xml version="1.0"?>
                    <response>
                       <osmp_txn_id>'. $hashClean.'</osmp_txn_id>
                       <result>0</result>
                    </response>
                    ';
            $good_reply=trim($good_reply);
            die($good_reply);
            
        } else {

            $bad_reply='
                  <?xml version="1.0"?>
                    <response>
                       <osmp_txn_id>'.$hashClean.'</osmp_txn_id>
                       <result>5</result>
                  </response>
                ';
            $bad_reply=trim($bad_reply);
            die($bad_reply);
        }
    }
    
    //Запрос на внесение платежа 
    if ($_GET['command'] == 'pay') {
        
        $hash = 'OSMP_'.trim($_GET['txn_id']);
        $hashClean=trim($_GET['txn_id']);
        $summ = $_GET['sum'];
        $customerid = trim($_GET['account']);
        $paysys = 'OSMP';
        $note = 'some debug info';
        
        $allcustomers = op_CustomersGetAll();
        //опять ожидаем подляны и все-таки проверим хотя бы валидность кастомера
        if (isset($allcustomers[$customerid])) {
         
        //а также уникальность транзакции
        if (osmp_CheckTransaction($hash)) {     
        //регистрируем новую транзакцию
        op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
        //вызываем обработчики необработанных транзакций
        op_ProcessHandlers();
        
        $newTransactionId=  osmp_getIdByHash($hash);
            
        $good_reply='
            <?xml version="1.0" encoding="UTF-8"?>
            <response>
            <osmp_txn_id>'.$hashClean.'</osmp_txn_id>
            <prv_txn>'.$newTransactionId.'</prv_txn>
            <sum>'.$summ.'</sum>
            <result>0</result>
            <comment>OK</comment>
            </response>
            ';
            $good_reply=trim($good_reply);
            die($good_reply); 
            } else {
                $newTransactionId=  osmp_getIdByHash($hash);
                $transactionDoneReply='
                    <?xml version="1.0" encoding="UTF-8"?>
                    <response>
                    <osmp_txn_id>'.$hashClean.'</osmp_txn_id>
                    <prv_txn>'.$newTransactionId.'</prv_txn>
                    <sum>'.$summ.'</sum>
                    <result>0</result>
                    <comment>OK</comment>
                    </response>
                    ';
                
                $transactionDoneReply=trim($transactionDoneReply);
                die($transactionDoneReply);
            }
        } else {
              $bad_reply='
                  <?xml version="1.0"?>
                    <response>
                       <osmp_txn_id>'.$hashClean.'</osmp_txn_id>
                       <result>5</result>
                  </response>
                ';
            $bad_reply=trim($bad_reply);
            die($bad_reply);
        }
        
    }
}
?>
