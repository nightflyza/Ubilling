<?php

//file_put_contents('debug.log', file_get_contents("php://input"),FILE_APPEND | LOCK_EX);
// подключаем API OpenPayz

include ("../../libs/api.openpayz.php");

$rawRequest = file_get_contents("php://input");
//$rawRequest=  file_get_contents('debug.log');

if (!empty($rawRequest)) {
    parse_str($rawRequest, $requestData);
    if (!empty($requestData)) {
        if (isset($requestData['amount'])) {
            if (isset($requestData['merchant_data'])) {
                $merchantDataRaw = stripslashes($requestData['merchant_data']);
                $merchantData = json_decode($merchantDataRaw);
                if (isset($merchantData[0])) {
                    if ($merchantData[0]->name == 'paymentid') {
                        $customerId = $merchantData[0]->value;
                        $allcustomers = op_CustomersGetAll();
                        if (isset($allcustomers[$customerId])) {
                            $paysys = 'OPLATA';
                            $hash = 'OPLT_' . $requestData['payment_id'];
                            $summ = $requestData['amount'] / 100; // деньги то в копейках
                            //регистрируем новую транзакцию
                            op_TransactionAdd($hash, $summ, $customerId, $paysys, 'NOPE');
                            //вызываем обработчики необработанных транзакций
                            op_ProcessHandlers();
                        }
                    }
                }
            }
        }
    }
}
?>