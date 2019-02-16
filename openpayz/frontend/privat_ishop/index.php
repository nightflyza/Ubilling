<?php
/*
$filename = dirname(__FILE__). '/debug.txt';
if (!empty($_POST)) {
$dh = fopen ($filename,'a+');
fwrite($dh, var_export($_POST,true));
fclose($dh);
}
*/

/*
 * Фронтенд платежной системы Приват24 получающий ответы в виде POST
 */

//достаем конфиг
$pbConf = parse_ini_file('config/privat.ini');

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
function pb_CheckPost($params) {
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

function pb_CheckTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

//пытаемся ловить объязательные параметры от Приват24
if (pb_CheckPost(array('payment', 'signature'))) {
    $merchant_id = $pbConf['MERCHANT_ID'];
    $merchant_pass = $pbConf['SIGNATURE'];
    $signature = sha1(md5($_POST['payment'].$merchant_pass));

    if($_POST['signature'] == $signature) {

        //разбираем на куски пойманный XML
        parse_str($_POST['payment'], $output_arr);

        if($pbConf['MERCHANT_ID'] == $output_arr['merchant']) {
            $hash = $output_arr['order'];
            $customerid = $output_arr['ext_details'];
            $summ = $output_arr['amt'];
            $status = $output_arr['state'];
            $date_arr = str_split($output_arr['date'], 6);
            $date = implode("-", str_split($date_arr[0], 2));
            $time = implode(":", str_split($date_arr[1], 2));
            $paysys = 'PRIVAT24';
            $note = $output_arr['details'] . ' date: ' . $date . ' ' . $time;
            if ($status == 'ok' OR $status == 'test') {
                if (pb_CheckTransaction($hash)) {
                    $allcustomers = op_CustomersGetAll();
                    if (isset($allcustomers[$customerid])) {
                        // Вычисление комиссии
                        $addCommission = (isset($pbConf['ADD_COMMISSION'])) ? $pbConf['ADD_COMMISSION'] : 1;
                        $summ = round(($summ / $addCommission), 2); //Зачисляем сумму без процентов
                        //регистрируем новую транзакцию
                        op_TransactionAdd($hash, $summ, $customerid, $paysys, $note);
                        //вызываем обработчики необработанных транзакций
                        op_ProcessHandlers();
                        //тихонько помираем
                        die('TRANSACTION_OK');
                    } else {
                        die('ERROR_NO_SUCH_USER');
                    }
                }
            }
        } else {
            die('ERROR_MERCHANT_ID');
        }
    } else {
        die('ERROR_EMPTY_XML');
    }
} else {
    die('ERROR_NO_POST_DATA');
}
?>