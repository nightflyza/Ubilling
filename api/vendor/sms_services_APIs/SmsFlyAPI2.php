<?php

class SmsFlyAPI2 extends SMSServiceApi {

    /**
     * Contains country code for target country
     *
     * @var string
     */
    protected $countryCode = '38';

    public function __construct($smsServiceId, $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        $result = '';
        $smsHistoryEnabled = $this->ubConfig->getAlterParam('SMS_HISTORY_ON');
        $smsAdvancedEnabled = $this->instanceSendDog->getUBConfigInstance()->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
        $telepatia = new Telepathy(false);

        if ($smsHistoryEnabled) {
            $telepatia->flushPhoneTelepathyCache();
            $telepatia->usePhones();
        }

        $allSmsQueue = $this->smsMessagePack;
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $io => $eachsms) {
                $params = array(
                    "action" => "SENDMESSAGE",
                    "data" => array(
                        "recipient" => $this->checkPhone($eachsms['number']),
                        "channels" => array("sms"),
                        "sms" => array(
                            "source" => $this->serviceAlphaName,
                            "text" => $eachsms['message']
                        )
                    )
                );
                // Send SMS
                $responce = $this->apiquery($params);
                //remove old sent message
                $this->instanceSendDog->getSmsQueueInstance()->deleteSms($eachsms['filename']);

                if ($smsHistoryEnabled) {
                    if ($responce) {
                        $messageStatusCode = $responce['data']['sms']['status'];
                        $decodedMessageStatus = $this->decodeStatusMsg($messageStatusCode);
                        $smsMsgId = $responce['data']['messageID'];
                        $sessionID = strtoupper(md5(uniqid(rand(), true)));
                        $Login = $telepatia->getByPhoneFast($eachsms['number']);
                        if ($smsAdvancedEnabled) {
                            $query = "INSERT INTO `sms_history` (`smssrvid`, `login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `send_status`, `msg_text`, `date_send`)
                                             VALUES (" . $this->serviceId . ", '" . $Login . "', '" . $eachsms['number'] . "', '" . $smsMsgId . "', '" . $sessionID . "', '" . $decodedMessageStatus['DeliveredStatus'] . "', '" . $eachsms['message'] . "', '" . curdatetime() . "');";
                        } else {
                            $query = "INSERT INTO `sms_history` (`login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `send_status`, `msg_text`, `date_send`)
                                             VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '" . $smsMsgId . "', '" . $sessionID . "', '" . $decodedMessageStatus['DeliveredStatus'] . "', '" . $eachsms['message'] . "', '" . curdatetime() . "');";
                        }
                        nr_query($query);
                    }
                }
            }
        }
    }

    /**
     * Renders current SMS-Fly service user balance
     * 
     * @return string
     */
    public function getBalance() {
        $result = '';
        $balance = '';

        $params = array("action" => "GETBALANCE");
        $responce = $this->apiquery($params);

        if ($responce) {
            $balance = $responce['data']['balance'];
        }

        $result .= $this->instanceSendDog->getUBMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $balance, 'info');
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
    }

    protected function apiquery(array $params) {
        $params['auth'] = array(
            'key' => $this->serviceApiKey,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->serviceGatewayAddr);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, 256));
        $result = curl_exec($ch);
        curl_close($ch);

        if (!empty($result)) {
            $response = json_decode($result, true);
            if ($response['success'] == 1) {
                return $response;
            } else {
                $decodedMessageStatus = $this->decodeStatusMsg($response['error']['code']);
                log_register('SENDDOG SMSFLYAPI2 ERROR failed. Server answer: ' . $decodedMessageStatus['StatusMsg']);
                return false;
            }
        } else {
            log_register('SENDDOG SMSFLYAPI2 ERROR for use API');
            return false;
        }
    }

    /**
     * Checks messages status for SMS-Fly service
     *
     * @return void
     */
    public function checkMessagesStatuses() {
        if ($this->isDefaultService) {
            $query = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND (`smssrvid` = " . $this->serviceId . " OR `smssrvid` = 0);";
        } else {
            $query = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND `smssrvid` = " . $this->serviceId . ";";
        }
        $checkMessages = simple_queryall($query);

        if (!empty($checkMessages)) {
            foreach ($checkMessages as $io => $eachmessage) {
                $params = array(
                    "action" => "GETMESSAGESTATUS",
                    "data" => array(
                        "messageID" => $eachmessage['srvmsgself_id']
                    )
                );
                // Check SMS Status
                $responce = $this->apiquery($params);

                if ($responce) {
                    $messageStatusCode = $responce['data']['sms']['status'];
                    $decodedMessageStatus = $this->decodeStatusMsg($messageStatusCode);

                    $query = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                       `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                       `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                       `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                        WHERE `srvmsgself_id` = '" . $eachmessage['srvmsgself_id'] . "';";
                    nr_query($query);
                }
            }
        }
    }

    /**
     * SMS-Fly messages statuses codes decoding routine
     *
     * @param $statusMsgCode
     *
     * @return array
     */
    protected function decodeStatusMsg($statusMsgCode) {
        $msgStatusCodes = array(
            'ACCEPTD' => 'Message received by the system',
            'PENDING' => 'Message in queue for sending',
            'INPROGRESS' => 'Message in processing',
            'SENT' => 'Message sent',
            'DELIVRD' => 'Message delivered',
            'VIEWED' => 'Message viewed',
            'EXPIRED' => 'Message delivery time expired',
            'UNDELIV' => 'Message not delivered',
            'STOPED' => 'Message stopped by the system',
            'ERROR' => 'Message sending error',
            'INSUFFICIENTFUNDS' => 'Insufficient funds to send this message',
            'MODERATION' => 'Message on moderation',
            'RESERVED' => 'Message reserved by the system',
            'REFUND' => 'Message prepared for refund'
        );
        $statusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);
        $statusMsg = (isset($msgStatusCodes[$statusMsgCode])) ? __($msgStatusCodes[$statusMsgCode]) : __('Unknown status code');

        switch ($statusMsgCode) {
            case 'ACCEPTD':
            case 'PENDING':
            case 'INPROGRESS':
            case 'MODERATION':
                $statusArray['StatusMsg'] = $statusMsg;
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;
            case 'EXPIRED':
            case 'UNDELIV':
            case 'STOPED':
            case 'ERROR':
            case 'INSUFFICIENTFUNDS':
            case 'RESERVED':
            case 'REFUND':
                $statusArray['StatusMsg'] = $statusMsg;
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'VIEWED':
            case 'DELIVRD':
                $statusArray['StatusMsg'] = $statusMsg;
                $statusArray['DeliveredStatus'] = 1;
                $statusArray['NoStatusCheck'] = 1;
                break;
            default:
                $statusArray['StatusMsg'] = __('Sending status code is unknown:') . '  ' . $statusMsgCode;
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
        }

        return ($statusArray);
    }

    /**
     * As SMS-Fly needs phone numbers to be only in 38 0YY XXX XX XX format
     * this function will try to make the phone number suitable
     *
     * @param $phoneNumber string
     *
     * @return string
     */
    protected function checkPhone($number) {
        $valid_operators = [
            "039" => "kstar",
            "050" => "mts",
            "063" => "life",
            "066" => "mts",
            "067" => "kstar",
            "068" => "kstar",
            "073" => "life",
            "091" => "utel",
            "092" => "peoplenet",
            "093" => "life",
            "094" => "intertelecom",
            "095" => "mts",
            "096" => "kstar",
            "097" => "kstar",
            "098" => "kstar",
            "099" => "mts",
        ];

        preg_match_all("/([0-9]+)/", $number, $matches);
        $number = implode("", $matches[1]);
        $number = str_pad($number, 12, "0", STR_PAD_LEFT);
        $phone = substr($number, -7);
        $operator = substr($number, -10, 3);
        if(!isset($valid_operators[$operator]) || 7 != strlen($phone)) {
            return false;
        }
        $result = $this->countryCode . $operator . $phone;
        return $result; 
    }

}

?>