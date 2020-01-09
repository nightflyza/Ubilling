<?php
/**
 *  Фронтэнд взаимодействия с FC-Sistema (https://fc-sistema.com/)
 *
 *  Реализация протокола: https://wiki.fc-sistema.com/index.php/%D0%9F%D1%80%D0%BE%D1%82%D0%BE%D0%BA%D0%BE%D0%BB_%D0%B2%D0%B7%D0%B0%D1%94%D0%BC%D0%BE%D0%B4%D1%96%D1%97
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

// Define vars
define('FCS_MERCH_ID', 'fcsistema');

// Если: FCS_USE_AGENTCODES = 1 и/или FCS_DEFAULT_AGENTCODE != '' - будет использован режим "с кодами контрагентов"

//Использовать ли внешний кодификатор контрагентов из agentcodes.ini?
define('FCS_USE_AGENTCODES', 0);

// будет передано в <provider_id_s></provider_id_s> - уникальный идентификатор  в системе FcSistema
// в случае если у Провайдера несколько контрагентов и есть необходимость перечисления денежных средств на разных получателей
// указывайте здесь именно идентификатор в системе FcSistema, а не ID из Ubilling
define('FCS_DEFAULT_AGENTCODE', '');

//URL вашего работающего Ubilling
define('FCS_API_URL', 'http://localhost/billing/');
//И его серийный номер
define('FCS_API_KEY', 'UBxxxxxxxxxxxxxxxx');

// Error codes
define('FCS_USER_EXISTS', '21');
define('FCS_PAYMENT_OK', '27');
define('FCS_NO_SUCH_USER', '-40');
define('FCS_ERROR_PAYMENT', '80');
define('FCS_TRANSACTION_NOT_FOUND', '-27');
define('FCS_TRANSACTION_DUPLICATE', '-10');

// mandatory GET parameters
$requiredGETParams = array('cmd', 'merchantid');

/**
 *
 * Check for GET have needed variables
 *
 * @param   $params array of GET variables to check
 * @return  bool
 *
 */
