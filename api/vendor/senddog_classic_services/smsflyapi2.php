<?php

class smsflyapi2 extends SendDogProto {

    protected $baseurl = 'https://sms-fly.ua/api/v2/api.php';
    protected $apikey = '';

    /**
     * Returns set of inputs, required for SMS-Fly service configuration
     * 
     * @return string
     */
    public function renderSmsflyConfigInputs() {
        $inputs = wf_tag('h2') . __('SMS-Fly API2') . ' ' . wf_Link(self::URL_ME . '&showmisc=smsflyapi2', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs .= wf_TextInput('editsmsflyapi2gateway', __('SMS-Fly API address'), $this->settings['SMSFLYAPI2_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editsmsflyapi2key', __('User login to access SMS-Fly API'), $this->settings['SMSFLYAPI2_KEY'], true, 20);
        $inputs .= wf_TextInput('editsmsflyapi2sign', __('SMS-Fly') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['SMSFLYAPI2_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'smsflyapi2') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use SMS-Fly as default SMS service'), 'smsflyapi2', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Sends all sms storage via SMS-Fly service
     * 
     * @return void
     */
    public function smsflyPushMessages() {
        $result = '';
        $smsHistoryEnabled = $this->ubConfig->getAlterParam('SMS_HISTORY_ON');
        $telepatia = new Telepathy(false);

        if ($smsHistoryEnabled) {
            $telepatia->flushPhoneTelepathyCache();
            $telepatia->usePhones();
        }

        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $io => $eachsms) {
                $params = array(
                    "action" => "SENDMESSAGE",
                    "data" => array(
                        "recipient" => $this->cutInternationalsFromPhoneNum($eachsms['number']),
                        "channels" => array("sms"),
                        "sms" => array(
                            "source" => $this->source,
                            "text" => $eachsms['message']
                        )
                    )
                );
                // Send SMS
                $responce = $this->apiquery($params);
                //remove old sent message
                $this->smsQueue->deleteSms($eachsms['filename']);

                if ($smsHistoryEnabled) {
                    if ($responce) {
                        $messageStatusCode = $responce['data']['sms']['status'];
                        $decodedMessageStatus = $this->decodeStatusMsg($messageStatusCode);
                        $smsMsgId = $responce['data']['messageID'];
                        $sessionID = strtoupper(md5(uniqid(rand(), true)));
                        $Login = $telepatia->getByPhoneFast($eachsms['number']);
                        $query = "INSERT INTO `sms_history` (`login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `send_status`, `msg_text`, `date_send`) 
                                                 VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '" . $smsMsgId . "', '" . $sessionID . "', '" . $decodedMessageStatus['DeliveredStatus'] . "', '" . $eachsms['message'] . "', '" . curdatetime() . "');";
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
    public function renderSmsflyBalance() {
        $result = '';
        $balance = '';

        $params = array("action" => "GETBALANCE");
        $responce = $this->apiquery($params);

        if ($responce) {
            $balance = $responce['data']['balance'];
        }

        $result .= wf_BackLink(self::URL_ME, '', true);
        $result .= $this->messages->getStyledMessage(__('Current account balance') . ': ' . $balance, 'info');
        return ($result);
    }

    /**
     * Renders current SMS-Fly service user balance
     * 
     * @return array/bool
     */
    protected function apiquery(array $params) {
        $params['auth'] = array(
            'key' => $this->apikey,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->baseurl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, 256));
        $result = curl_exec($ch);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch); // Deprecated in PHP 8.5
        }

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
    public function smsflyCheckMessagesStatus() {
        $query = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1;";

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
            'PENDING' => 'message in queue for sending',
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
            case 'SENT':
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
     * Loads SMS-Fly service config
     * 
     * @return void
     */
    public function loadSmsflyConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_SMSFLYAPI2_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'https://sms-fly.ua/api/v2/api.php';
            zb_StorageSet('SENDDOG_SMSFLYAPI2_GATEWAY', $smsgateway);
        }

        $smskey = zb_StorageGet('SENDDOG_SMSFLYAPI2_KEY');
        if (empty($smskey)) {
            $smskey = 'dtTXXXXXXXHUdZ5m2mCXXXXXXXXXX';
            zb_StorageSet('SENDDOG_SMSFLYAPI2_KEY', $smskey);
        }
        $smssign = zb_StorageGet('SENDDOG_SMSFLYAPI2_SIGN');
        if (empty($smssign)) {
            $smssign = 'InfoCentr';
            zb_StorageSet('SENDDOG_SMSFLYAPI2_SIGN', $smssign);
        }

        $this->settings['SMSFLYAPI2_GATEWAY'] = $smsgateway;
        $this->settings['SMSFLYAPI2_KEY'] = $smskey;
        $this->settings['SMSFLYAPI2_SIGN'] = $smssign;

        $this->baseurl = $this->settings['SMSFLYAPI2_GATEWAY'];
        $this->apikey = $this->settings['SMSFLYAPI2_KEY'];
        $this->source = $this->settings['SMSFLYAPI2_SIGN'];
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {
        //SMS-Fly configuration
        if ($_POST['editsmsflyapi2gateway'] != $this->settings['SMSFLYAPI2_GATEWAY']) {
            zb_StorageSet('SENDDOG_SMSFLYAPI2_GATEWAY', $_POST['editsmsflyapi2gateway']);
            log_register('SENDDOG CONFIG SET SMSFLYAPI2GATEWAY `' . $_POST['editsmsflyapi2gateway'] . '`');
        }
        if ($_POST['editsmsflyapi2key'] != $this->settings['SMSFLYAPI2_KEY']) {
            zb_StorageSet('SENDDOG_SMSFLYAPI2_KEY', $_POST['editsmsflyapi2key']);
            log_register('SENDDOG CONFIG SET SMSFLYAPI2KEY `' . $_POST['editsmsflyapi2key'] . '`');
        }
        if ($_POST['editsmsflyapi2sign'] != $this->settings['SMSFLYAPI2_SIGN']) {
            zb_StorageSet('SENDDOG_SMSFLYAPI2_SIGN', $_POST['editsmsflyapi2sign']);
            log_register('SENDDOG CONFIG SET SMSFLYAPI2SIGN `' . $_POST['editsmsflyapi2sign'] . '`');
        }
    }

}
