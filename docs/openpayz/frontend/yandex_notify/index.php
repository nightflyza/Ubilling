<?php

/*
 * Фронтенд платежной системы YandexMoney получающий ответы в виде POST 
 * согласно протокола: https://tech.yandex.ru/money/doc/payment-buttons/reference/notifications-docpage/
 */

//достаем конфиг
$liqConf = parse_ini_file('config/yandex.ini');

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

/**
 *
 * Check for POST have needed variables
 *
 * @param   $params array of POST variables to check
 * @return  bool
 *
 */
function lq_CheckPost($params) {
    $result = true;
    if (!empty($params)) {
        foreach ($params as $eachparam) {
            if (isset($_POST[$eachparam])) {
                if (empty($_POST[$eachparam])) {
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
 * Check is transaction unique?
 * 
 * @param $hash - transaction hash
 * 
 * @return bool
 */

function lq_CheckTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

//пытаемся ловить объязательные параметры от LiqPay
if (lq_CheckPost(array('operation_id', 'sha1_hash', 'label'))) {
    $data = $_POST['data'];
    $sha1_hash = $_POST['sha1_hash'];
    $notification_secret = $liqConf['SECRET_KEY'];
    $notification_type = $_POST['notification_type'];
    $operation_id = $_POST['operation_id'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $datetime = $_POST['datetime'];
    $sender = $_POST['sender'];
    $codepro = $_POST['codepro'];
    $customer_id = $_POST['label'];
    $signature = sha1("$notification_type&$operation_id&$amount&$currency&$datetime&$sender&$codepro&$notification_secret&$customer_id");
    if ($sha1_hash == $signature) {
            //$summ = $_POST['amount']; //Сумма, которая зачислена на счет получателя.
            $summ = $_POST['withdraw_amount']; //Сумма, которая списана со счета отправителя.
            $paysys = "YandexMoney";
            $note = "TRANSACTION ID: " . $operation_id;
            if (lq_CheckTransaction($operation_id)) {
                $allcustomers = op_CustomersGetAll();
                if (isset($allcustomers[$customer_id])) {
                    //регистрируем новую транзакцию
                    op_TransactionAdd($operation_id, $summ, $customer_id, $paysys, $note);
                    //вызываем обработчики необработанных транзакций
                    op_ProcessHandlers();
                    //тихонько помираем
                    die('TRANSACTION_OK');
                } else {
                    die('ERROR_NO_SUCH_USER');
                }
            } else {
                die('DOUBLE_PAYMENT');
            }
    } else {
        die('MISSING_HASH');
    }
} else {
    die('ERROR_NO_POST_DATA');
}
?>