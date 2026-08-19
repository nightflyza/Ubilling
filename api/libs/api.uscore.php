<?php


/**
 * Emits uscore JSON reply and stops execution
 *
 * @param string $operation
 * @param string $login
 * @param string $value
 * @param bool $error
 * @param string $errorMessage
 *
 * @return void
 */
function uscoreReply($operation, $login, $value, $error, $errorMessage = '') {
    $replyValue = $value;
    if (($operation == 'setpassword') or ($operation == 'userauthcheck')) {
        $replyValue = '';
    }

    header('Content-Type: application/json; charset=UTF-8');
    $result = json_encode(array(
        'operation' => $operation,
        'login' => $login,
        'value' => $replyValue,
        'error' => $error,
        'error_message' => $errorMessage
    ));
    die($result);
  
}

/**
 * Resets user like the reset module
 *
 * @param string $login
 *
 * @return void
 */
function uscoreDoReset($login) {
    global $billing, $ubillingConfig;
    $billing->resetuser($login);
    log_register('USCORE RESET (' . $login . ')');
    if ($ubillingConfig->getAlterParam('RESETHARD')) {
        zb_UserResurrect($login);
    }
}

/**
 * Changes user MAC like the macedit module
 *
 * @param string $login
 * @param string $mac
 *
 * @return string
 */
function uscoreDoSetMac($login, $mac) {
    global $billing, $ubillingConfig;
    $result = '';
    $mac = trim($mac);
    if ($mac == '') {
        $result = 'PARAMS_MISSED';
    } else {
        if (!check_mac_format($mac)) {
            $result = 'INVALID_MAC';
            log_register('USCORE MACINVALID TRY (' . $login . ')');
        } else {
            $ip = zb_UserGetIP($login);
            if (empty($ip)) {
                $result = 'NO_IP';
            } else {
                $allUsedMacs = zb_getAllUsedMac();
                if (!zb_checkMacFree($mac, $allUsedMacs)) {
                    $result = 'MAC_IN_USE';
                    log_register('USCORE MACDUPLICATE TRY (' . $login . ') `' . $mac . '`');
                } else {
                    $oldMac = zb_MultinetGetMAC($ip);
                    $userData = zb_UserGetAllData($login);
                    $userData = $userData[$login];
                    multinet_change_mac($ip, $mac);
                    if ($ubillingConfig->getAlterParam('MULTIGEN_ENABLED')) {
                        $newUserData = $userData;
                        $newUserData['mac'] = strtolower($mac);
                        $mlg = new MultiGen();
                        if ($ubillingConfig->getAlterParam('MULTIGEN_POD_ON_MAC_CHANGE') == 2) {
                            $mlg->podOnExternalEvent($login, $userData, $newUserData);
                            $mlg->podOnExternalEvent($login, $newUserData);
                        }
                        if ($ubillingConfig->getAlterParam('MULTIGEN_POD_ON_MAC_CHANGE') == 1) {
                            $mlg->podOnExternalEvent($login, $newUserData);
                        }
                    }
                    log_register('USCORE MAC CHANGE (' . $login . ') ' . $ip . ' FROM  `' . $oldMac . '` ON `' . $mac . '`');
                    multinet_rebuild_all_handlers();
                    $billing->resetuser($login);
                    log_register('USCORE RESET (' . $login . ')');
                    if ($ubillingConfig->getAlterParam('RESETHARD')) {
                        zb_UserResurrect($login);
                    }
                    if ($ubillingConfig->getAlterParam('MACCHGDOUBLEKILL')) {
                        $billing->resetuser($login);
                        log_register('USCORE RESET (' . $login . ') DOUBLEKILL');
                    }
                }
            }
        }
    }
    return ($result);
}