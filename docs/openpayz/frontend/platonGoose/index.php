<?php

/**
 * Draft implementation of https://platon.atlassian.net/wiki/spaces/docs/pages/1315733632/Client+-+Server#Callback
 * Модуль написаний для логіки модуля "Опір Гусака".
 * Вам необхіжно в вашому білінгу прописати в "Додаткова інформації" "Господорюючого суб'єкта" "Ім`я платіжної системи" у вигляді префіксу "PLATONM_".
 * для кожного "Господорюючого суб'єкта" "Ім`я платіжної системи" повинно бути унікальним, наприклад: "PLATONM_1" для іншого "Господорюючого суб'єкта" "PLATONM_UBILL" і т.д.
 * Всі дані для бекенду також беруться з білінгу, і далі фронтенд оброблює їх зі своєї сторони.
 */

error_reporting(E_ALL);

//external service payment percent: (float for external payment, 0 - disabled)
const SERVICE_PAYMENT_PERCENT = 0;

//URL вашего работающего Ubilling
define('API_URL', 'http://localhost/billing/');
//И его серийный номер
define('API_KEY', 'UBxxxxxxxxxx');
// Префікс платіжної системи в білінгу для роширеної інформації по агентам
define('PAYSYS_PREFIX', 'PLATONM' . '_');

//including required libs
include("../../libs/api.openpayz.php");

// Send main headers
header('Last-Modified: ' . gmdate('r'));
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache");

/**
* Returns user's assigned agent extended data, if available
*
* @param $gentID
*
* @return array|empty
*/
function getGoosData($customerId, $amountRaw = '') {
    $baseUrl = API_URL . '?module=remoteapi&key=' . API_KEY . '&action=goose';
    $callbackUrl = $baseUrl . '&amount=' . $amountRaw . '&paymentid=' . $customerId;
    $gooseResult = @file_get_contents($callbackUrl);
    
    return ($gooseResult);
}

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
        if (isset($requestData['id']) and isset($requestData['order']) and isset($requestData['description'])) {
            $allCustomers = op_CustomersGetAll();
            $customerId = $requestData['description'];
            if (isset($allCustomers[$customerId])) {
                    $summRaw = $requestData['amount'];
                    $summ = $summRaw;
                    if (SERVICE_PAYMENT_PERCENT) {
                        $summ = $summ / (1 + (SERVICE_PAYMENT_PERCENT / 100));
                    }
                    $paysys = 'PLATON';
                    $hash = $paysys . '_' . $requestData['id'];
                    $note = $requestData['ip'] . ' (' . $requestData['date'] . ') [rawsumm: ' . $summRaw . ' | paysumm:' . $summ . ' ] ' . $requestData['description'];
                    if (platon_CheckTransaction($hash)) {
                        if ($requestData['status'] == 'SALE') {
                            if (isset($requestData['ext1']) and $requestData['ext1']) {
                                $sRulesArr = json_decode($requestData['ext1'], true);
                                if (!empty($sRulesArr)) {
                                    if ($summRaw == array_sum($sRulesArr)) {
                                        $gooseResult = getGoosData($customerId);
                                        if (!empty($gooseResult)) {
                                            $gooseResult = json_decode($gooseResult);
                                            if (!empty($gooseResult)) {
                                                $agentsExtInfo = preg_grep("/^" . PAYSYS_PREFIX . ".+/", array_column((array)$gooseResult->agentsextinfo, 'internal_paysys_name', 'id'));
                                                if (!empty($agentsExtInfo)) {
                                                    $billPayStatus = FALSE;
                                                    foreach ($agentsExtInfo as $id => $paysysPrefix) {
                                                        $agentId = $gooseResult->agentsextinfo->{$id}->agentid;
                                                        $edrpo = $gooseResult->agents->{$agentId}->edrpo;
                                                        $ipn = $gooseResult->agents->{$agentId}->ipn;
                                                        if (isset($sRulesArr[$edrpo]) or isset($sRulesArr[$ipn])) {
                                                            $summ = isset($sRulesArr[$edrpo]) ? $sRulesArr[$edrpo] : $sRulesArr[$ipn];
                                                            if (SERVICE_PAYMENT_PERCENT) {
                                                                $summ = $summ / (1 + (SERVICE_PAYMENT_PERCENT / 100));
                                                            }
                                                            $paysys = $paysysPrefix;
                                                            $hash = $paysys . '_' . $requestData['id'];
                                                            if (platon_CheckTransaction($hash)) {
                                                                op_TransactionAdd($hash, $summ, $customerId, $paysys, $note);
                                                                op_ProcessHandlers();
                                                                $billPayStatus = TRUE;
                                                            }
                                                        }
                                                    }
                                                    if ($billPayStatus) {
                                                        platon_reportSuccess('Transaction processed');
                                                    } else {
                                                        platon_reportError('Billing procces fail');
                                                    }
                                                } else {
                                                    platon_reportError('Critical error. No advanced information found for agents');
                                                }
                                            } else {
                                                platon_reportError('Goose resistance could not find information about the subscriber');
                                            }
                                        } else {
                                            platon_reportError('Goose resistance could not find information about the subscriber');
                                        }
                                    } else {
                                        platon_reportError('The sum of the splits does not equal the total');
                                    }
                                } else {
                                    platon_reportError('Incorrect format of additional field for split identification');
                                }
                            } else {
                                // Do payments without additional  information
                                op_TransactionAdd($hash, $summ, $customerId, $paysys, $note);
                                op_ProcessHandlers();
                                platon_reportSuccess('Transaction processed');
                            }
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
