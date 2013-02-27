<?php

/*
* Фронтенд для получения оплат от QIWI в виде GET запроса
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

$required=array('command','txn_id','account','sum');

//если нас пнули объязательными параметрами
if (qiwi_CheckGet($required)) {
    
    //это нас киви как-бы проверяет на вшивость
    if ($_GET['command']=='check') {
       $allcustomers=  op_CustomersGetAll();
        
                $hash=$_GET['txn_id'];
                $summ=$_GET['sum'];
                $customerid=trim($_GET['account']);
                $paysys='QIWI';
                $note='some debug info';
                
                //нашелся братиша!
                if (isset($allcustomers[$customerid])) {

                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();
                
                $good_reply='
                    <?xml version="1.0"?>
                    <response>
                       <osmp_txn_id>'.$hash.'</osmp_txn_id>
                       <result>0</result>
                    </response>
                    ';
                
                die($good_reply);
                
                } else {
                    die('Пшли нах');
                }

                
    }
    
}

?>