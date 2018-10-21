<?php

class SkySMS extends SMSSrvAPI {
    public function __construct($SMSSrvID, $SMSPack = array()) {
        parent::__construct($SMSSrvID, $SMSPack);
    }

    public function getBalance() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        if ( !empty($this->SMSMsgPack) ) {
            global $ubillingConfig;
            $i = 0;
            $SMSHistoryEnabled = $ubillingConfig->getAlterParam('SMS_HISTORY_ON');
            $SMSAdvancedEnabled = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
            $SMSHistoryTabFreshIDs = array();
            $PreSendStatus = __('Perparing for delivery');
            $Telepatia = new Telepathy(false);

            if ($SMSHistoryEnabled) {
                $Telepatia->flushPhoneTelepathyCache();
                $Telepatia->usePhones();
            }

            $XMLPacket = '<?xml version="1.0" encoding="utf-8"?>
                          <packet version="1.0">
                          <auth login="' . $this->SrvLogin . '" password="' . $this->SrvPassword . '"/>
                          <command name="sendmessage">
                          <message id="0" type="sms">
                          <data charset="lat"></data>
                          <recipients>
                         ';

            foreach ($this->SMSMsgPack as $io => $eachsms) {
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

                    $XMLPacket .= '<recipient id="' . $RecID . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                } else {
                    $XMLPacket .= '<recipient id="' . ++$i . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                }

                $this->SendDog->getSMSQueueInstance()->deleteSms($eachsms['filename']);
            }

            $Telepatia->savePhoneTelepathyCache();

            $XMLPacket .= '</recipients>
                            </message>
                            </command>
                            </packet>
                          ';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_URL, $this->SrvGatewayAddr);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLPacket);
            $Result = curl_exec($curl);
            curl_close($curl);

            $ParsedResult = zb_xml2array($Result);

            if ( !empty($ParsedResult) ) {
                $ServerAnswerCode = (isset($ParsedResult['packet']['result_attr']['type'])) ? $ParsedResult['packet']['result_attr']['type'] : '42';

                if ($ServerAnswerCode == '00') {
                    $SMSPacketID = $ParsedResult['packet']['result']['message_attr']['smsmsgid'];
                    log_register('SENDDOG SKYSMS packet ' . $SMSPacketID . ' sent successfully');

                    if ($SMSHistoryEnabled) {
                        $Recipients = $ParsedResult['packet']['result']['message']['recipients']['recipient'];

                        if ( empty($Recipients) ) { $Recipients = $ParsedResult['packet']['result']['message']['recipients']; }

                        foreach ($Recipients as $each => $Recipient) {
                            if ( isset($Recipient['id']) ) {
                                $tQuery = "UPDATE `sms_history` SET `srvmsgself_id` = '" . $Recipient['smsid'] . "', 
                                                                    `srvmsgpack_id` = '" . $SMSPacketID . "',                                                            
                                                                    `date_send` = '" . curdatetime() . "', 
                                                                    `send_status` = '" . __('Message queued') . "' 
                                                WHERE `id` = '" . $Recipient['id'] . "';";
                                nr_query($tQuery);
                            }
                        }
                    }
                } else {
                    $ServerErrorMsg = $this->decodeSkySMSErrorMsg($ServerAnswerCode);
                    log_register('SENDDOG SKYSMS failed to sent SMS packet. Server answer: ' . $ServerErrorMsg . ( ($ServerAnswerCode == '42') ? $Result : '') );

                    if ($SMSHistoryEnabled) {
                        $IDsAsStr = implode(',', $SMSHistoryTabFreshIDs);
                        $tQuery = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "',
                                                            `date_statuschk` = '" . curdatetime() . "',
                                                            `no_statuschk` = '1', 
                                                            `send_status` = '" . __('Failed to send message') . ': ' . $ServerErrorMsg ."' 
                                        WHERE `id` IN (" . $IDsAsStr . ");";
                        nr_query($tQuery);
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
        if ( empty($this->SMSMsgPack) ) {
            if ($this->IsDefaultService) {
                $tQuery = "SELECT DISTINCT `srvmsgpack_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND (`smssrvid` = " . $this->SrvID . " OR `smssrvid` = 0);";
            } else {
                $tQuery = "SELECT DISTINCT `srvmsgpack_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1 AND `smssrvid` = " . $this->SrvID . ";";
            }
            $ChkMessages = simple_queryall($tQuery);
        } else { $ChkMessages = $this->SMSMsgPack; }

        if ( !empty($ChkMessages) ) {
            $SkySMSAPIURL   = $this->SrvGatewayAddr;
            $SkySMSAPILogin = $this->SrvLogin;
            $SkySMSAPIPassw = $this->SrvPassword;

            foreach ($ChkMessages as $io => $EachMsg) {
                $SMSPAcketID = $EachMsg['srvmsgpack_id'];

                if ( empty($SMSPAcketID) ) { continue; }

                $XMLPacket = '<?xml version="1.0" encoding="utf-8"?>
                              <packet version="1.0">
                              <auth login="' . $SkySMSAPILogin . '" password="' . $SkySMSAPIPassw . '"/>
                              <command name="querymessage">
                              <message smsmsgid="' . $SMSPAcketID . '"/>
                              </command>
                              </packet>
                             ';

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_URL, $SkySMSAPIURL);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLPacket);
                $Result = curl_exec($curl);
                curl_close($curl);

                $ParsedResult = zb_xml2array($Result);

                if ( !empty($ParsedResult) ) {
                    $ServerAnswerCode = (isset($ParsedResult['packet']['result_attr']['type'])) ? $ParsedResult['packet']['result_attr']['type'] : '42';

                    if ($ServerAnswerCode == '00') {
                        $Recipients = $ParsedResult['packet']['result']['message']['recipients']['recipient'];

                        if ( empty($Recipients) ) { $Recipients = $ParsedResult['packet']['result']['message']['recipients']; }

                        foreach ($Recipients as $each => $Recipient) {
                            if (isset($Recipient['smsid'])) {
                                $MsgSelfID         = $Recipient['smsid'];
                                $MsgStatus         = $Recipient['status'];
                                $DecodedMsgStatus  = $this->decodeSkySMSStatusMsg($MsgStatus);

                                $tQuery = "UPDATE `sms_history` SET `date_statuschk` = '". curdatetime() . "', 
                                                                    `delivered` = '" . $DecodedMsgStatus['DeliveredStatus'] . "', 
                                                                    `no_statuschk` = '" . $DecodedMsgStatus['NoStatusCheck'] . "', 
                                                                    `send_status` = '" . $DecodedMsgStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $MsgSelfID . "';";
                                nr_query($tQuery);
                            }
                        }

                        log_register('SENDDOG SKYSMS checked SMS packet ' . $SMSPAcketID . ' send status');
                    } else {
                        $ServerErrorMsg = $this->decodeSkySMSErrorMsg($ServerAnswerCode);
                        log_register('SENDDOG SKYSMS failed to get SMS packet ' . $SMSPAcketID . ' send status. Server answer: ' . $ServerErrorMsg . ( ($ServerAnswerCode == '42') ? $Result : '') );
                    }
                }
            }
        }
    }

