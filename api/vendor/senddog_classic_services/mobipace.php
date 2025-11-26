<?php

class mobipace extends SendDogProto {

    /**
     * Loads Mobipace service config
     *
     * @return void
     */
    public function loadMobipaceConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_MOBIPACE_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'https://api.mobipace.com:444/v3/';
            zb_StorageSet('SENDDOG_MOBIPACE_GATEWAY', $smsgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_MOBIPACE_LOGIN');
        if (empty($smslogin)) {
            $smslogin = 'Userlogin';
            zb_StorageSet('SENDDOG_MOBIPACE_LOGIN', $smslogin);
        }

        $smspassword = zb_StorageGet('SENDDOG_MOBIPACE_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = 'MySecretPassword';
            zb_StorageSet('SENDDOG_MOBIPACE_PASSWORD', $smspassword);
        }

        $smsalphaname = zb_StorageGet('SENDDOG_MOBIPACE_ALPHANAME');
        if (empty($smsalphaname)) {
            $smsalphaname = 'Alphaname';
            zb_StorageSet('SENDDOG_MOBIPACE_ALPHANAME', $smsalphaname);
        }

        $smsurlauth = zb_StorageGet('SENDDOG_MOBIPACE_URL_AUTH');
        if (empty($smsurlauth)) {
            $smsurlauth = 'authorize';
            zb_StorageSet('SENDDOG_MOBIPACE_URL_AUTH', $smsurlauth);
        }

        $smsurlsend = zb_StorageGet('SENDDOG_MOBIPACE_URL_SEND');
        if (empty($smsurlsend)) {
            $smsurlsend = 'Send';
            zb_StorageSet('SENDDOG_MOBIPACE_URL_SEND', $smsurlsend);
        }

        $smsurlstatuses = zb_StorageGet('SENDDOG_MOBIPACE_URL_STATUSES');
        if (empty($smsurlstatuses)) {
            $smsurlstatuses = 'QueryMessages';
            zb_StorageSet('SENDDOG_MOBIPACE_URL_STATUSES', $smsurlstatuses);
        }

        $smsurlbalance = zb_StorageGet('SENDDOG_MOBIPACE_URL_BALANCE');
        if (empty($smsurlbalance)) {
            $smsurlbalance = 'QueryBalance';
            zb_StorageSet('SENDDOG_MOBIPACE_URL_BALANCE', $smsurlbalance);
        }

        $this->settings['MOBIPACE_GATEWAY'] = $smsgateway;
        $this->settings['MOBIPACE_LOGIN'] = $smslogin;
        $this->settings['MOBIPACE_PASSWORD'] = $smspassword;
        $this->settings['MOBIPACE_ALPHANAME'] = $smsalphaname;
        $this->settings['MOBIPACE_URL_AUTH'] = $smsurlauth;
        $this->settings['MOBIPACE_URL_SEND'] = $smsurlsend;
        $this->settings['MOBIPACE_URL_STATUSES'] = $smsurlstatuses;
        $this->settings['MOBIPACE_URL_BALANCE'] = $smsurlbalance;
    }

