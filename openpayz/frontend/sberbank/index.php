<?php
/*
 * Фронтенд поддержки приема платежей от сбербанка посредством GET запросов
 * согласно протокола: http://store.nightfly.biz/st/1393110118/sberbank.rtf
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
    $hash=  loginDB_real_escape_string($hash);
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
    $hash=  loginDB_real_escape_string($hash);
    $query="SELECT `date` from `op_transactions` WHERE `hash`='".$hash."'";
    $data=  simple_query($query);
    $rawDate=$data['date'];
    $timestamp=  strtotime($rawDate);
    $result=date("Y-m-d\TH:i:s",$timestamp);
    return ($result);
}   
   
   
$required=array('action');

// ловим объязательные параметры
if (sber_CheckGet($required)) {
    $action=vf($_GET['action']);
    //проверка на валидность юзера
    if ($action=='check') {
        if (sber_CheckGet(array('number'))) {
            $allcustomers=  op_CustomersGetAll();
            if (isset($allcustomers[$_GET['number']])) {
                $replyCheck='
                <?xml version="1.0" encoding="utf-8"?>
                <response>
                <code>0</code>
                <message>Абонент существует, возможен прием Платежей</message>
                </response>
                ';

            } else {
                $replyCheck='
                <?xml version="1.0" encoding="utf-8"?>
                <response>
                <code>2</code>
                <message>Абонент не найден</message>
                </response>
                ';
            }
        $replyCheck=trim($replyCheck);
        die($replyCheck);
        } 
        
    }
    
    //обработка входящего платежа
    if ($action=='payment') {
        if (sber_CheckGet(array('amount','receipt','number'))) {
            $hashClean= $_GET['receipt'];
            $hashStore='SBERBANK_'.$hashClean;
            $summ= $_GET['amount'];
            $paysys='SBERBANK';
            $note='some debug info here';
            $customer_id=$_GET['number'];
            $date=date("Y-m-d\TH:i:s");
            
            $allcustomers=  op_CustomersGetAll();
            
            if (isset($allcustomers[$customer_id])) {
                if (sber_CheckTransaction($hashStore)) {
                 //регистрируем новую транзакцию
                 op_TransactionAdd($hashStore, $summ, $customer_id, $paysys, $note);
                 //вызываем обработчики необработанных транзакций
                 op_ProcessHandlers();
                    
                    $replyPayment='
                    <?xml version="1.0" encoding="utf-8"?>
                    <response>
                    <code>0</code>
                    <date>'.$date.'</date>
                    <message>Платеж успешно обработан</message>
                    </response>
                    ';
                    
                } else {
                    $replyPayment='
                    <?xml version="1.0" encoding="utf-8"?>
                    <response>
                    <code>0</code>
                    <date>'.$date.'</date>
                    <message>Платеж уже обработан</message>
                    </response>
                    ';
                }
                
            } else {
                $replyPayment='
                    <?xml version="1.0" encoding="utf-8"?>
                    <response>
                    <code>2</code>
                    <date>'.$date.'</date>
                    <message>Абонент не найден</message>
                    </response>
                    ';
            }
            
            $replyPayment=trim($replyPayment);
            die($replyPayment);
            
        }
    }
    
    //проверка состояния транзакции
    if ($action=='status') {
        if (sber_CheckGet(array('receipt'))) {
            $hashClean=$_GET['receipt'];
            $hashStore='SBERBANK_'.$hashClean;
            
            if (!sber_CheckTransaction($hashStore)) {
                $date=  sber_getTransactionDate($hashStore);
                
                $replyStatus='
                    <?xml version="1.0" encoding="utf-8"?>
                    <response>
                    <code>0</code>
                    <date>'.$date.'</date>
                    <message>Платеж обработан</message>
                    </response>
                    ';
            } else {
                $date=date("Y-m-d\TH:i:s");
                
                $replyStatus='
                    <?xml version="1.0" encoding="utf-8"?>
                    <response>
                    <code>6</code>
                    <date>'.$date.'</date>
                    <message>Платеж не найден</message>
                    </response>
                    ';
            }
            
            $replyStatus=trim($replyStatus);
            die($replyStatus);
            
        }
    }
    
    //отмену платежей игнорируем - чай не булочками торгуем.
    if ($action=='cancel') {
        $date=date("Y-m-d\TH:i:s");
        $replyCancel='
                  <?xml version="1.0" encoding="utf-8"?>
                    <response>
                    <code>1</code>
                    <date>'.$date.'</date>
                    <message>Операция не поддерживается</message>
                    </response>
            ';
        $replyCancel=trim($replyCancel);
        die($replyCancel);
    }
    
}

?>