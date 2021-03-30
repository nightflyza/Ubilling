<?php

class Mobipace extends SMSServiceApi {
    /**
     * Status message returned by service after any request
     *
     * @var string
     */
    protected $statusStr = '';

    /**
     * Status code returned by service after any request
     *
     * @var int
     */
    protected $statusCode = 0;

    /**
     * Session ID returned by service after successful auth
     *
     * @var string
     */
    protected $sessionID = '';

    /**
     * Service URL path suffix for a "auth" request
     *
     * @var string
     */
    protected $urlSuffixAuth = 'authorize';

    /**
     * Service URL path suffix for a "send" request
     *
     * @var string
     */
    protected $urlSuffixSend = 'Send';

    /**
     * Service URL path suffix for a "get msg statuses" request
     *
     * @var string
     */
    protected $urlSuffixStatuses = 'QueryMessages';

    /**
     * Service URL path suffix for a "get balance" request
     *
     * @var string
     */
    protected $urlSuffixBalance = 'QueryBalance';

    /**
     * Messages status codes as defined in API manual
     *
     * @var array
     */
    protected $msgStatusCodes = array(1 => 'Pending',
                                      2 => 'Scheduled',
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

    /**
     * Error codes => messages as defined in API manual
     *
     * @var array
     */
    protected $errorCodes = array(100 => 'System failure',
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


    public function __construct($smsServiceId, $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
        $this->doAuth();
    }

    protected function doCURL($url, $postFileds) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json; charset=utf-8", "Cache-Control: no-cache"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFileds);
        $result = curl_exec($curl);
        curl_close($curl);

        return ($result);
    }

    public function doAuth() {
        $url        = rtrim($this->serviceGatewayAddr, '/') . '/' . $this->urlSuffixAuth;
        $authReq    = json_encode(array('Username' => $this->serviceLogin, 'Password' => $this->servicePassword));
        $response   = $this->doCURL($url, $authReq);

        if (!empty($response)) {
            $response       = json_decode($response, true);
            $srvAnswerCode  = (isset($response['StatusCode'])) ? $response['StatusCode'] : '42';

            if ($srvAnswerCode == 101) {
                $this->sessionID = $response['SessionId'];
            } else {
                $serverErrorMsg = $this->processError($srvAnswerCode);
                log_register('SENDDOG MOBIPACE ERROR auth failed: ' . $serverErrorMsg);
            }
        }
    }

    public function pushMessages() {
        if (empty($this->sessionID)) {
            log_register('SENDDOG MOBIPACE ERROR send messages failed - empty session ID, check auth parameters');
        } else {
            $url                = rtrim($this->serviceGatewayAddr, '/') . '/' . $this->urlSuffixSend;
            $sender             = $this->serviceAlphaName;
            $smsHistoryEnabled  = $this->instanceSendDog->getUBConfigInstance()->getAlterParam('SMS_HISTORY_ON');
            $smsAdvancedEnabled = $this->instanceSendDog->getUBConfigInstance()->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
            $smsHistTabFreshIds = array();
            $preSendStatus      = __('Perparing for delivery');
            $telepatia          = new Telepathy(false);

            if ($smsHistoryEnabled) {
                $telepatia->flushPhoneTelepathyCache();
                $telepatia->usePhones();
            }

            $allSmsQueue = $this->smsMessagePack;
            if (!empty($allSmsQueue)) {
                log_register('SENDDOG MOBIPACE sending SMS packet: ' . $this->sessionID);
                $smsArray = array('SessionId' => $this->sessionID,
                                  'Sender' => $sender,
                                  'Messages' => array()
                                 );

                foreach ($allSmsQueue as $eachsms) {
                    $smsMsgId = strtoupper(md5(uniqid(rand(), true)));

                    if ($smsHistoryEnabled) {
                        $Login = $telepatia->getByPhoneFast($eachsms['number']);

                        if ($smsAdvancedEnabled) {
                            $query = "INSERT INTO `sms_history` (`smssrvid`, `login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `send_status`, `msg_text`) 
                                                         VALUES (" . $this->serviceId . ", '" . $Login . "', '" . $eachsms['number'] . "', '". $smsMsgId . "', '" . $this->sessionID . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                        } else {
                            $query = "INSERT INTO `sms_history` (`login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `send_status`, `msg_text`) 
                                                         VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '". $smsMsgId . "', '" . $this->sessionID . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                        }
                        nr_query($query);

                        $recId = simple_get_lastid('sms_history');
                        $smsHistTabFreshIds[] = $recId;
                    }

                    $smsArray['Messages'][] = array('Recipient' => ltrim($eachsms['number'], '+0'),
                                                    'Body' => $eachsms['message'],
                                                    'Reference' => $smsMsgId
                                                   );

                    $this->instanceSendDog->getSmsQueueInstance()->deleteSms($eachsms['filename']);
                }

                $smsArray = json_encode($smsArray);
                $response = $this->doCURL($url, $smsArray);

                if (!empty($response)) {
                    $response       = json_decode($response, true);
                    $srvAnswerCode  = (isset($response['StatusCode'])) ? $response['StatusCode'] : '42';
                    $msgsCnt        = (isset($response['MessageCount'])) ? $response['MessageCount'] : '0';
                    $priceTotal     = (isset($response['TotalPrice'])) ? $response['TotalPrice'] : '0';
                    $idsAsStr       = implode(',', $smsHistTabFreshIds);

                    if ($srvAnswerCode == 101) {
                        $query = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "', 
                                                           `send_status` = '" . __('Message queued') . "' 
                                            WHERE `id` IN (" . $idsAsStr . ");";
                        nr_query($query);

                        log_register('SENDDOG MOBIPACE sending SMS packet: ' . $this->sessionID . ' successful. Packet messages count: ' . $msgsCnt . '. Packet price: ' . $priceTotal . '.');
                    } else {
                        $serverErrorMsg = $this->processError($srvAnswerCode);
                        log_register('SENDDOG MOBIPACE sending SMS packet: ' . $this->sessionID . 'FAILED. Server answer: ' . $serverErrorMsg);

                        $query = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "',
                                                           `date_statuschk` = '" . curdatetime() . "',
                                                           `no_statuschk` = '1', 
                                                           `send_status` = '" . __('Failed to send message') . ': ' . $serverErrorMsg ."' 
                                            WHERE `id` IN (" . $idsAsStr . ");";
                        nr_query($query);
                    }
                }
            }
        }
    }

    public function checkMessagesStatuses() {
        if (empty($this->sessionID)) {
            log_register('SENDDOG MOBIPACE ERROR check messages statuses failed - empty session ID, check auth parameters');
        } else {
            $url        = rtrim($this->serviceGatewayAddr, '/') . '/' . $this->urlSuffixStatuses;
            $statusReq  = array('SessionId' => $this->sessionID, 'References' => array());

            if ($this->isDefaultService) {
                $query = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND (`smssrvid` = " . $this->serviceId . " OR `smssrvid` = 0);";
            } else {
                $query = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND `smssrvid` = " . $this->serviceId . ";";
            }

            $checkMessages = simple_queryall($query);

            if (!empty($checkMessages)) {
                $messagesIDs = array();

                foreach ($checkMessages as $io => $eachmessage) {
                    $messagesIDs[] = $eachmessage['srvmsgself_id'];
                }

                //$messagesIdsList = implode(';', $messagesIDs);

                $statusReq['References'] = $messagesIDs;
                $statusReq = json_encode($statusReq);

                $response = $this->doCURL($url, $statusReq);

                if (!empty($response)) {
                    $response       = json_decode($response, true);
                    $srvAnswerCode  = (isset($response['StatusCode'])) ? $response['StatusCode'] : '42';

                    if ($srvAnswerCode == 101) {
                        $msgStatuses = (empty($response['MessageStatuses'])) ? array() : $response['MessageStatuses'];

                        foreach ($msgStatuses as $io => $eachMsgStatus) {
                            $messageId            = $eachMsgStatus['Reference'];
                            $messageStatusCode    = $eachMsgStatus['StatusCode'];
                            $decodedMessageStatus = $this->decodeMobipaceStatusMsg($messageStatusCode);

                            $query = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                               `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                               `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                               `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $messageId . "';";
                            nr_query($query);
                        }

                        log_register('SENDDOG MOBIPACE checked statuses for ' . count($msgStatuses) . ' messages.');
                    } else {
                        $serverErrorMsg = $this->processError($srvAnswerCode);
                        log_register('SENDDOG MOBIPACE ERROR failed to check messages statuses. Server answer: ' . $serverErrorMsg);
                    }
                }
            }
        }
    }

    public function getBalance() {
        if (empty($this->sessionID)) {
            log_register('SENDDOG MOBIPACE ERROR get balance failed - empty session ID, check auth parameters');
            $errormes = $this->instanceSendDog->getUBMsgHelperInstance()->getStyledMessage(__('Getting balance failed - empty session ID, check auth parameters'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
            die(wf_modalAutoForm(__('Error'), $errormes, $_POST['modalWindowId'], '', true));
        } else {
            $result         = '';
            $url            = rtrim($this->serviceGatewayAddr, '/') . '/' . $this->urlSuffixBalance;
            $balanceReq     = json_encode(array('SessionId' => $this->sessionID));
            $response       = $this->doCURL($url, $balanceReq);
            $response       = json_decode($response, true);
            $srvAnswerCode  = (isset($response['StatusCode'])) ? $response['StatusCode'] : '42';

            if ($srvAnswerCode == 101) {
                $balance = wf_delimiter(1) . wf_nbsp(4) . __('Balance') . ': ' . $response['Balance']
                           . wf_delimiter(0) . wf_nbsp(4) . __('Credit') . ': ' . $response['BalanceNegativeLimit'];
            } else {
                $balance = $this->processError($srvAnswerCode);
                log_register('SENDDOG MOBIPACE ERROR getting balance failed: ' . $balance);
            }

            $result.= $this->instanceSendDog->getUBMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $balance, 'info');
            die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
        }
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    protected function processError($errorCode) {
        $errMsg = (isset($this->errorCodes[$errorCode])) ? __($this->errorCodes[$errorCode]) : __('Unknown error code');
        return ('[' . $errorCode . '] - ' . $errMsg);
    }

    protected function decodeMobipaceStatusMsg($statusMsgCode) {
        $statusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);
        $statusMsg   = (isset($this->msgStatusCodes[$statusMsgCode])) ? __($this->msgStatusCodes[$statusMsgCode]) : __('Unknown status code');

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
}