    /**
     * Gets the error message code as a parameter and returns appropriate message string
     *
     * @param string $ErrorMsgCode
     * @return string
     */
    protected function decodeSkySMSErrorMsg($ErrorMsgCode) {
        switch ($ErrorMsgCode) {
            case '01':
                $Message = __('Incorrect parameters value or insufficient parameters count');
                break;
            case '02':
                $Message = __('Database server connection error');
                break;
            case '03':
                $Message = __('Database was not found');
                break;
            case '04':
                $Message = __('Authorization procedure error');
                break;
            case '05':
                $Message = __('Login or password is incorrect');
                break;
            case '06':
                $Message = __('Malfunction in user\'s configuration');
                break;
            default:
                $Message = __('Error code is unknown. Servers answer:') . '  ' . $ErrorMsgCode;
        }

        return $Message;
    }

    /**
     * Gets the status message code as a parameter and returns appropriate message string
     *
     * @param  string $StatusMsgCode
     * @return array
     */
    protected function decodeSkySMSStatusMsg($StatusMsgCode) {
        $StatusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);

        switch ($StatusMsgCode) {
            case 'DELIVERED':
                $StatusArray['StatusMsg'] = __('Message is delivered to recipient');
                $StatusArray['DeliveredStatus'] = 1;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'TOSEND':
                $StatusArray['StatusMsg'] = __('Message is queued for delivering');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'ENROUTE':
                $StatusArray['StatusMsg'] = __('Message is sent but not yet delivered to recipient');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'PAUSED':
                $StatusArray['StatusMsg'] = __('Message delivering is paused');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'CANCELED':
                $StatusArray['StatusMsg'] = __('Message delivering is canceled');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'FAILED':
                $StatusArray['StatusMsg'] = __('Failed to send message');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'EXPIRED':
                $StatusArray['StatusMsg'] = __('Failed to deliver message - delivery term is expired');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'UNDELIVERABLE':
                $StatusArray['StatusMsg'] = __('Message can not be delivered to recipient');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'REJECTED':
                $StatusArray['StatusMsg'] = __('Message is rejected by server');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'BADCOST':
                $StatusArray['StatusMsg'] = __('Message is not delivered to recipient - can not determine message cost');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'UNKNOWN':
                $StatusArray['StatusMsg'] = __('Message status is unknown');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            default:
                $StatusArray['StatusMsg'] = __('Sending status code is unknown:') . '  ' . $StatusMsgCode;
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
        }

        return $StatusArray;
    }
}

?>