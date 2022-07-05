<?php

/**
 * Draft implementation of https://platon.atlassian.net/wiki/spaces/docs/pages/1315733632/Client+-+Server#Callback
 */
error_reporting(E_ALL);
//including required libs
include ("../../libs/api.openpayz.php");

// Send main headers
header('Last-Modified: ' . gmdate('r'));
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache");

/**
 * Reports some error
 * 
 * @param string $data
 * 
 * @return void
 */
function platon_reportError($data) {
    header('HTTP/1.1 400 ' . $data . '"', true, 400);
    die($data);
}

/**
 * Reports some success
 * 
 * @param string $data
 * 
 * @return void
 */
function platon_reportSuccess($data) {
    header('HTTP/1.1 200 ' . $data . '"', true, 200);
    die($data);
}

/**
 * Returns request data
 *
 * @return array
 */
function platon_RequestGet() {
    $result = array();
    if (!empty($_POST)) {
        $result = $_POST;
    }
    return ($result);
}

//                    __
//         .,-;-;-,. /'_\
//       _/_/_/_|_\_\) /
//     '-<_><_><_><_>=/\
//       `/_/====/_/-'\_\
//        ""     ""    ""
//    ^^^ CE CHEREPASHKA ^^^

/**
 * Check is transaction unique?
 * 
 * @param $hash - hash string to check
 * @return bool
 */
function platon_CheckTransaction($hash) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT `id` from `op_transactions` WHERE `hash`='" . $hash . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return (false);
    } else {
        return (true);
    }
}

//processing callback
$requestData = platon_RequestGet();
if (!empty($requestData)) {
    if (is_array($requestData)) {
        if (isset($requestData['id']) AND isset($requestData['order']) AND isset($requestData['description'])) {
            $allCustomers = op_CustomersGetAll();
            $customerId = $requestData['description'];
            if (isset($allCustomers[$customerId])) {
                $summ = $requestData['amount'];
                $paysys = 'PLATON';
                $hash = $paysys . '_' . $requestData['id'];
                $note = $requestData['ip'] . ' ' . $requestData['date'] . ' ' . $requestData['description'];
                if (platon_CheckTransaction($hash)) {
                    if ($requestData['status'] == 'SALE') {
                        op_TransactionAdd($hash, $summ, $customerId, $paysys, $note);
                        op_ProcessHandlers();
                        platon_reportSuccess('Transaction processed');
                    } else {
                        platon_reportError('Unknown callback status');
                    }
                } else {
                    platon_reportSuccess('Transaction processed');
                }
            } else {
                platon_reportError('User not found');
            }
        } else {
            platon_reportError('Required fields not found');
        }
    } else {
        platon_reportError('Callback proceesing error');
    }
} else {
    platon_reportError('Empty callback request');
}