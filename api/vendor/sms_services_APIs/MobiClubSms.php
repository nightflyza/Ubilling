<?php

class MobiClubSms extends SMSServiceApi {
    public function __construct($smsServiceId, array $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
    }

    public function getBalance() {
        $result = '';
        $url = 'https://gate.smsclub.mobi/http/getbalance.php?';
        $url_result = $url . 'username=' . $this->serviceLogin . '&password=' . $this->servicePassword;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url_result);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);

        $result.= $this->instanceSendDog->getUBMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $response, 'info');
        //return ($result);
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        if (!empty($this->smsMessagePack)) {
            global $ubillingConfig;
            $i = 0;
            $smsHistoryEnabled = $ubillingConfig->getAlterParam('SMS_HISTORY_ON');
            $smsAdvancedEnabled = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');

            $smsHistoryTabFreshIds = array();
            $smsHistoryTabPhonesIds = array();
            $preSendStatus = __('Perparing for delivery');
            $telepatia = new Telepathy(false);

            if ($smsHistoryEnabled) {
                $telepatia->flushPhoneTelepathyCache();
                $telepatia->usePhones();
            }

            $xmlPacket  = '<?xml version="1.0" encoding="utf-8"?><request_sendsms><username><![CDATA[' . $this->serviceLogin . ']]></username>';
            $xmlPacket .= '<password><![CDATA[' . $this->servicePassword . ']]></password><from><![CDATA[' . $this->serviceAlphaName . ']]></from>';

            foreach ($this->smsMessagePack as $io => $eachsms) {
                $formattedPhone = $this->makePhoneFormat($eachsms['number']);

                if ($smsHistoryEnabled) {
                    //$PhoneToSearch = $this->sendDog->cutInternationalsFromPhoneNum($eachsms['number']);
                    $login = $telepatia->getByPhoneFast($eachsms['number']);

                    if ($smsAdvancedEnabled) {
                        $query = "INSERT INTO `sms_history` (`smssrvid`, `login`, `phone`, `send_status`, `msg_text`) 
                                                      VALUES (" . $this->serviceId . ", '" . $login . "', '" . $eachsms['number'] . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                    } else {
                        $query = "INSERT INTO `sms_history` (`login`, `phone`, `send_status`, `msg_text`) 
                                                      VALUES ('" . $login . "', '" . $eachsms['number'] . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                    }
                    nr_query($query);

                    $recId = simple_get_lastid('sms_history');
                    $smsHistoryTabFreshIds[] = $recId;
                    $smsHistoryTabPhonesIds[$formattedPhone] = $recId;
                }

                $xmlPacket .= '<to><![CDATA[' . $formattedPhone . ']]></to><text><![CDATA[' . $eachsms['message'] . ']]></text>';

                $this->instanceSendDog->getSmsQueueInstance()->deleteSms($eachsms['filename']);
            }

            $telepatia->savePhoneTelepathyCache();
            $xmlPacket .= '</request_sendsms>';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_CRLF, true);
            curl_setopt($curl, CURLOPT_URL, $this->serviceGatewayAddr . 'individual.php');
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlPacket);
            $result = curl_exec($curl);
            curl_close($curl);

            $parsedResult = zb_xml2array($result);

            if ( !empty($parsedResult) ) {
                $sendStatus   = $parsedResult['response']['status'];
                $serverAnswer = $parsedResult['response']['text'];

                if (strtolower($sendStatus) == 'ok') {
                    $smsPacketId = strtoupper(md5(uniqid(rand(), true)));
                    log_register('SENDDOG MobiClubSms packet ' . $smsPacketId . ' sent successfully');

                    if ($smsHistoryEnabled) {
                        $messagePhone = '';
                        $phonesServiceIds = array();

                        if ( isset($parsedResult['response']['ids']['mess_attr']['tel']) ) {
                            $phonesServiceIds[$parsedResult['response']['ids']['mess_attr']['tel']] = $parsedResult['response']['ids']['mess'];
                        } else {
                            $messagesIds = $parsedResult['response']['ids']['mess'];

                            foreach ($messagesIds as $key => $val) {
                                if (stristr($key, 'attr') !== FALSE) {
                                    continue;
                                }

                                $phoneKeyIndex = $key . '_attr';
                                $messageId = $val;

                                if ($messagePhone !== $messagesIds[$phoneKeyIndex]['tel']) {
                                    $messagePhone = $messagesIds[$phoneKeyIndex]['tel'];
                                } else {
                                    continue;
                                }

                                $phonesServiceIds[$messagePhone] = $messageId;
                            }
                        }

                        if ( !empty($smsHistoryTabPhonesIds) and !empty($phonesServiceIds) ) {
                            foreach ($smsHistoryTabPhonesIds as $phone => $recId) {
                                $ServiceMessageID = $phonesServiceIds[$phone];

                                $query = "UPDATE `sms_history` SET `srvmsgself_id` = '" . $ServiceMessageID . "', 
                                                                    `srvmsgpack_id` = '" . $smsPacketId . "',                                                            
                                                                    `date_send` = '" . curdatetime() . "', 
                                                                    `send_status` = '" . __('Message queued') . "' 
                                                WHERE `id` = '" . $recId . "';";
                                nr_query($query);
                            }
                        }
                    }
                } else {
                    log_register('SENDDOG MobiClubSms failed to send SMS packet. Server answer: ' . $serverAnswer);

                    if ($smsHistoryEnabled) {
                        $idsAsStr = implode(',', $smsHistoryTabFreshIds);
                        $query = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "',
                                                            `date_statuschk` = '" . curdatetime() . "',
                                                            `no_statuschk` = '1', 
                                                            `send_status` = '" . __('Failed to send message') . ': ' . $serverAnswer ."' 
                                        WHERE `id` IN (" . $idsAsStr . ");";
                        nr_query($query);
                    }
                }
            }
        }
    }

    public function checkMessagesStatuses() {
        if ( empty($this->smsMessagePack) ) {
            if ($this->isDefaultService) {
                $query = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND (`smssrvid` = " . $this->serviceId . " OR `smssrvid` = 0);";
            } else {
                $query = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND `smssrvid` = " . $this->serviceId . ";";
            }
            $checkMessages = simple_queryall($query);
        } else { $checkMessages = $this->smsMessagePack; }

        if ( !empty($checkMessages) ) {
            $messagesIDs = array();

            foreach ($checkMessages as $io => $eachmessage) {
                if (!empty($eachmessage['srvmsgself_id'])) {
                    $messagesIDs[] = $eachmessage['srvmsgself_id'];
                }
            }

            $messagesIdsList = implode(';', $messagesIDs);

            $xmlPacket  = '<?xml version="1.0" encoding="utf-8"?><request_getstate><username><![CDATA[' . $this->serviceLogin . ']]></username>';
            $xmlPacket .= '<password><![CDATA[' . $this->servicePassword . ']]></password><smscid><![CDATA[' . $messagesIdsList . ']]></smscid>';
            $xmlPacket .= '</request_getstate>';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_CRLF, true);
            curl_setopt($curl, CURLOPT_URL, $this->serviceGatewayAddr . 'state.php');
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlPacket);
            $result = curl_exec($curl);
            curl_close($curl);

            $parsedResult = zb_xml2array($result);

            if ( !empty($parsedResult) ) {
                $sendStatus   = $parsedResult['response']['status'];
                $serverAnswer = $parsedResult['response']['text'];

                if (strtolower($sendStatus) == 'ok') {
                    $statusEntries = $parsedResult['response']['entries']['entry'];

                    if ( isset($statusEntries['smscid']) and isset($statusEntries['state'])) {
                        $messageId = $statusEntries['smscid'];
                        $messageStatus = $statusEntries['state'];
                        $decodedMessageStatus = $this->decodeMobiSmsStatusMsg($messageStatus);

                        $query = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                            `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                            `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                            `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $messageId . "';";
                        nr_query($query);
                    } else {
                        foreach ($statusEntries as $io => $EachEntry) {
                            $messageId = $EachEntry['smscid'];
                            $messageStatus = $EachEntry['state'];
                            $decodedMessageStatus = $this->decodeMobiSmsStatusMsg($messageStatus);

                            $query = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                            `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                            `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                            `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $messageId . "';";
                            nr_query($query);
                        }
                    }

                    log_register('SENDDOG MobiClubSms checked statuses for ' . count($checkMessages) . ' messages.');
                } else {
                    log_register('SENDDOG MobiClubSms failed to check messages statuses. Server answer: ' . $serverAnswer);
                }
            }
        }
    }

    /**
     * Gets the status message code as a parameter and returns appropriate message string
     *
     * @param  string $statusMsgCode
     * @return array
     */
    protected function decodeMobiSmsStatusMsg($statusMsgCode) {
        $statusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);

        switch ($statusMsgCode) {
            case 'DELIVRD':
                $statusArray['StatusMsg'] = __('Message is delivered to recipient');
                $statusArray['DeliveredStatus'] = 1;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'UNDELIV':
                $statusArray['StatusMsg'] = __('Message can not be delivered to recipient');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'ENROUTE':
                $statusArray['StatusMsg'] = __('Message is sent but not yet delivered to recipient');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'EXPIRED':
                $statusArray['StatusMsg'] = __('Failed to deliver message - delivery term is expired');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'REJECTD':
                $statusArray['StatusMsg'] = __('Message is rejected by server');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;
        }

        return $statusArray;
    }


    /**
     * As MobiClub needs phone numbers to be only in 38 0YY XXX XX XX format
     * this function will try to make the phone number suitable
     *
     * @param $phoneNumber string
     *
     * @return string
     */
    protected function makePhoneFormat($phoneNumber) {
        $phoneNumber = str_replace('+', '', $phoneNumber);

        if ( strlen($phoneNumber) == 10 ) {
            if (substr($phoneNumber, 0, 1) == '0') {
                $phoneNumber = '8' . $phoneNumber;
            }

            if (substr($phoneNumber, 0, 1) == '8') {
                $phoneNumber = '3' . $phoneNumber;
            }
        }

        return $phoneNumber;
    }
}
?>