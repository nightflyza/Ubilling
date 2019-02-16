<?php

/*
 * Фронтенд для получения оплат от CITY24 в виде GET запроса
 * Версия API: Protocol_City-Pay_20v.3.03.02.pdf
 */

//Использовать ли внешний кодификатор контрагентов из agentcodes.ini?
define('CPAYX_USE_AGENTCODES', 1);

//URL вашего работающего Ubilling
define('API_URL', 'http://127.0.0.1/billing/');

//И его серийный номер
define('API_KEY', 'UB0000000000000000000000');


error_reporting(E_ALL);

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
    $hash = mysql_real_escape_string($hash);
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
    $hash = mysql_real_escape_string($hash);
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
     
/**
 * Returns all user RealNames
 * 
 * @return array
 */
function cpay_UserGetAllRealnames() {
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


//ищем агента

if (!empty($agentData)) {
                $agentData = json_decode($agentData, true);
                if (!empty($agentData)) {
                    $agentCode = '';
                    $agentsOverrides = parse_ini_file('agentcodes.ini');
                    if (IBX_USE_AGENTCODES) {
                        if (isset($agentsOverrides[$agentData['id']])) {
                            $agentCode = $agentsOverrides[$agentData['id']];
                        } else {
                            $agentCode = $agentData['id'];
                        }
                    } else {
                        $agentCode = $agentData['id'];
           }
    }
    return($fioz);
}





//Выбираем все адреса в формате Ubilling
function cpay_AddressGetFulladdresslist() {
$result=array();
$apts=array();
$builds=array();
//наглая заглушка
$alterconf['ZERO_TOLERANCE']=0;
$alterconf['CITY_DISPLAY']=0;
$city_q="SELECT * from `city`";
$adrz_q="SELECT * from `address`";
$apt_q="SELECT * from `apt`";
$build_q="SELECT * from build";
$streets_q="SELECT * from `street`";
$alladdrz=simple_queryall($adrz_q);
$allapt=simple_queryall($apt_q);
$allbuilds=simple_queryall($build_q);
$allstreets=simple_queryall($streets_q);
if (!empty ($alladdrz)) {
   
        foreach ($alladdrz as $io1=>$eachaddress) {
        $address[$eachaddress['id']]=array('login'=>$eachaddress['login'],'aptid'=>$eachaddress['aptid']);
        }
        foreach ($allapt as $io2=>$eachapt) {
        $apts[$eachapt['id']]=array('apt'=>$eachapt['apt'],'buildid'=>$eachapt['buildid']);
        }
        foreach ($allbuilds as $io3=>$eachbuild) {
        $builds[$eachbuild['id']]=array('buildnum'=>$eachbuild['buildnum'],'streetid'=>$eachbuild['streetid']);
        }
        foreach ($allstreets as $io4=>$eachstreet) {
        $streets[$eachstreet['id']]=array('streetname'=>$eachstreet['streetname'],'cityid'=>$eachstreet['cityid']);
        }

    foreach ($address as $io5=>$eachaddress) {
        $apartment=$apts[$eachaddress['aptid']]['apt'];
        $building=$builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
        $streetname=$streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
        $cityid=$streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
        // zero apt handle
        if ($alterconf['ZERO_TOLERANCE']) {
            if ($apartment==0) {
            $apartment_filtered='';
            } else {
            $apartment_filtered='/'.$apartment;
            }
        } else {
        $apartment_filtered='/'.$apartment;    
        }
    
        if (!$alterconf['CITY_DISPLAY']) {
        $result[$eachaddress['login']]=$streetname.' '.$building.$apartment_filtered;
        } else {
        $result[$eachaddress['login']]=$cities[$cityid].' '.$streetname.' '.$building.$apartment_filtered;
        }
    }
}

return($result);
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
    $query="SELECT * from `op_transactions` WHERE `paysys` = 'CITY24' AND `date` BETWEEN '".$start."' AND '".$end."';";
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
            $cleanHash= str_replace('CITY24_', '', $each['hash']);
            
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
    $hashPrepared = 'CITY24_' . $hash;
    $paysys = 'CITY24';
    $customerid = trim($_GET['Account']);

    // Ловим запрос на проверку существования абонента
    if ($_GET['QueryType'] == 'check') {
        if (isset($allcustomers[$customerid])) {
            $resultCode = 0;
            $customerLogin = $allcustomers[$customerid];
            $userlogin = $allcustomers[$customerid];
            $userData = simple_query("SELECT * from `users` WHERE `login`='" . $userlogin . "'");
            $allrealnames = cpay_UserGetAllRealnames();
            $alladdress = cpay_AddressGetFulladdresslist();
            //$agentData = getAgentData($customerLogin);
          // if (!empty($agentData)) {
              //  $agentData = json_decode($agentData, true);
             //  if (!empty($agentData)) {
                  //  $agentCode = '';
                  //  $agentsOverrides = parse_ini_file('agentcodes.ini');
                   // if (CPAYX_USE_AGENTCODES) {
                      //  if (isset($agentsOverrides[$agentData['id']])) {
                         //   $agentCode = $agentsOverrides[$agentData['id']];
                       // } else {
                        //    $agentCode = $agentData['id'];
                      //  }
                   // } else {
                     //   $agentCode = $agentData['id'];

                         $resultCode = 0;
        } else {
            $resultCode = 21;
        }

        $reply = '
            <?xml version="1.0" encoding="UTF-8"?>
            <Response>
            <TransactionId>' . $hash . '</TransactionId>
            <ResultCode>' . $resultCode . '</ResultCode>
            <fields>
                   <field1 name="balance">' . @$userData['Cash'] . '</field1>
 // это когда включаю дает ошибку                 <field2 name="LegalCode">' . $agentCode . '</field2>
                   <field3 name="name">' . @$allrealnames[$userlogin] . '</field3>
                   <field4 name="address">' . @$alladdress[$userlogin] . '</field4>
            </fields>
            <Comment>OK</Comment>
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
                $note = 'hash:' . mysql_real_escape_string($hash) . ' date:' . mysql_real_escape_string($payDate);
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
