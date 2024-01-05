<?php

/**
 * Фронтенд для получения платежей от платежной системы Ukrpays посредством POST запросов
 * Документация по протоколу: http://store.nightfly.biz/st/1526994620/UKRPAYS_API.2.5.4.pdf
 */

/**
 * Check for POST have needed variables
 *
 * @param   $params array of POST variables to check
 * @return  bool
 *
 */
function ups_CheckPost($params) {
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

/**
 * Checks is reference unique?
 *
 * @param int $rawhash reference number to check
 *
 * @return bool
 */
function ups_CheckHash($rawHash) {
    $rawhash = mysql_real_escape_string($rawHash);
    $hash = 'UPAYS_' . $rawHash;
    $query = "SELECT * from `op_transactions` WHERE `hash`='" . $hash . "';";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

//cathing payment request
if (ups_CheckPost(array('id_ups', 'order', 'amount', 'date', 'system'))) {
    $rawHash = $_POST['id_ups'];
    $hash = 'UPAYS_' . $rawHash;
    $customerId = $_POST['order'];
    $paysys = 'UKRPAYS';
    $summ = $_POST['amount'];
    $note = 'Payment method: ' . $_POST['system'] . ' timestamp: ' . $_POST['date'];
    $allCustomers = op_CustomersGetAll();

    //checking is hash unique?
    if (ups_CheckHash($rawHash)) {
        //checking for existing user
        if (isset($allCustomers[$customerId])) {
            op_TransactionAdd($hash, $summ, $customerId, $paysys, $note);
            op_ProcessHandlers();
            die('OK');
        } else {
            //no such uuser here
            die('usernotfound');
        }
    } else {
        //transaction already processed
        die('duplicate');
    }
}
?>