<?php

/**
 * Just sample frontend intended for OpenPayz payments import
 * 
 * Sample GET request: ?login=sometestuser&cash=666&reason=07:24:08 10.05.2023;6226642993;126.00;100&timestamp=1683692702
 */
include ("../../libs/api.openpayz.php");

$reply = '';

/**
 * Check is transaction unique?
 * 
 * @param string $hash - hash string to check
 * 
 * @return bool
 */
function paygate_checkTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

/**
 * Checks is all of variables array present in GET scope
 * 
 * @param array/string $params array of variable names to check or single variable name as string
 * @param bool  $ignoreEmpty ignore or not existing variables with empty values (like wf_Check)
 * 
 * @return bool
 */
function checkGet($params, $ignoreEmpty = true) {
    if (!empty($params)) {
        if (!is_array($params)) {
            //single param check
            $params = array($params);
        }
        foreach ($params as $eachparam) {
            if (!isset($_GET[$eachparam])) {
                return (false);
            }
            if ($ignoreEmpty) {
                if (empty($_GET[$eachparam])) {
                    return (false);
                }
            }
        }
        return(true);
    } else {
        throw new Exception('EX_PARAMS_EMPTY');
    }
}

$requiredFields = array('login', 'cash', 'reason', 'timestamp');

if (checkGet($requiredFields)) {
    $userLogin = trim($_GET['login']);
    $summ = trim($_GET['cash']);
    $reason = trim($_GET['reason']);
    $timestamp = trim($_GET['timestamp']);

    $allCustomers = op_CustomersGetAll();
    $reverseCustomers = array_flip($allCustomers);
    $hash = 'PAYGATE:' . $userLogin . '|' . $summ . '|' . $reason . '|' . $timestamp;


    if (isset($reverseCustomers[$userLogin])) {
        $paymentId = $reverseCustomers[$userLogin];
        if (is_numeric($summ)) {
            //maybe already processed?
            if (paygate_checkTransaction($hash)) {
                op_TransactionAdd($hash, $summ, $paymentId, 'PAYGATE', '');
                op_ProcessHandlers();
                $reply = array('error' => 0, 'message' => 'Success');
            } else {
                $reply = array('error' => 0, 'message' => 'Already processed');
            }
        } else {
            $reply = array('error' => 2, 'message' => 'Wrong cash format');
        }
    } else {
        $reply = array('error' => 1, 'message' => 'User not found');
    }
} else {
    $reply = array('error' => 666, 'message' => 'Payment data not received');
}

header('Content-Type: application/json; charset=UTF-8');
die(json_encode($reply));
