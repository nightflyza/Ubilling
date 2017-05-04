<?php
/*
 * Фронтенд для принятия уведомлений от CoPAYCo о вроде бы успешных транзакциях
 */

require_once './incl/copayco.php';
require_once '../../libs/api.openpayz.php';



/*
 * Check is transaction unique?
 */
function copayco_CheckTransaction($hash) {
    $hash=  loginDB_real_escape_string($hash);
    $query="SELECT `id` from `op_transactions` WHERE `hash`='".$hash."'";
    $data=  simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}


function copayco_perform()
{
    $sRequestType = NULL;
    try {
        $oCPC = copayco_api::instance();

        // Получение ID транзакции для загрузки остальных параметров
        $sTaId = $oCPC->get_ta_id();
        
         // Подготовка основных данных
        $aReq = $oCPC->get_request_data();
        $sRequestType = $oCPC->get_request_type();
        
        //в копейках же
        $nAmount=($aReq['amount']/100);
        $sCurrency=$aReq['currency'];
        $sCustom=$aReq['custom'];


        // Установка основных параметров
        $oCPC->set_main_data($sTaId, $nAmount, $sCurrency);
        // Установка custom-поля (если необходимо)
        $oCPC->set_custom_field($sCustom);
        
        //всякие штуки нужные для OpenPayz
        $hash=$sTaId;
        $customerid=trim($sCustom);
        $summ=$nAmount;
        $paysys='COPAYCO';
        $note=$aReq['currency'].':'.$aReq['amount'].' '.$aReq['custom'].' '.$aReq['payment_mode'];
        $allcustomers=  op_CustomersGetAll();
        $transactionCheck= copayco_CheckTransaction($hash);
        
        if ($sRequestType == 'check') {
            // Проверка полученных данных 
            // ограничимся просто проверкой на существование такого Payment ID и уникальности транзакции.
           if (!isset($allcustomers[$customerid])) {
               $oCPC->set_error_message('Unknown Payment ID');
           }
           
           if (!$transactionCheck) {
               $oCPC->set_error_message('Transaction ID is not unique');
           }
           
        } else {
            if ($aReq['status']=='finished') {
                //о, кажись нам пришли бабки, давайте нарисуем транзакцию для OpenPayz?
                //регистрируем новую транзакцию
                op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                //вызываем обработчики необработанных транзакций
                op_ProcessHandlers();
            } 
        }
      

    } catch (copayco_exception $e) {
        // Обработка ошибок
        $nErrType  = $e->get_error_type_code();
        $nCode = $e->getCode();
        $oCPC->set_error_message($e->getMessage() . ' ' . $nCode);
    }

    // Вывод результатов
    if ($sRequestType == 'perform') {
        $oCPC->output_perform_answer();
    } else {
        $oCPC->output_check_answer();
    }
}


copayco_perform();

//uncomment this section for debugging raw http requests
//file_put_contents('debug2.log', var_export($_POST,true));

?>
