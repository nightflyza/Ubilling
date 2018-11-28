<?php

class SkySms extends SMSServiceApi {
    public function __construct($smsServiceId, $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
    }

    public function getBalance() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        if ( !empty($this->smsMessagePack) ) {
            global $ubillingConfig;
            $i = 0;
            $smsHistoryEnabled = $ubillingConfig->getAlterParam('SMS_HISTORY_ON');
            $smsAdvancedEnabled = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
            $smsHistoryTabFreshIds = array();
            $preSendStatus = __('Perparing for delivery');
            $telepatia = new Telepathy(false);

            if ($smsHistoryEnabled) {
                $telepatia->flushPhoneTelepathyCache();
                $telepatia->usePhones();
            }

            $xmlPacket = '<?xml version="1.0" encoding="utf-8"?>
                          <packet version="1.0">
                          <auth login="' . $this->serviceLogin . '" password="' . $this->servicePassword . '"/>
                          <command name="sendmessage">
                          <message id="0" type="sms">
                          <data charset="lat"></data>
                          <recipients>
                         ';

            foreach ($this->smsMessagePack as $io => $eachsms) {
                if ($smsHistoryEnabled) {
                    //$PhoneToSearch = $this->sendDog->cutInternationalsFromPhoneNum($eachsms['number']);
                    $Login = $telepatia->getByPhoneFast($eachsms['number']);

                    if ($smsAdvancedEnabled) {
                        $query = "INSERT INTO `sms_history` (`smssrvid`, `login`, `phone`, `send_status`, `msg_text`) 
                                                      VALUES (" . $this->serviceId . ", '" . $Login . "', '" . $eachsms['number'] . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                    } else {
                        $query = "INSERT INTO `sms_history` (`login`, `phone`, `send_status`, `msg_text`) 
                                                      VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                    }
                    nr_query($query);

                    $recId = simple_get_lastid('sms_history');
                    $smsHistoryTabFreshIds[] = $recId;

                    $xmlPacket .= '<recipient id="' . $recId . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                } else {
                    $xmlPacket .= '<recipient id="' . ++$i . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                }

                $this->instanceSendDog->getSmsQueueInstance()->deleteSms($eachsms['filename']);
            }

            $telepatia->savePhoneTelepathyCache();

            $xmlPacket .= '</recipients>
                            </message>
                            </command>
                            </packet>
                          ';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_URL, $this->serviceGatewayAddr);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlPacket);
            $result = curl_exec($curl);
            curl_close($curl);

            $parsedResult = zb_xml2array($result);

            if ( !empty($parsedResult) ) {
                $serverAnswerCode = (isset($parsedResult['packet']['result_attr']['type'])) ? $parsedResult['packet']['result_attr']['type'] : '42';

                if ($serverAnswerCode == '00') {
                    $smsPacketId = $parsedResult['packet']['result']['message_attr']['smsmsgid'];
                    log_register('SENDDOG SKYSMS packet ' . $smsPacketId . ' sent successfully');

                    if ($smsHistoryEnabled) {
                        $recipients = $parsedResult['packet']['result']['message']['recipients']['recipient'];

                        if ( empty($recipients) ) { $recipients = $parsedResult['packet']['result']['message']['recipients']; }

                        foreach ($recipients as $each => $Recipient) {
                            if ( isset($Recipient['id']) ) {
                                $query = "UPDATE `sms_history` SET `srvmsgself_id` = '" . $Recipient['smsid'] . "', 
                                                                    `srvmsgpack_id` = '" . $smsPacketId . "',                                                            
                                                                    `date_send` = '" . curdatetime() . "', 
                                                                    `send_status` = '" . __('Message queued') . "' 
                                                WHERE `id` = '" . $Recipient['id'] . "';";
                                nr_query($query);
                            }
                        }
                    }
                } else {
                    $serverErrorMsg = $this->decodeSkySMSErrorMsg($serverAnswerCode);
                    log_register('SENDDOG SKYSMS failed to sent SMS packet. Server answer: ' . $serverErrorMsg . ( ($serverAnswerCode == '42') ? $result : '') );

                    if ($smsHistoryEnabled) {
                        $idsAsStr = implode(',', $smsHistoryTabFreshIds);
                        $query = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "',
                                                            `date_statuschk` = '" . curdatetime() . "',
                                                            `no_statuschk` = '1', 
                                                            `send_status` = '" . __('Failed to send message') . ': ' . $serverErrorMsg ."' 
                                        WHERE `id` IN (" . $idsAsStr . ");";
                        nr_query($query);
                    }
                }
            }

            /*//remove old sent message
            foreach ($allSmsQueue as $io => $eachsms) {
                $this->smsQueue->deleteSms($eachsms['filename']);
            }*/
        }
    }

