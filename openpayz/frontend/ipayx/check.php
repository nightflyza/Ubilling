<?php

//including required libs
include ("../../libs/api.openpayz.php");

/**
 * Renders check reply
 * 
 * @param bool $state
 * 
 * @return string
 */
function replyCheck($state) {
    $checkCode = ($state) ? 1 : -1;
    $checkText = ($state) ? 'OK' : 'FAIL';
    $result = '<?xml version="1.0" encoding="UTF-8"?>
                    <response>
                    <code>' . $checkCode . '</code>
                    <desc>' . $checkText . '</desc>
                    <datetime>' . date("Y-m-d\TH:i:s") . '</datetime>
                    <salt></salt>
                    </response>
               ';
    $result = trim($result);
    return ($result);
}

$rawXml = @$_POST['xml'];

if (!empty($rawXml)) {
    $xmlCheck = xml2array($rawXml);
    if (!empty($xmlCheck)) {
        if (isset($xmlCheck['check'])) {
            if (isset($xmlCheck['check']['pay_account'])) {
                $checkCustomerId = $xmlCheck['check']['pay_account'];
                $allCustomers = op_CustomersGetAll();
                $checkResult = (isset($allCustomers[$checkCustomerId])) ? true : false;
                die(replyCheck($checkResult));
            } else {
                die('WRONG REQUEST FORMAT: missing pay_account section');
            }
        } else {
            die('WRONG REQUEST FORMAT: missing check section');
        }
    } else {
        die('EMPTY CHECK REQUEST: xml parse error');
    }
} else {
    die('EMPTY CHECK REQUEST: no data received');
}

