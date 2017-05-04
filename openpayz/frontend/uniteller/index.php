<?php

/*
* Фронтенд для получения оплат от "Uniteller"
*/

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

//подсасываем конфиг Uniteller прямо из настроек бекенда
$confUniteller=parse_ini_file("../../backend/uniteller/config/uniteller.ini");


function checkSignature( $Order_ID, $Status, $Signature ) {
    global $confUniteller;
    $password=$confUniteller['PASSWORD'];
    // проверка подлинности подписи и данных
 return ( $Signature == strtoupper(md5($Order_ID . $Status . $password)) );
}

/*
 * Ну а дальше все по доке, колбасим данные для нашей транзакции
 */
// Пришел callback с параметрами Order_ID, Status, Signature
if ( count($_POST) && isset($_POST["Order_ID"]) && isset($_POST["Status"]) && isset($_POST["Signature"]) ) {
    //debug log writer
    if ($confUniteller['DEBUG_MODE']) {
        file_put_contents("postdata.log", print_r($_POST,true),FILE_APPEND);
    }
// проверка подписи
if ( checkSignature( $_POST["Order_ID"], $_POST["Status"], $_POST["Signature"] ) )
{
// подпись сошлась
  $raw_order=$_POST['Order_ID'];
  $order=  explode('|', $raw_order);
  if (sizeof($order)==4) {
                $hash=$raw_order;
                $summ=$order[2];
                $customerid=$order[1];
                $paysys='UNITELLER';
                $note='order:'.$_POST["Order_ID"].' status:'. $_POST["Status"].' sig:'.$_POST["Signature"];
                
                 //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();
                //все, господа хорошие - транзакция заколбашена
                die('TRANSACTION:DONE');
      
  } else {
      //какой-то странный инвойс прилетел нам в коллбеке
      die('EXEPTION:ORDER_FAIL');
  }
  
} else {
// не сошлась подпись
  die('EXEPTION:SIGNATURE_FAIL');
 }
} else {
    die('EXEPTION:NO_DATA_RECEIVED');
}

?>