    /**
     * Checks messages status for SKYSMS service
     *
     * @return void
     */
    public function checkMessagesStatuses() {
        /*if ( empty($this->smsMessagePack) ) {
        } else { $chkMessages = $this->smsMessagePack; }*/

        // we better ignore the contents of $this->smsMessagePack because it will probably come not distinguished by srvmsgpack_id
        // which will mess up all the things and flood our events log with plenty of unneeded shit
        // and as long as SkySms allows to check statuses by packet ID (no need to do it for every message individually)
        // it will be better to requery data from DB in our manner:
        if ($this->isDefaultService) {
            $query = "SELECT DISTINCT `srvmsgpack_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND (`smssrvid` = " . $this->serviceId . " OR `smssrvid` = 0);";
        } else {
            $query = "SELECT DISTINCT `srvmsgpack_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND `smssrvid` = " . $this->serviceId . ";";
        }
        $chkMessages = simple_queryall($query);

        if ( !empty($chkMessages) ) {
            $skySmsApiUrl   = $this->serviceGatewayAddr;
            $skySmsApiLogin = $this->serviceLogin;
            $skySmsApiPassw = $this->servicePassword;

            foreach ($chkMessages as $io => $eachmessage) {
                $smsPacketID = $eachmessage['srvmsgpack_id'];

                if ( empty($smsPacketID) ) { continue; }

                $xmlPacket = '<?xml version="1.0" encoding="utf-8"?>
                              <packet version="1.0">
                              <auth login="' . $skySmsApiLogin . '" password="' . $skySmsApiPassw . '"/>
                              <command name="querymessage">
                              <message smsmsgid="' . $smsPacketID . '"/>
                              </command>
                              </packet>
                             ';

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_URL, $skySmsApiUrl);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlPacket);
                $result = curl_exec($curl);
                curl_close($curl);

                $parsedResult = zb_xml2array($result);

                if ( !empty($parsedResult) ) {
                    $serverAnswerCode = (isset($parsedResult['packet']['result_attr']['type'])) ? $parsedResult['packet']['result_attr']['type'] : '42';

                    if ($serverAnswerCode == '00') {
                        $recipients = $parsedResult['packet']['result']['message']['recipients']['recipient'];

                        if ( empty($recipients) ) { $recipients = $parsedResult['packet']['result']['message']['recipients']; }

                        foreach ($recipients as $each => $Recipient) {
                            if (isset($Recipient['smsid'])) {
                                $messageId             = $Recipient['smsid'];
                                $messageStatus         = $Recipient['status'];
                                $decodedMessageStatus  = $this->decodeSkySMSStatusMsg($messageStatus);

                                $query = "UPDATE `sms_history` SET `date_statuschk` = '". curdatetime() . "', 
                                                                    `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                                    `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                                    `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $messageId . "';";
                                nr_query($query);
                            }
                        }

                        //log_register('SENDDOG SKYSMS checked SMS message ' . $messageId . ' send status');
                        log_register('SENDDOG SKYSMS checking SMS packet ' . $smsPacketID . ' send status');
                    } else {
                        $serverErrorMsg = $this->decodeSkySMSErrorMsg($serverAnswerCode);
                        log_register('SENDDOG SKYSMS failed to get SMS packet ' . $smsPacketID . ' send status. Server answer: ' . $serverErrorMsg . ( ($serverAnswerCode == '42') ? $result : '') );
                    }
                }
            }
        }
    }

    /**
     * Gets the error message code as a parameter and returns appropriate message string
     *
     * @param string $errorMsgCode
     * @return string
     */
    protected function decodeSkySMSErrorMsg($errorMsgCode) {
        switch ($errorMsgCode) {
            case '01':
                $message = __('Incorrect parameters value or insufficient parameters count');
                break;
            case '02':
                $message = __('Database server connection error');
                break;
            case '03':
                $message = __('Database was not found');
                break;
            case '04':
                $message = __('Authorization procedure error');
                break;
            case '05':
                $message = __('Login or password is incorrect');
                break;
            case '06':
                $message = __('Malfunction in user\'s configuration');
                break;
            default:
                $message = __('Error code is unknown. Servers answer:') . '  ' . $errorMsgCode;
        }

        return $message;
    }

    /**
     * Gets the status message code as a parameter and returns appropriate message string
     *
     * @param  string $statusMsgCode
     * @return array
     */
    protected function decodeSkySMSStatusMsg($statusMsgCode) {
        $statusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);

        switch ($statusMsgCode) {
            case 'DELIVERED':
                $statusArray['StatusMsg'] = __('Message is delivered to recipient');
                $statusArray['DeliveredStatus'] = 1;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'TOSEND':
                $statusArray['StatusMsg'] = __('Message is queued for delivering');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'ENROUTE':
                $statusArray['StatusMsg'] = __('Message is sent but not yet delivered to recipient');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'PAUSED':
                $statusArray['StatusMsg'] = __('Message delivering is paused');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'CANCELED':
                $statusArray['StatusMsg'] = __('Message delivering is canceled');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'FAILED':
                $statusArray['StatusMsg'] = __('Failed to send message');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'EXPIRED':
                $statusArray['StatusMsg'] = __('Failed to deliver message - delivery term is expired');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'UNDELIVERABLE':
                $statusArray['StatusMsg'] = __('Message can not be delivered to recipient');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'REJECTED':
                $statusArray['StatusMsg'] = __('Message is rejected by server');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'BADCOST':
                $statusArray['StatusMsg'] = __('Message is not delivered to recipient - can not determine message cost');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'UNKNOWN':
                $statusArray['StatusMsg'] = __('Message status is unknown');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            default:
                $statusArray['StatusMsg'] = __('Sending status code is unknown:') . '  ' . $statusMsgCode;
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
        }

        return $statusArray;
    }
}

?>