<?php

class MobiClubSMS extends SMSSrvAPI {
    public function __construct($SMSSrvID, array $SMSPack = array()) {
        parent::__construct($SMSSrvID, $SMSPack);
    }

    public function getBalance() {
        $result = '';
        $url = 'https://gate.smsclub.mobi/http/getbalance.php?';
        $url_result = $url . 'username=' . $this->SrvLogin . '&password=' . $this->SrvPassword;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url_result);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl);
        curl_close($curl);

        $result.= $this->SendDog->getUbillingMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $response, 'info');
        //return ($result);
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['ModalWID'], '', true, 'false', '700'));
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        if (!empty($this->SMSMsgPack)) {
            global $ubillingConfig;
            $i = 0;
            $SMSHistoryEnabled = $ubillingConfig->getAlterParam('SMS_HISTORY_ON');
            $SMSAdvancedEnabled = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');

            $SMSHistoryTabFreshIDs = array();
            $SMSHistoryTabPhonesIDs = array();
            $PreSendStatus = __('Perparing for delivery');
            $Telepatia = new Telepathy(false);

            if ($SMSHistoryEnabled) {
                $Telepatia->flushPhoneTelepathyCache();
                $Telepatia->usePhones();
            }

            $XMLPacket  = '<?xml version="1.0" encoding="utf-8"?><request_sendsms><username><![CDATA[' . $this->SrvLogin . ']]></username>';
            $XMLPacket .= '<password><![CDATA[' . $this->SrvPassword . ']]></password><from><![CDATA[' . $this->SrvAlphaName . ']]></from>';

            foreach ($this->SMSMsgPack as $io => $eachsms) {
                $FormattedPhone = $this->makePhoneFormat($eachsms['number']);

                if ($SMSHistoryEnabled) {
                    //$PhoneToSearch = $this->SendDog->cutInternationalsFromPhoneNum($eachsms['number']);
                    $Login = $Telepatia->getByPhoneFast($eachsms['number']);

                    if ($SMSAdvancedEnabled) {
                        $tQuery = "INSERT INTO `sms_history` (`smssrvid`, `login`, `phone`, `send_status`, `msg_text`) 
                                                      VALUES (" . $this->SrvID . ", '" . $Login . "', '" . $eachsms['number'] . "', '" . $PreSendStatus . "', '" . $eachsms['message'] . "');";
                    } else {
                        $tQuery = "INSERT INTO `sms_history` (`login`, `phone`, `send_status`, `msg_text`) 
                                                      VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '" . $PreSendStatus . "', '" . $eachsms['message'] . "');";
                    }
                    nr_query($tQuery);

                    $RecID = simple_get_lastid('sms_history');
                    $SMSHistoryTabFreshIDs[] = $RecID;
                    $SMSHistoryTabPhonesIDs[$FormattedPhone] = $RecID;
                }

                $XMLPacket .= '<to><![CDATA[' . $FormattedPhone . ']]></to><text><![CDATA[' . $eachsms['message'] . ']]></text>';

                $this->SendDog->getSMSQueueInstance()->deleteSms($eachsms['filename']);
            }

            $Telepatia->savePhoneTelepathyCache();
            $XMLPacket .= '</request_sendsms>';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_CRLF, true);
            curl_setopt($curl, CURLOPT_URL, $this->SrvGatewayAddr . 'individual.php');
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLPacket);
            $Result = curl_exec($curl);
            curl_close($curl);

            $ParsedResult = zb_xml2array($Result);

            if ( !empty($ParsedResult) ) {
                $SendStatus   = $ParsedResult['response']['status'];
                $ServerAnswer = $ParsedResult['response']['text'];

                if (strtolower($SendStatus) == 'ok') {
                    $SMSPacketID = strtoupper(md5(uniqid(rand(), true)));
                    log_register('SENDDOG MobiClubSMS packet ' . $SMSPacketID . ' sent successfully');

                    if ($SMSHistoryEnabled) {
                        $MsgPhone = '';
                        $PhonesSrvIDs = array();

                        if ( isset($ParsedResult['response']['ids']['mess_attr']['tel']) ) {
                            $PhonesSrvIDs[$ParsedResult['response']['ids']['mess_attr']['tel']] = $ParsedResult['response']['ids']['mess'];
                        } else {
                            $MsgsIDs = $ParsedResult['response']['ids']['mess'];

                            foreach ($MsgsIDs as $key => $val) {
                                if (stristr($key, 'attr') !== FALSE) {
                                    continue;
                                }

                                $PhoneKeyIndex = $key . '_attr';
                                $MsgID = $val;

                                if ($MsgPhone !== $MsgsIDs[$PhoneKeyIndex]['tel']) {
                                    $MsgPhone = $MsgsIDs[$PhoneKeyIndex]['tel'];
                                } else {
                                    continue;
                                }

                                $PhonesSrvIDs[$MsgPhone] = $MsgID;
                            }
                        }

                        if ( !empty($SMSHistoryTabPhonesIDs) and !empty($PhonesSrvIDs) ) {
                            foreach ($SMSHistoryTabPhonesIDs as $Phone => $RecID) {
                                $SrvMsgID = $PhonesSrvIDs[$Phone];

                                $tQuery = "UPDATE `sms_history` SET `srvmsgself_id` = '" . $SrvMsgID . "', 
                                                                    `srvmsgpack_id` = '" . $SMSPacketID . "',                                                            
                                                                    `date_send` = '" . curdatetime() . "', 
                                                                    `send_status` = '" . __('Message queued') . "' 
                                                WHERE `id` = '" . $RecID . "';";
                                nr_query($tQuery);
                            }
                        }
                    }
                } else {
                    log_register('SENDDOG MobiClubSMS failed to send SMS packet. Server answer: ' . $ServerAnswer);

                    if ($SMSHistoryEnabled) {
                        $IDsAsStr = implode(',', $SMSHistoryTabFreshIDs);
                        $tQuery = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "',
                                                            `date_statuschk` = '" . curdatetime() . "',
                                                            `no_statuschk` = '1', 
                                                            `send_status` = '" . __('Failed to send message') . ': ' . $ServerAnswer ."' 
                                        WHERE `id` IN (" . $IDsAsStr . ");";
                        nr_query($tQuery);
                    }
                }
            }
        }
    }

    public function checkMessagesStatuses() {
        if ( empty($this->SMSMsgPack) ) {
            if ($this->IsDefaultService) {
                $tQuery = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND (`smssrvid` = " . $this->SrvID . " OR `smssrvid` = 0);";
            } else {
                $tQuery = "SELECT DISTINCT `srvmsgself_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND `smssrvid` = " . $this->SrvID . ";";
            }
            $ChkMessages = simple_queryall($tQuery);
        } else { $ChkMessages = $this->SMSMsgPack; }

        if ( !empty($ChkMessages) ) {
            $MsgsIDs = array();

            foreach ($ChkMessages as $io => $EachMsg) {
                $MsgsIDs[] = $EachMsg['srvmsgself_id'];
            }

            $MsgsIDsList = implode(';', $MsgsIDs);

            $XMLPacket  = '<?xml version="1.0" encoding="utf-8"?><request_getstate><username><![CDATA[' . $this->SrvLogin . ']]></username>';
            $XMLPacket .= '<password><![CDATA[' . $this->SrvPassword . ']]></password><smscid><![CDATA[' . $MsgsIDsList . ']]></smscid>';
            $XMLPacket .= '</request_getstate>';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_CRLF, true);
            curl_setopt($curl, CURLOPT_URL, $this->SrvGatewayAddr . 'state.php');
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLPacket);
            $Result = curl_exec($curl);
            curl_close($curl);

            $ParsedResult = zb_xml2array($Result);

            if ( !empty($ParsedResult) ) {
                $SendStatus   = $ParsedResult['response']['status'];
                $ServerAnswer = $ParsedResult['response']['text'];

                if (strtolower($SendStatus) == 'ok') {
                    $StatusEntries = $ParsedResult['response']['entries']['entry'];

                    if ( isset($StatusEntries['smscid']) and isset($StatusEntries['state'])) {
                        $MsgSelfID = $StatusEntries['smscid'];
                        $MsgStatus = $StatusEntries['state'];
                        $DecodedMsgStatus = $this->decodeMobiSMSStatusMsg($MsgStatus);

                        $tQuery = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                            `delivered` = '" . $DecodedMsgStatus['DeliveredStatus'] . "', 
                                                            `no_statuschk` = '" . $DecodedMsgStatus['NoStatusCheck'] . "', 
                                                            `send_status` = '" . $DecodedMsgStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $MsgSelfID . "';";
                        nr_query($tQuery);
                    } else {
                        foreach ($StatusEntries as $io => $EachEntry) {
                            $MsgSelfID = $EachEntry['smscid'];
                            $MsgStatus = $EachEntry['state'];
                            $DecodedMsgStatus = $this->decodeMobiSMSStatusMsg($MsgStatus);

                            $tQuery = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                            `delivered` = '" . $DecodedMsgStatus['DeliveredStatus'] . "', 
                                                            `no_statuschk` = '" . $DecodedMsgStatus['NoStatusCheck'] . "', 
                                                            `send_status` = '" . $DecodedMsgStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $MsgSelfID . "';";
                            nr_query($tQuery);
                        }
                    }

                    log_register('SENDDOG MobiClubSMS checked statuses for ' . count($ChkMessages) . ' messages.');
                } else {
                    log_register('SENDDOG MobiClubSMS failed to check messages statuses. Server answer: ' . $ServerAnswer);
                }
            }
        }
    }

    /**
     * Gets the status message code as a parameter and returns appropriate message string
     *
     * @param  string $StatusMsgCode
     * @return array
     */
    protected function decodeMobiSMSStatusMsg($StatusMsgCode) {
        $StatusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);

        switch ($StatusMsgCode) {
            case 'DELIVRD':
                $StatusArray['StatusMsg'] = __('Message is delivered to recipient');
                $StatusArray['DeliveredStatus'] = 1;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'UNDELIV':
                $StatusArray['StatusMsg'] = __('Message can not be delivered to recipient');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'ENROUTE':
                $StatusArray['StatusMsg'] = __('Message is sent but not yet delivered to recipient');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'EXPIRED':
                $StatusArray['StatusMsg'] = __('Failed to deliver message - delivery term is expired');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'REJECTD':
                $StatusArray['StatusMsg'] = __('Message is rejected by server');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;
        }

        return $StatusArray;
    }


    /**
     * As MobiClub needs phone numbers to be only in 38 0YY XXX XX XX format
     * this function will try to make the phone number suitable
     *
     * @param $PhoneNumber string
     *
     * @return string
     */
    protected function makePhoneFormat($PhoneNumber) {
        $PhoneNumber = str_replace('+', '', $PhoneNumber);

        if ( strlen($PhoneNumber) == 10 ) {
            if (substr($PhoneNumber, 0, 1) == '0') {
                $PhoneNumber = '8' . $PhoneNumber;
            }

            if (substr($PhoneNumber, 0, 1) == '8') {
                $PhoneNumber = '3' . $PhoneNumber;
            }
        }

        return $PhoneNumber;
    }
}
?>