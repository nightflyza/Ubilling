<?php
 
/*
* Фронтенд для получения оплат от COMEPAY в виде GET запроса
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
function comepay_CheckGet($params) {
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

function  nvp_GetLastid($tablename) {
    $tablename=loginDB_real_escape_string($tablename);
    $query="SELECT `id` from `".$tablename."` ORDER BY `id` DESC LIMIT 1";
    $result=simple_query($query);
    return ($result['id']);
}

/*
 * Check is transaction unique?
 */
function nvp_CheckTransaction($hash) {
    $hash=  loginDB_real_escape_string($hash);
    $query="SELECT `id` from `op_transactions` WHERE `hash`='".$hash."'";
    $data=  simple_query($query);
    if (!empty($data)) {
        return ($data['id']);
    } else {
        return (false);
    }
}

$required=array('command','txn_id','account','sum');

//если нас пнули объязательными параметрами
if (comepay_CheckGet($required)) {

    //это нас киви как-бы проверяет на вшивость
    if ($_GET['command']=='check') {
        $allcustomers = op_CustomersGetAll();

        $hash='COME_'.$_GET['txn_id'];
        $summ=$_GET['sum'];
        $customerid=trim($_GET['account']);
        $paysys='COMEPAY';
        $note='some debug info';

        //нашелся братиша!
        if (isset($allcustomers[$customerid])) {
            $transactionCheck = nvp_CheckTransaction($hash);

            //если еще нету такой транзакции регистрируем и обрабатываем новую
            if (!$transactionCheck) {
                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();
            }

            $good_reply='
            <?xml version="1.0"?>
            <response>
            <osmp_txn_id>'.$hash.'</osmp_txn_id>
            <result>0</result>
            </response>
            ';

            die(trim($good_reply));

        } else {
            $bad_reply='
            <?xml version="1.0"?>
            <response>
            <osmp_txn_id>'.$hash.'</osmp_txn_id>
            <result>4</result>
            </response>
            ';
            die(trim($bad_reply));
            
        }
    }
}
 
?>