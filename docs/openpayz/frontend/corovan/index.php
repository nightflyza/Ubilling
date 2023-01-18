<?php

/**
 * Just sample frontend intended only for OpenPayz testing
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
function corovan_checkTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

if ((isset($_GET['user'])) AND ( isset($_GET['transactionid'])) AND ( isset($_GET['cash']))) {
    $allcustomers = op_CustomersGetAll();
    $hashRaw = trim($_GET['transactionid']);
    $hash = 'COROVANTEST' . $hashRaw;
    $summ = $_GET['cash'];
    $customerid = trim($_GET['user']);


    if (isset($allcustomers[$customerid])) {
        if (is_numeric($summ)) {
            //maybe already processed?
            if (corovan_checkTransaction($hash)) {
                op_TransactionAdd($hash, $summ, $customerid, 'COROVAN', '');
                op_ProcessHandlers();
                $reply = $hashRaw . ':OK';
            } else {
                $reply = $hashRaw . ':DONE';
            }
        } else {
            $reply = $hashRaw . ':OK';
        }
    } else {
        $reply = $hashRaw . ':USER_NOT_FOUND';
    }
} else {
    $reply = 'ERROR:NOT_ENOUGH_PARAMS';
}

die($reply);
