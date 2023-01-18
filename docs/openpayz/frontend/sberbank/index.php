<?php
/*
 * Фронтенд поддержки приема платежей от сбербанка посредством GET запросов
 * согласно протокола тип Б
 */

$allowed_ips = "91.232.246.58"; 
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
function sber_CheckGet($params) {
    $result=true;
    if (!empty ($params)) {
        foreach ($params as $eachparam) {
            if (isset($_GET[$eachparam])) {
                if (empty ($_GET[$eachparam])) {
                $result=false;                    
                }
            } else {
                $result=false;
            }
        }
     }
     return ($result);
   } 
   
   
 /*
 * Check is transaction unique?
 * 
 * @param $hash - transaction hash
 * 
 * @return bool
 */
function sber_CheckTransaction($hash) {
    $hash=  mysql_real_escape_string($hash);
    $query="SELECT `id` from `op_transactions` WHERE `hash`='".$hash."'";
    $data=  simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}   

 /*
 * Gets transaction date in sberbank valid format
 * 
 * @param $hash - transaction hash
 * 
 * @return bool
 */
function sber_getTransactionDate($hash) {
    $hash=  mysql_real_escape_string($hash);
    $query="SELECT `date` from `op_transactions` WHERE `hash`='".$hash."'";
    $data=  simple_query($query);
    $rawDate=$data['date'];
    $timestamp=  strtotime($rawDate);
    $result=date("Y-m-d\TH:i:s",$timestamp);
    return ($result);
}   
   
// Проверка разрешенных IP для доступа
$ips = explode(",",$allowed_ips);
if (array_search($_SERVER["REMOTE_ADDR"],$ips) === FALSE)
{
    $replyCheck='Wrong IP';
    $replyCheck=trim($replyCheck);
    die($replyCheck);
    exit;
};

$required=array('command');

// ловим объязательные параметры
if (sber_CheckGet($required)) {
    $action=vf($_GET['command']);
    //проверка на валидность юзера и транзакции
    if ($action=='check') {
        if (sber_CheckGet(array('account'))) {
            $allcustomers=  op_CustomersGetAll();
            if (isset($allcustomers[$_GET['account']])) {
                $replyCheck='
                <?xml version="1.0" encoding="UTF-8"?>
                <response>
                    <osmp_txn_id>'.$txn_id.'</osmp_txn_id>
                    <result>0</result>
                </response>
                ';

            } else {
                $replyCheck='
                <?xml version="1.0" encoding="UTF-8"?>
                <response>
                    <osmp_txn_id>'.$txn_id.'</osmp_txn_id>
                    <result>5</result>
                    <comment>Идентификатор абонента не найден (Ошиблись номером)</comment>
                </response>
                ';
            }
        $replyCheck=trim($replyCheck);
        die($replyCheck);
        } 
        
    }
    
    //обработка входящего платежа
    if ($action=='pay') {
        if (sber_CheckGet(array('sum','txn_id','account'))) {
            $hashClean= $_GET['txn_id'];
            $hashStore='SBERBANK_'.$hashClean;
            $summ= $_GET['sum'];
            $paysys='SBERBANK';
            $note='some debug info here';
            $customer_id=$_GET['account'];
            $date=date("Y-m-d\TH:i:s");
            
            $allcustomers=  op_CustomersGetAll();
            
            if (isset($allcustomers[$customer_id])) {
                if (sber_CheckTransaction($hashStore)) {
                 //регистрируем новую транзакцию
                 op_TransactionAdd($hashStore, $summ, $customer_id, $paysys, $note);
                 //вызываем обработчики необработанных транзакций
                 op_ProcessHandlers();
                    
                    $replyPayment='
                        <?xml version="1.0" encoding="UTF-8"?>
                        <response>
                            <osmp_txn_id>'.$txn_id.'</osmp_txn_id>
                            <prv_txn>'.$hashStore.'</prv_txn>
                            <sum>'.$summ.'</sum>
                            <result>0</result>
                        </response>
                        ';
                    
                } else {
                    $replyPayment='
                        <?xml version="1.0" encoding="UTF-8"?>
                        <response>
                            <osmp_txn_id>'.$txn_id.'</osmp_txn_id>
                            <prv_txn>'.$hashStore.'</prv_txn>
                            <sum>'.$summ.'</sum>
                            <result>0</result>
                        </response>
                        ';
                }
                
            } else {
                $replyPayment='
                    <?xml version="1.0" encoding="UTF-8"?>
                    <response>
                        <osmp_txn_id>'.$txn_id.'</osmp_txn_id>
                        <prv_txn>'.$hashStore.'</prv_txn>
                        <sum>'.$summ.'</sum>
                        <result>5</result>
                        <comment>Идентификатор абонента не найден (Ошиблись номером)</comment>
                    </response>
                    ';
            }
            
            $replyPayment=trim($replyPayment);
            die($replyPayment);
            
        }
    }
}

?>