    /**
     * Checks messages status for Mopbipace service
     *
     * @return void
     */
    public function mobipaceCheckMessagesStatus() {
        $sessionID = $this->mobipaceDoAuth();

        if (empty($sessionID)) {
            log_register('SENDDOG MOBIPACE ERROR check messages statuses failed - empty session ID, check auth parameters');
        } else {
            $url = rtrim($this->settings['MOBIPACE_GATEWAY'], '/') . '/' . $this->settings['MOBIPACE_URL_STATUSES'];
            $statusReq = array('SessionId' => $sessionID, 'References' => array());

            $query = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1;";

            $checkMessages = simple_queryall($query);

            if (!empty($checkMessages)) {
                $messagesIDs = array();

                foreach ($checkMessages as $io => $eachmessage) {
                    $messagesIDs[] = $eachmessage['srvmsgself_id'];
                }

                //$messagesIdsList = implode(';', $messagesIDs);

                $statusReq['References'] = $messagesIDs;
                $statusReq = json_encode($statusReq);

                $response = $this->mobipaceDoCURL($url, $statusReq);

                if (!empty($response)) {
                    $response = json_decode($response, true);
                    $srvAnswerCode = (isset($response['StatusCode'])) ? $response['StatusCode'] : '42';

                    if ($srvAnswerCode == 101) {
                        $msgStatuses = (empty($response['MessageStatuses'])) ? array() : $response['MessageStatuses'];

                        foreach ($msgStatuses as $io => $eachMsgStatus) {
                            $messageId = $eachMsgStatus['Reference'];
                            $messageStatusCode = $eachMsgStatus['StatusCode'];
                            $decodedMessageStatus = $this->mobipaceDecodeStatusMsg($messageStatusCode);

                            $query = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                               `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                               `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                               `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $messageId . "';";
                            nr_query($query);
                        }

                        log_register('SENDDOG MOBIPACE checked statuses for ' . count($msgStatuses) . ' messages.');
                    } else {
                        $serverErrorMsg = $this->mobipaceProcessError($srvAnswerCode);
                        log_register('SENDDOG MOBIPACE ERROR failed to check messages statuses. Server answer: ' . $serverErrorMsg);
                    }
                }
            }
        }
    }

    /**
     * Mobipace messages statuses codes decoding routine
     *
     * @param $statusMsgCode
     *
     * @return array
     */
    public function mobipaceDecodeStatusMsg($statusMsgCode) {
        $msgStatusCodes = array(1 => 'Pending',
            2 => 'Scheduled message',
            3 => 'Sent',
            4 => 'Insufficient funds to send messages',
            5 => 'Invalid cell number format',
            6 => 'Maximum message length of 500 chars reached',
            7 => 'Invalid alpha-name - please, contact your manager',
            8 => 'Invalid message route - please, contact your manager',
            9 => 'Cell number blocked',
            10 => 'System failure',
            100 => 'Delivered',
            101 => 'Undeliverable',
            102 => 'Message sent, status unknown',
            103 => 'Rejected',
            104 => 'TimeOut'
        );
        $statusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);
        $statusMsg = (isset($msgStatusCodes[$statusMsgCode])) ? __($msgStatusCodes[$statusMsgCode]) : __('Unknown status code');

        switch ($statusMsgCode) {
            case 1:
            case 2:
            case 3:
                $statusArray['StatusMsg'] = $statusMsg;
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 4:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
            case 10:
            case 101:
            case 102:
            case 103:
            case 104:
                $statusArray['StatusMsg'] = $statusMsg;
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 100:
                $statusArray['StatusMsg'] = $statusMsg;
                $statusArray['DeliveredStatus'] = 1;
                $statusArray['NoStatusCheck'] = 0;
                break;

            default:
                $statusArray['StatusMsg'] = __('Sending status code is unknown:') . '  ' . $statusMsgCode;
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
        }

        return ($statusArray);
    }

    /**
     * Mobipace error codes decoding routine
     *
     * @param $errorCode
     *
     * @return string
     */
    public function mobipaceProcessError($errorCode) {
        $errorCodes = array(100 => 'System failure',
            101 => 'Request successful',
            102 => 'Request format or parameters are incorrect',
            103 => 'Session expired',
            104 => 'System is processing request with such ID already or request with such ID temporarily can not be processed',
            200 => 'Incorrect user name or password',
            201 => 'Maximum amount of auth fails reached - please, contact your manager',
            202 => 'User is blocked - please, contact your manager',
            300 => 'Insufficient funds to send messages',
            301 => 'Message count overflow: no more than 20000 messages per session supported',
            302 => 'Account currency not defined - proceed to your personal page and check config'
        );
        $errMsg = (isset($errorCodes[$errorCode])) ? __($errorCodes[$errorCode]) : __('Unknown error code');
        return ('[' . $errorCode . '] - ' . $errMsg);
    }

    /**
     * Performs CURL init and exec with Mobipace's suitable pre-configs
     *
     * @param $url
     * @param $postFileds
     *
     * @return bool|string
     */
    public function mobipaceDoCURL($url, $postFileds) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json; charset=utf-8", "Cache-Control: no-cache"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFileds);
        $result = curl_exec($curl);
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl); // Deprecated in PHP 8.5
        }
        return ($result);
    }

    /**
     * Mobipace auth routine
     */
    public function mobipaceDoAuth() {
        $url = rtrim($this->settings['MOBIPACE_GATEWAY'], '/') . '/' . $this->settings['MOBIPACE_URL_AUTH'];
        $authReq = json_encode(array('Username' => $this->settings['MOBIPACE_LOGIN'], 'Password' => $this->settings['MOBIPACE_PASSWORD']));
        $response = $this->mobipaceDoCURL($url, $authReq);
        $sessionID = '';

        if (!empty($response)) {
            $response = json_decode($response, true);
            $srvAnswerCode = (isset($response['StatusCode'])) ? $response['StatusCode'] : '42';

            if ($srvAnswerCode == 101) {
                $sessionID = $response['SessionId'];
            } else {
                $serverErrorMsg = $this->mobipaceProcessError($srvAnswerCode);
                log_register('SENDDOG MOBIPACE ERROR auth failed: ' . $serverErrorMsg);
            }
        }

        return ($sessionID);
    }

    /**
     * Renders current Mobipace service user balance
     *
     * @return string
     */
    public function renderMobipaceBalance() {
        $sessionID = $this->mobipaceDoAuth();
        $result = '';

        if (empty($sessionID)) {
            log_register('SENDDOG MOBIPACE ERROR get balance failed - empty session ID, check auth parameters');
            $result = $this->messages->getStyledMessage(__('Getting balance failed - empty session ID, check auth parameters'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
            //die(wf_modalAutoForm(__('Error'), $errormes, $_POST['modalWindowId'], '', true));
        } else {
            $url = rtrim($this->settings['MOBIPACE_GATEWAY'], '/') . '/' . $this->settings['MOBIPACE_URL_BALANCE'];
            $balanceReq = json_encode(array('SessionId' => $sessionID));
            $response = $this->mobipaceDoCURL($url, $balanceReq);
            $response = json_decode($response, true);
            $srvAnswerCode = (isset($response['StatusCode'])) ? $response['StatusCode'] : '42';

            if ($srvAnswerCode == 101) {
                $balance = wf_delimiter(1) . wf_nbsp(4) . __('Balance') . ': ' . $response['Balance']
                        . wf_delimiter(0) . wf_nbsp(4) . __('Credit') . ': ' . $response['BalanceNegativeLimit'];
            } else {
                $balance = $this->processError($srvAnswerCode);
                log_register('SENDDOG MOBIPACE ERROR getting balance failed: ' . $balance);
            }

            $result .= $this->messages->getStyledMessage(__('Current account balance') . ': ' . $balance, 'info');
            //die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
        }

        return ($result);
    }

    /**
     * Returns set of inputs, required for Mobipace service configuration
     *
     * @return string
     */
    public function renderMobipaceConfigInputs() {
        $inputs = wf_tag('h2') . 'Mobipace' . ' ' . wf_Link(self::URL_ME . '&showmisc=mobipace', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs .= wf_TextInput('editmobipacegateway', __('Mobipace API address'), $this->settings['MOBIPACE_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editmobipacelogin', __('User login to access Mobipace API'), $this->settings['MOBIPACE_LOGIN'], true, 20);
        $inputs .= wf_TextInput('editmobipacepassword', __('User password for access Mobipace API'), $this->settings['MOBIPACE_PASSWORD'], true, 20);
        $inputs .= wf_TextInput('editmobipacealphaname', __('User sign for Mobipace service') . ' (' . __('Alphaname') . ')', $this->settings['MOBIPACE_ALPHANAME'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'mobipace') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use Mobipace as default SMS service'), 'mobipace', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {
        // Mobipace configuration
        if ($_POST['editmobipacegateway'] != $this->settings['MOBIPACE_GATEWAY']) {
            zb_StorageSet('SENDDOG_MOBIPACE_GATEWAY', $_POST['editmobipacegateway']);
            log_register('SENDDOG CONFIG SET MOBIPACEGATEWAY `' . $_POST['editmobipacegateway'] . '`');
        }
        if ($_POST['editmobipacelogin'] != $this->settings['MOBIPACE_LOGIN']) {
            zb_StorageSet('SENDDOG_MOBIPACE_LOGIN', $_POST['editmobipacelogin']);
            log_register('SENDDOG CONFIG SET MOBIPACELOGIN `' . $_POST['editmobipacelogin'] . '`');
        }
        if ($_POST['editmobipacepassword'] != $this->settings['MOBIPACE_PASSWORD']) {
            zb_StorageSet('SENDDOG_MOBIPACE_PASSWORD', $_POST['editmobipacepassword']);
            log_register('SENDDOG CONFIG SET MOBIPACEPASSWORD `' . $_POST['editmobipacepassword'] . '`');
        }
        if ($_POST['editmobipacealphaname'] != $this->settings['MOBIPACE_ALPHANAME']) {
            zb_StorageSet('SENDDOG_MOBIPACE_ALPHANAME', $_POST['editmobipacealphaname']);
            log_register('SENDDOG CONFIG SET MOBIPACE ALPHANAME `' . $_POST['editmobipacealphaname'] . '`');
        }
    }

    /**
     * Sends all sms storage via Mobipace service
     *
     * @return void
     */
    public function mobipacePushMessages() {
        $sessionID = $this->mobipaceDoAuth();

        if (empty($sessionID)) {
            log_register('SENDDOG MOBIPACE ERROR send messages failed - empty session ID, check auth parameters');
        } else {
            $url = rtrim($this->settings['MOBIPACE_GATEWAY'], '/') . '/' . $this->settings['MOBIPACE_URL_SEND'];
            $sender = $this->settings['MOBIPACE_ALPHANAME'];
            $smsHistoryEnabled = $this->ubConfig->getAlterParam('SMS_HISTORY_ON');
            $smsHistTabFreshIds = array();
            $preSendStatus = __('Perparing for delivery');
            $telepatia = new Telepathy(false);

            if ($smsHistoryEnabled) {
                $telepatia->flushPhoneTelepathyCache();
                $telepatia->usePhones();
            }

            $allSmsQueue = $this->smsQueue->getQueueData();
            if (!empty($allSmsQueue)) {
                log_register('SENDDOG MOBIPACE sending SMS packet: ' . $sessionID);
                $smsArray = array('SessionId' => $sessionID,
                    'Sender' => $sender,
                    'Messages' => array()
                );

                foreach ($allSmsQueue as $eachsms) {
                    $smsMsgId = strtoupper(md5(uniqid(rand(), true)));

                    if ($smsHistoryEnabled) {
                        $Login = $telepatia->getByPhoneFast($eachsms['number']);

                        $query = "INSERT INTO `sms_history` (`login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `send_status`, `msg_text`) 
                                                     VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '" . $smsMsgId . "', '" . $sessionID . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                        nr_query($query);

                        $recId = simple_get_lastid('sms_history');
                        $smsHistTabFreshIds[] = $recId;
                    }

                    $smsArray['Messages'][] = array('Recipient' => ltrim($eachsms['number'], '+0'),
                        'Body' => $eachsms['message'],
                        'Reference' => $smsMsgId
                    );

                    $this->smsQueue->deleteSms($eachsms['filename']);
                }

                $smsArray = json_encode($smsArray);
                $response = $this->mobipaceDoCURL($url, $smsArray);

                if (!empty($response)) {
                    $response = json_decode($response, true);
                    $srvAnswerCode = (isset($response['StatusCode'])) ? $response['StatusCode'] : '42';
                    $msgsCnt = (isset($response['MessageCount'])) ? $response['MessageCount'] : '0';
                    $priceTotal = (isset($response['TotalPrice'])) ? $response['TotalPrice'] : '0';
                    $idsAsStr = implode(',', $smsHistTabFreshIds);

                    if ($srvAnswerCode == 101) {
                        $query = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "', 
                                                           `send_status` = '" . __('Message queued') . "' 
                                            WHERE `id` IN (" . $idsAsStr . ");";
                        nr_query($query);

                        log_register('SENDDOG MOBIPACE sending SMS packet: ' . $sessionID . ' successful. Packet messages count: ' . $msgsCnt . '. Packet price: ' . $priceTotal . '.');
                    } else {
                        $serverErrorMsg = $this->mobipaceProcessError($srvAnswerCode);
                        log_register('SENDDOG MOBIPACE sending SMS packet: ' . $sessionID . 'FAILED. Server answer: ' . $serverErrorMsg);

                        $query = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "',
                                                           `date_statuschk` = '" . curdatetime() . "',
                                                           `no_statuschk` = '1', 
                                                           `send_status` = '" . __('Failed to send message') . ': ' . $serverErrorMsg . "' 
                                            WHERE `id` IN (" . $idsAsStr . ");";
                        nr_query($query);
                    }
                }
            }
        }
    }

}
