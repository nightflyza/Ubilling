<?php

/*
 * Фронтенд платежной системы LiqPay получающий ответы в виде POST XML 
 * согласно протокола: https://www.liqpay.ua/documentation/api/aquiring/checkout/
 */

//достаем конфиг
$liqConf = parse_ini_file('config/liqpay.ini');

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
if (lq_CheckPost(array('data', 'signature'))) {
    $data = $_POST['data'];
    $reqSig = $_POST['signature'];
    $private_key = $liqConf['SIGNATURE'];
    $signature = base64_encode(sha1($private_key . $data . $private_key, 1));
    if ($reqSig == $signature) {
        $data_decoded = base64_decode($data);

        if (!empty($data_decoded)) {
            $data_js = json_decode($data_decoded);
            if (!json_last_error()) {
                if ($data_js->status == 'success') {
                    $hash = $data_js->order_id;
                    $customerid = $data_js->description;
                    $summ = $data_js->amount;
                    $addCommission = (isset($liqConf['ADD_COMMISSION'])) ? $liqConf['ADD_COMMISSION'] : 1;
                    $summ = round(($summ / $addCommission), 2); //Зачисляем сумму без процентов
                    $paysys = "LIQPAY";
                    $note = "TRANSACTION ID: " . $data_js->transaction_id;
                    if (lq_CheckTransaction($hash)) {
                        $allcustomers = op_CustomersGetAll();
                        if (isset($allcustomers[$customerid])) {
                            //регистрируем новую транзакцию
                            op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
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
                }
            } else {
                die('ERROR_INVALID_JSON_DATA');
            }
        } else {
            die('ERROR_EMPTY_DATA');
        }
    } else {
        die('MISSING_SIGNATURE');
    }
} else {
    die('ERROR_NO_POST_DATA');
}
?>