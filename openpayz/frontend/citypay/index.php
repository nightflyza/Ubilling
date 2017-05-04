<?php

/*
 * Фронтенд для получения оплат от CITY-PAY в виде GET запроса
 * Версия API: http://store.nightfly.biz/st/1384958270/Protocol_City-Pay_20v.3.03.01.pdf
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
function cpay_CheckGet($params) {
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
 * Gets last transaction ID from database by hash
 * 
 * @param $hash - transaction hash with prefix
 * 
 * @return int
 */

function cpay_GetTransactionID($hash) {
    $hash = loginDB_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "';";
    $rawData = simple_query($query);
    if (!empty($rawData)) {
        $result = $rawData['id'];
    } else {
        $result = false;
    }
    return ($result);
}

/*
 * Check is transaction unique?
 * 
 * @param $hash - prepared transaction hash with prefix
 * 
 * @return bool
 */

function cpay_CheckTransaction($hash) {
    $hash = loginDB_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

/*
 * find substring into string
 * 
 * @param $string - target string
 * @param $search - needle
 * 
 * @return bool
 */
function cpay_ispos($string,$search) {
      if (strpos($string,$search)===false) {
        return(false);
      } else {
        return(true);
      }
     }
     

/*
 * returns XML formatted transactions by interval in yyyyMMddHHmmss
 * 
 * @param $start - time start
 * @param @end   - time end
 * 
 * @return string
 */

function cpay_GetRevise($start,$end) {
    $start= strtotime($start);
    $start=date('Y:m:d H:i:s',$start);
    $end= strtotime($end);
    $end=date('Y:m:d H:i:s',$end);
    $payments='';
    $query="SELECT * from `op_transactions` WHERE `paysys` = 'CITYPAY' AND `date` BETWEEN '".$start."' AND '".$end."';";
    $rawData=  simple_queryall($query);
    if (!empty($rawData)) {
        foreach ($rawData as $io=>$each) {
            if (cpay_ispos($each['note'], 'date:')) {
                $tmpDate=explode('date:',$each['note']);
                $transDate=trim($tmpDate[1]);
            } else {
                $transDate= strtotime($each['date']);
                $transDate= date("YmdHis",$transDate);
            }
            $cleanHash= str_replace('CPAY_', '', $each['hash']);
            
            $payments.='
            <Payment>
             <TransactionId>'.$cleanHash.'</TransactionId>
             <Account>'.$each['customerid'].'</Account>
             <TransactionDate>'.$transDate.'</TransactionDate>
             <Amount>'.$each['summ'].'</Amount>
            </Payment>
            ';
        }
    }
    
    $result='
        <?xml version="1.0" encoding="UTF-8"?>
        <Response>
        '.$payments.'
        </Response>
        ';
    $result=trim($result);
    print($result);
}




$requiredCheck = array('QueryType', 'TransactionId', 'Account');

//проверяем наличие сильно объязательных параметров
if (cpay_CheckGet($requiredCheck)) {

    $allcustomers = op_CustomersGetAll();
    $hash = $_GET['TransactionId'];
    $hashPrepared = 'CPAY_' . $hash;
    $paysys = 'CITYPAY';
    $customerid = trim($_GET['Account']);

    // Ловим запрос на проверку существования абонента
    if ($_GET['QueryType'] == 'check') {
        if (isset($allcustomers[$customerid])) {
            $resultCode = 0;
        } else {
            $resultCode = 21;
        }

        $reply = '
            <?xml version="1.0" encoding="UTF-8"?>
            <Response>
            <TransactionId>' . $hash . '</TransactionId>
            <ResultCode>' . $resultCode . '</ResultCode>
            <Comment></Comment>
            </Response>';
        $reply = trim($reply);
        die($reply);
    }

    //ловим запрос на пополнение счета
    if ($_GET['QueryType'] == 'pay') {
        if (cpay_CheckGet(array('Amount', 'TransactionDate'))) {
            $amount = $_GET['Amount'];
            $payDate = $_GET['TransactionDate'];
            //если абонент найден по платежному ID регистрируем транзакцию
            if (isset($allcustomers[$customerid])) {
                $note = 'hash:' . loginDB_real_escape_string($hash) . ' date:' . loginDB_real_escape_string($payDate);
                if (cpay_CheckTransaction($hashPrepared)) {
                    //регистрируем новую транзакцию
                    op_TransactionAdd($hashPrepared, $amount, $customerid, $paysys, $note);
                    //вызываем обработчики необработанных транзакций
                    op_ProcessHandlers();
                }
                //выцепляем ее внутренний ID
                $transactionExtID = cpay_GetTransactionID($hashPrepared);
                $resultPayCode = 0;
            } else {
                $resultPayCode = 21;
                $transactionExtID = '';
            }
            $reply = '<?xml version="1.0" encoding="UTF-8"?>
                    <Response>
                    <TransactionId>' . $hash . '</TransactionId>
                    <TransactionExt>' . $transactionExtID . '</TransactionExt>
                    <Amount>' . $amount . '</Amount>
                    <ResultCode>' . $resultPayCode . '</ResultCode>
                    <Comment>:)</Comment>
                    </Response>';
            $reply = trim($reply);
            die($reply);
        } else {
            throw new Exception('EX_NO_REQUIRED_PARAMS');
        }
    }
} else {
    //если это не поиск абонента либо пополнение счета
    //будем считать, что это автоматическая сверка транзакций
    if (cpay_CheckGet(array('CheckDateBegin', 'CheckDateEnd'))) {
        cpay_GetRevise($_GET['CheckDateBegin'],$_GET['CheckDateEnd']);
    }
}
?>
