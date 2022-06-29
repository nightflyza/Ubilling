<?php

class SmsFlyAPI2 extends SMSServiceApi {

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
                $params = [
                    "action" => "SENDMESSAGE",
                    "data" => [
                        "recipient" => $this->cutInternationalsFromPhoneNum($eachsms['number']),
                        "channels" => ["sms"],
                        "sms" => [
                            "source" => $this->serviceAlphaName,
                            "text" => $eachsms['message']
                        ]
                    ]
                ];
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
                            $query = "INSERT INTO `sms_history` (`smssrvid`, `login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `send_status`, `msg_text`) 
                                             VALUES (" . $this->serviceId . ", '" . $Login . "', '" . $eachsms['number'] . "', '" . $smsMsgId . "', '" . $sessionID . "', '" . $decodedMessageStatus['DeliveredStatus']  . "', '" . $eachsms['message'] . "', '" . curdatetime() . "');";
                        } else {
                            $query = "INSERT INTO `sms_history` (`login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `send_status`, `msg_text`) 
                                             VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '" . $smsMsgId . "', '" . $sessionID . "', '" . $decodedMessageStatus['DeliveredStatus']  . "', '" . $eachsms['message'] . "', '" . curdatetime() . "');";
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
        
        $params = ["action" => "GETBALANCE"];
        $responce = $this->apiquery($params);

        if ($responce) {
            $balance = $responce['data']['balance'];
        }
    
        $result.= $this->instanceSendDog->getUBMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $balance, 'info');
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
    }

    protected function apiquery(array $params) {
        $params['auth'] = [
            'key' => $this->serviceApiKey,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->serviceGatewayAddr);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, JSON_UNESCAPED_UNICODE));
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
                $params = [
                    "action" => "GETMESSAGESTATUS",
                    "data" => [
                        "messageID" => $eachmessage['srvmsgself_id']
                    ]
                ];
                // Check SMS Status
                $responce = $this->apiquery($params);

                if ($responce) {
                    $messageStatusCode = $responce['data']['sms']['status'];
                    $decodedMessageStatus = $this->decodeStatusMsg($messageStatusCode);

                    $query = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                       `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                       `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                       `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                        WHERE `srvmsgself_id` = '" .$eachmessage['srvmsgself_id'] . "';";
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
}

?>