function fcs_CheckGet($params) {
    $result = true;
    if (!empty($params)) {
        foreach ($params as $eachparam) {
            if (isset($_GET[$eachparam])) {
                if (empty($_GET[$eachparam])) {
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
 * Gets user associated agent data JSON
 *
 * @param string $userlogin
 *
 * @return string
 */
function fcs_GetAgentData($userlogin) {
    $action = FCS_API_URL . '?module=remoteapi&key=' . FCS_API_KEY . '&action=getagentdata&param=' . $userlogin;
    @$result = file_get_contents($action);
    return ($result);
}

/**
 * Check is such transaction exists?
 *
 * @param $hash - prepared transaction hash with prefix
 *
 * @return bool|mixed
 */

function fcs_CheckTransactionExists($hash, $returnID = false, $returnTransactData = false) {
    $hash = mysql_real_escape_string($hash);
    $query = "SELECT * from `op_transactions` WHERE `hash` = 'PCS_" . $hash . "'";
    $data = simple_query($query);
    if (empty($data)) {
        return (false);
    } else {
        if ($returnID) {
            return ($data['id']);
        } elseif ($returnTransactData) {
            return ($data);
        } else {
            return (true);
        }
    }
}

/**
 * Gets existing transaction date
 *
 * @param $id - existing transaction id
 * @return bool/datetime
 */
function fcs_GetTransactionTime($id) {
    $id = vf($id, 3);
    $query = "SELECT `date` from `op_transactions` WHERE `id`='" . $id . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        return ($data['date']);
    } else {
        return (false);
    }
}

/**
 * Try to get contragent agent code assigned to $userLogin
 *
 * @param $userLogin
 *
 * @return string
 */
function fcs_GetFCSAgentCode($userLogin) {
    $agentCode = FCS_DEFAULT_AGENTCODE;
    $userAgentData = fcs_GetAgentData($userLogin);
    $agentData = json_decode($userAgentData, true);

    if (!empty($agentData)) {
        $agentsOverrides = parse_ini_file('agentcodes.ini');

        if (FCS_USE_AGENTCODES) {
            if (isset($agentsOverrides[$agentData['id']])) {
                $agentCode = $agentsOverrides[$agentData['id']];
            } else {
                $agentCode = $agentData['id'];
            }
        } else {
            $agentCode = $agentData['id'];
        }
    }

    return ($agentCode);
}

/**
 * Reply to paysys on user payment ID check
 *
 * @param $customerID
 * @param bool $useAgentCodes
 *
 * @return string
 */
function fcs_ReplyCheck($customerID, $useAgentCodes = false) {
    $allcustomers = op_CustomersGetAll();
    $agentCode = '';

    if (isset($allcustomers[$customerID])) {
        $userLogin = $allcustomers[$customerID];

        if ($useAgentCodes) {
            $agentCode = fcs_GetFCSAgentCode($userLogin);
        }

        $companyCode = (empty($agentCode)) ? '' : '<provider_id_s>' . $agentCode . '</provider_id_s>';

        $apiReply = '
<?xml version="1.0" encoding="windows-1251"?>
<response>
<result>' . FCS_USER_EXISTS . '</result>
<text></text>
<comment>Account exist.</comment>
' . $companyCode . '
</response>
                    ';
    } else {
        $apiReply = '
<?xml version="1.0" encoding="windows-1251"?>
<response>
<result>' . FCS_NO_SUCH_USER . '</result>
<text></text>
<comment>Account exist.</comment>                        
</response>
                    ';
    }

    return ($apiReply);
}

/**
 * Reply to paysys on payment event
 *
 * @param $customerID
 * @param $moneyAmount
 * @param $paymentID
 * @param bool $useAgentCodes
 *
 * @return string
 */
function fcs_ReplyPayment($customerID, $moneyAmount, $paymentID, $useAgentCodes = false) {
    $allcustomers = op_CustomersGetAll();
    $transactHash = 'PCS_' . $paymentID;
    $agentCode = '';

    if (isset($allcustomers[$customerID])) {
        $userLogin = $allcustomers[$customerID];

        if ($useAgentCodes) {
            $agentCode = fcs_GetFCSAgentCode($userLogin);
        }

        $companyCode = (empty($agentCode)) ? '' : '<provider_id_s>' . $agentCode . '</provider_id_s>';

        if (fcs_CheckTransactionExists($paymentID)) {
            $apiReply = fcs_ReplyPaymentCheck($paymentID);
        } else {
            //really processin cash & openpayz transaction
            op_TransactionAdd($transactHash, $moneyAmount, $customerID, 'FCSISTEMA', 'FCS payment ID: ' . $paymentID);
            $innerID = simple_get_lastid('op_transactions');
            $transactDT = fcs_GetTransactionTime($innerID);
            op_ProcessHandlers();

            $apiReply = '
<?xml version="1.0" encoding="windows-1251"?>
<response>
<result>' . FCS_PAYMENT_OK . '</result>
<id>' . $paymentID . '</id>
<provider_id>' . $innerID . '</provider_id>
<provider_time>' . $transactDT . '</provider_time>
' . $companyCode . '
<comment>Transaction complete</comment>                
</response>
                        ';
        }
    }

    return ($apiReply);
}

/**
 * Reply to paysys on payment check event
 *
 * @param $paymentID
 *
 * @return string
 */
function fcs_ReplyPaymentCheck($paymentID) {
    $transaction = fcs_CheckTransactionExists($paymentID, false, true);

    if (empty($transaction)) {
        $apiReply = '
<?xml version="1.0" encoding="windows-1251"?>
<response>
<result>' . FCS_TRANSACTION_NOT_FOUND . '</result>                           
<comment>Transaction ID not found: ' . $paymentID . '</comment>                
</response>
                    ';
    } else {
        $apiReply = '
<?xml version="1.0" encoding="windows-1251"?>
<response>
<result>' . FCS_PAYMENT_OK . '</result>
<id>' . $paymentID . '</id>
<provider_id>' . $transaction['id'] . '</provider_id>
<provider_time>' . $transaction['date'] . '</provider_time>                   
<comment>Transaction complete</comment>                
</response>
                    ';
    }

    return ($apiReply);
}


$defaultAgentCode = FCS_DEFAULT_AGENTCODE;
$useAgentCodes = (FCS_USE_AGENTCODES or !empty($defaultAgentCode));

if (fcs_CheckGet($requiredGETParams) and $_GET['merchantid'] == FCS_MERCH_ID) {
    $fcsAPIAction = $_GET['cmd'];

    switch ($fcsAPIAction) {
        case 'verify':
            if (isset($_GET['account'])) {
                $chekReply = fcs_ReplyCheck($_GET['account'], $useAgentCodes);
                die(mb_convert_encoding($chekReply, 'windows-1251'));
            } else {
                die('FCS ERROR: WRONG API VERIFY REQUEST PARAMETERS');
            }

        case 'pay':
            $requiredPaymentParams = array('account', 'sum', 'id');

            if (fcs_CheckGet($requiredPaymentParams)) {
                $paymentReply = fcs_ReplyPayment($_GET['account'], $_GET['sum'], $_GET['id'], $useAgentCodes);
                die(mb_convert_encoding($paymentReply, 'windows-1251'));
            } else {
                die('FCS ERROR: WRONG API PAYMENT REQUEST PARAMETERS');
            }

        case 'check':
            if (isset($_GET['id'])) {
                $chekPaymentReply = fcs_ReplyPaymentCheck($_GET['id']);
                die(mb_convert_encoding($chekPaymentReply, 'windows-1251'));
            } else {
                die('FCS ERROR: WRONG API PAYMENT CHECK REQUEST PARAMETERS');
            }

        default:
            die('FCS ERROR: UNKNOWN/UNSUPPORTED API CMD REQUEST');
    }
} else {
    die('FCS ERROR: REQUIRED API REQUEST PARAMETERS MISSING');
}
