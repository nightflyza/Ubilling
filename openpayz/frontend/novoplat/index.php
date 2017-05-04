<?php

/*
* Фронтенд для получения оплат от "Новоплат" ( http://www.novo-plat.ru )  в виде GET запроса
* 
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
function nvp_CheckGet($params) {
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
   
   
/**
 *
 * Get last id from specified table
 *
 * @param   $tablename name of the table to extract last id
 * @return  string
 *
 */
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


/*
 * Trims leading zeros
 * 
 * @param $string string to trim
 * 
 * @return string
 */
function nvp_RemoveLeadingZeros($string) {
 $result = ltrim($string, '0');
 return ($result);
}
   
   //чего мы там ожидали получить?
   $required=array('command','txn_id','account','sum');
   
   //если нас пнули объязательными параметрами
if (nvp_CheckGet($required)) {
    //берем всех существующих кастомеров
    $allcustomers=  op_CustomersGetAll();
    //трансформируем в удобный для нас вид параметры
                $hash=$_GET['txn_id'];
                $summ=$_GET['sum'];
                $customerid=$_GET['account'];
                $customerid=  nvp_RemoveLeadingZeros($customerid);
                $paysys='NOVOPLAT';
                $note='no debug info yet';


    //для $customerid принимаем только числа            
    $reg= "|^[\d]*$|";            
    if (!preg_match($reg, $customerid)) {
        $reply_code=4;
        $comment='Wrong account format';
        
        $error_template='
        <?xml version="1.0" encoding="UTF-8"?>
        <response>
        <osmp_txn_id/>
        <result>'.$reply_code.'</result>
        '.$comment.'
        </response>';
        
        die($error_template);
    }
    
    //обрабатываем запрос проверки состояния абонента
    if ($_GET['command']=='check') {
        if (isset($allcustomers[$customerid])) {
            $reply_code=0;
            $comment='';
        } else {
            $reply_code=5;
            $comment='<comment>The subscriber has gone to Bobruisk...</comment>';
        }
        
        //а это у нас такой шаблон ответа будет   
        $reply_template='
        <?xml version="1.0" encoding="UTF-8"?>
        <response>
        <osmp_txn_id>'.$hash.'</osmp_txn_id>
        <result>'.$reply_code.'</result>
        '.$comment.'
        </response>
    ';
        die($reply_template);
    }
    
    //обрабатываем запрос на проведение оплаты
    if ($_GET['command']=='pay') {
        //давайте еще разок проверим, для надежности - вдруг убил кто-то абонента
        if (isset($allcustomers[$customerid])) {
            $transactionCheck=  nvp_CheckTransaction($hash);
            
            //если еще нету такой транзакции регистрируем и обрабатываем новую
            if (!$transactionCheck) {
                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();
                
                $transaction_id=nvp_GetLastid('op_transactions');
            } else {
                //если уже есть - тихонечко говорим что все ок
                $transaction_id=$transactionCheck;
            }
                
                //а это опять шаблон ответа, но уже о успешной транзакции
                $pay_reply='
                <?xml version="1.0" encoding="UTF-8"?>
                <response>
                <osmp_txn_id>'.$hash.'</osmp_txn_id>
                <prv_txn>'.$transaction_id.'</prv_txn>
                <sum>'.$summ.'</sum>
                <result>0</result>
                </response>
                ';
                die($pay_reply);
        }
    }
    


    
} else {
    $reply_code=300;
    $comment='<comment> Incomplete request </comment>';

    $error_template='
    <?xml version="1.0" encoding="UTF-8"?>
    <response>
    <osmp_txn_id/>
    <result>'.$reply_code.'</result>
    '.$comment.'
    </response>
    ';
    die($error_template);
}
   
?>
