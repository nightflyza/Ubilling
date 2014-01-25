<?php
/*
 * Фронтенд для взаимодействия с EasyPay 
 * на базе протокола: http://store.nightfly.biz/st/1390601030/EasySoft.Provider.2.8.docx
 * 
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");


//Error codes
define('NO_SUCH_USER', '-1');
define('ERROR_PAYMENT', '-2');
define('TRANSACTION_EXISTS', '-3');
define('TRANSACTION_NOT_EXIST', '-4');
define('BAD_CHECKSUM', '-5');


/*
 * Returns all user RealNames
 * 
 * @return array
 */

function ep_UserGetAllRealnames() {
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


/*
 * Check is transaction unique?
 * 
 * @param $hash - hash string to check
 * @return bool/array
 */
function ep_GetTransaction($hash) {
    $hash=  mysql_real_escape_string($hash);
    $query="SELECT `id` from `op_transactions` WHERE `hash`='".$hash."'";
    $data=  simple_query($query);
    if (!empty($data)) {
        return ($data['id']);
    } else {
        return (false);
    }
}



//Ловим POST запрос
$xml=$HTTP_RAW_POST_DATA;

$rawXml =  xml2array($xml);

//проверяем запрос на валидность вообще
if (!empty($rawXml)) {
if (isset($rawXml['Request'])) {
    //определяем тип запроса
    if (isset($rawXml['Request']['Check'])) {
        //
        // проверка пользователя
        //
        $allcustomers=  op_CustomersGetAll();
        $checkCustomerId=$rawXml['Request']['Check']['Account'];
        if (isset($allcustomers[$checkCustomerId])) {
            @$userlogin=$allcustomers[$checkCustomerId];
            $allrealnames=  ep_UserGetAllRealnames();
            
            $reply='
                <Response>
                <StatusCode>0</StatusCode>
                <StatusDetail>Ok</StatusDetail>
                <DateTime>'.date("Y-m-d\TH:i:s").'</DateTime>
                 <AccountInfo>
                    <Name>'.@$allrealnames[$userlogin].'</Name>
                 </AccountInfo>
                </Response>
                ';
            $reply=trim($reply);
            die($reply);
        } else {
            $reply='
                <Response>
                <StatusCode>'.NO_SUCH_USER.'</StatusCode>
                <StatusDetail>User not found</StatusDetail>
                </Response>
                ';
            $reply=trim($reply);
            die($reply);
        }
        
        
    }
    
        //
        // создание платежа
        //
        if (isset($rawXml['Request']['Payment'])) {
            $allcustomers=  op_CustomersGetAll();
            $paymentCustomerId= mysql_real_escape_string($rawXml['Request']['Payment']['Account']);
            $hashClean=  mysql_real_escape_string($rawXml['Request']['Payment']['OrderId']);
            $hash= 'EP_'.$hashClean;
            $summ=  mysql_real_escape_string($rawXml['Request']['Payment']['Amount']);
            
            if (isset($allcustomers[$paymentCustomerId])) {
                if (!ep_GetTransaction($hash)) { 
                //регистрируем новую транзакцию, вызываем хендлеры, получаем айдишку
                   op_TransactionAdd($hash, $summ, $paymentCustomerId, 'EASYPAY', 'no debug info');
                   op_ProcessHandlers();
                   $newTransactionId=  ep_GetTransaction($hash);
                   
                $reply='
                <Response>
                <StatusCode>0</StatusCode>
                <StatusDetail>Order Created</StatusDetail>
                <DateTime>'.date("Y-m-d\TH:i:s").'</DateTime>
                <PaymentId>'.$newTransactionId.'</PaymentId>
                </Response>
                ';
                    $reply=trim($reply);
                    die($reply);
                } else {
                    //уже есть такая транзакцийка
                     $reply='
                     <Response>
                        <StatusCode>'.TRANSACTION_EXISTS.'</StatusCode>
                        <StatusDetail>Duplicate transaction</StatusDetail>
                        <DateTime>'.date("Y-m-d\TH:i:s").'</DateTime>
                        <PaymentId>'.ep_GetTransaction($hash).'</PaymentId>
                     </Response>
                    ';
                     $reply=trim($reply);
                     die($reply);
                }
            } else {
                $reply='
                     <Response>
                        <StatusCode>'.NO_SUCH_USER.'</StatusCode>
                        <StatusDetail>No such user</StatusDetail>
                        <DateTime>'.date("Y-m-d\TH:i:s").'</DateTime>
                        <PaymentId>'.ep_GetTransaction($hash).'</PaymentId>
                     </Response>
                    ';
                $reply=trim($reply);
                die($reply);
            }
            
        }
       
    
}
} else {
    die('EMPTY_POST_DATA');
}



?>
