<?php

// Фронтенд для приема платежей fondy.eu
// https://www.fondy.eu/en/info/api

// подключаем API OpenPayz

include ("../../libs/api.openpayz.php");


//Пытаемся поймать JSON прямым POST-ом 
$rawRequest = file_get_contents("php://input");


/**
 * Check is transaction unique?
 * 
 * @param $hash - transaction hash
 * 
 * @return bool
 */
function opl_CheckTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

if (!empty($rawRequest)) {
    $requestData = json_decode($rawRequest);
    // print_r($requestData);
    if (!empty($requestData)) {
        if (isset($requestData->amount)) {

            if (isset($requestData->merchant_data)) {
                $merchantDataRaw = $requestData->merchant_data;
                $merchantData = json_decode($merchantDataRaw);
                if (isset($merchantData[0])) {
                    $merchantData = $merchantData[0];
                    if ($merchantData->name == 'paymentid') {
                        $customerId = $merchantData->value;
                        $allcustomers = op_CustomersGetAll();
                        if (isset($allcustomers[$customerId])) {
                            $paysys = 'FONDY';
                            $hash = 'OPL_' . $requestData->payment_id;
                            $summ = $requestData->amount / 100; // деньги то в копейках
                            $orderStatus = $requestData->order_status;
                            //а нету ли уже такой транзакции?
                            if (opl_CheckTransaction($hash)) {
                                //проверяем состояние платежа
                                if ($orderStatus == 'approved') {
                                    //регистрируем новую транзакцию
                                    op_TransactionAdd($hash, $summ, $customerId, $paysys, 'NOPE');
                                    //вызываем обработчики необработанных транзакций
                                    op_ProcessHandlers();
                                }
                            } else {
                                //здесь по логике должен быть ответ о том, что это дубль
                            }
                        } else {
                            // тут должен быть вопль о том, что невалидный пользователь aka merchantData->value
                        }
                    }
                }
            }
        }
    }
}
?>