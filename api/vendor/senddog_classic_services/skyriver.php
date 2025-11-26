<?php

class skyriver extends SendDogProto {

    /**
     * Loads Skyriver service config
     *
     * @return void
     */
    public function loadSkyriverConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_SKYSMS_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'http://sms.skysms.net/api/bulk_sm';
            zb_StorageSet('SENDDOG_SKYSMS_GATEWAY', $smsgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_SKYSMS_LOGIN');
        if (empty($smslogin)) {
            $smslogin = 'InfoCentr';
            zb_StorageSet('SENDDOG_SKYSMS_LOGIN', $smslogin);
        }

        $smspassword = zb_StorageGet('SENDDOG_SKYSMS_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = 'MySecretPassword';
            zb_StorageSet('SENDDOG_SKYSMS_PASSWORD', $smspassword);
        }

        $this->settings['SKYSMS_GATEWAY'] = $smsgateway;
        $this->settings['SKYSMS_LOGIN'] = $smslogin;
        $this->settings['SKYSMS_PASSWORD'] = $smspassword;
    }

    /**
     * Returns set of inputs, required for Skyriver service configuration
     *
     * @return string
     */
    public function renderSkyriverConfigInputs() {
        $inputs = wf_tag('h2') . 'Skyriver' . wf_tag('h2', true);
        $inputs .= wf_TextInput('editskysmsgateway', __('Skyriver API address'), $this->settings['SKYSMS_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editskysmslogin', __('User login to access Skyriver API (this is sign also)'), $this->settings['SKYSMS_LOGIN'], true, 20);
        $inputs .= wf_TextInput('editskysmspassword', __('User password for access Skyriver API'), $this->settings['SKYSMS_PASSWORD'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'skyriver') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use Skyriver as default SMS service'), 'skyriver', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {
        //Skyriver configuration
        if ($_POST['editskysmsgateway'] != $this->settings['SKYSMS_GATEWAY']) {
            zb_StorageSet('SENDDOG_SKYSMS_GATEWAY', $_POST['editskysmsgateway']);
            log_register('SENDDOG CONFIG SET SKYSMSGATEWAY `' . $_POST['editskysmsgateway'] . '`');
        }
        if ($_POST['editskysmslogin'] != $this->settings['SKYSMS_LOGIN']) {
            zb_StorageSet('SENDDOG_SKYSMS_LOGIN', $_POST['editskysmslogin']);
            log_register('SENDDOG CONFIG SET SKYSMSLOGIN `' . $_POST['editskysmslogin'] . '`');
        }
        if ($_POST['editskysmspassword'] != $this->settings['SKYSMS_PASSWORD']) {
            zb_StorageSet('SENDDOG_SKYSMS_PASSWORD', $_POST['editskysmspassword']);
            log_register('SENDDOG CONFIG SET SKYSMSPASSWORD `' . $_POST['editskysmspassword'] . '`');
        }
    }

    /**
     * Sends all sms storage via SKYSMS service
     *
     * @return void
     */
    public function skysmsPushMessages() {
        $result = '';
        $skySmsApiUrl = $this->settings['SKYSMS_GATEWAY'];
        $skySmsApiLogin = $this->settings['SKYSMS_LOGIN'];
        $skySsmApiPassw = $this->settings['SKYSMS_PASSWORD'];

        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            $i = 0;
            $smsHistoryEnabled = $this->ubConfig->getAlterParam('SMS_HISTORY_ON');
            $smsHistoryTabFreshIds = array();
            $preSendStatus = __('Perparing for delivery');
            $telepatia = new Telepathy(false);

            if ($smsHistoryEnabled) {
                $telepatia->flushPhoneTelepathyCache();
                $telepatia->usePhones();
            }

            $xmlPacket = '<?xml version="1.0" encoding="utf-8"?>
                          <packet version="1.0">
                          <auth login="' . $skySmsApiLogin . '" password="' . $skySsmApiPassw . '"/>
                          <command name="sendmessage">
                          <message id="0" type="sms">
                          <data charset="lat"></data>
                          <recipients>
                         ';

            foreach ($allSmsQueue as $io => $eachsms) {
                if ($smsHistoryEnabled) {
                    $phoneToSearch = $this->cutInternationalsFromPhoneNum($eachsms['number']);
                    $login = $telepatia->getByPhoneFast($phoneToSearch);

                    $query = "INSERT INTO `sms_history` (`login`, `phone`, `send_status`, `msg_text`) 
                                                  VALUES ('" . $login . "', '" . $eachsms['number'] . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                    nr_query($query);

                    $recId = simple_get_lastid('sms_history');
                    $smsHistoryTabFreshIds[] = $recId;

                    $xmlPacket .= '<recipient id="' . $recId . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                } else {
                    $xmlPacket .= '<recipient id="' . ++$i . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                }

                $this->smsQueue->deleteSms($eachsms['filename']);
            }

            $telepatia->savePhoneTelepathyCache();

            $xmlPacket .= '</recipients>
                            </message>
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
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl); // Deprecated in PHP 8.5
            }

            $parsedResult = zb_xml2array($result);

            if (!empty($parsedResult)) {
                $serverAnswerCode = (isset($parsedResult['packet']['result_attr']['type'])) ? $parsedResult['packet']['result_attr']['type'] : '42';

                if ($serverAnswerCode == '00') {
                    $smsPacketID = $parsedResult['packet']['result']['message_attr']['smsmsgid'];
                    log_register('SENDDOG SKYSMS packet ' . $smsPacketID . ' sent successfully');

                    if ($smsHistoryEnabled) {
                        $recipients = $parsedResult['packet']['result']['message']['recipients']['recipient'];

                        if (empty($recipients)) {
                            $recipients = $parsedResult['packet']['result']['message']['recipients'];
                        }

                        foreach ($recipients as $each => $Recipient) {
                            if (isset($Recipient['id'])) {
                                $query = "UPDATE `sms_history` SET `srvmsgself_id` = '" . $Recipient['smsid'] . "', 
                                                                    `srvmsgpack_id` = '" . $smsPacketID . "',                                                            
                                                                    `date_send` = '" . curdatetime() . "', 
                                                                    `send_status` = '" . __('Message queued') . "' 
                                                WHERE `id` = '" . $Recipient['id'] . "';";
                                nr_query($query);
                            }
                        }
                    }
                } else {
                    $serverErrorMsg = $this->decodeSkySmsErrorMessage($serverAnswerCode);
                    log_register('SENDDOG SKYSMS failed to sent SMS packet. Server answer: ' . $serverErrorMsg . ( ($serverAnswerCode == '42') ? $result : ''));

                    if ($smsHistoryEnabled) {
                        $idsAsStr = implode(',', $smsHistoryTabFreshIds);
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

    /**
     * Checks messages status for SKYSMS service
     *
     * @return void
     */
    public function skysmsCheckMessagesStatus() {
        $smsCheckStatusExpireDays = $this->altCfg['SMS_CHECKSTATUS_EXPIRE_DAYS'];
        $query = "UPDATE `sms_history` SET `no_statuschk` = 1,
                                            `send_status` = '" . __('SMS status check period expired') . "'
                        WHERE ABS( DATEDIFF(NOW(), `date_send`) ) > " . $smsCheckStatusExpireDays . " AND no_statuschk < 1;";
        nr_query($query);

        $query = "SELECT DISTINCT `srvmsgpack_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1;";
        $chkMessages = simple_queryall($query);

        if (!empty($chkMessages)) {
            $skySmsApiUrl = $this->settings['SKYSMS_GATEWAY'];
            $skySmsApiLogin = $this->settings['SKYSMS_LOGIN'];
            $skySmsApiPassw = $this->settings['SKYSMS_PASSWORD'];

            foreach ($chkMessages as $io => $eachmessage) {
                $smsPacketID = $eachmessage['srvmsgpack_id'];

                if (empty($smsPacketID)) {
                    continue;
                }

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
                if (PHP_VERSION_ID < 80000) {
                    curl_close($curl); // Deprecated in PHP 8.5
                }

                $parsedResult = zb_xml2array($result);

                if (!empty($parsedResult)) {
                    $serverAnswerCode = (isset($parsedResult['packet']['result_attr']['type'])) ? $parsedResult['packet']['result_attr']['type'] : '42';

                    if ($serverAnswerCode == '00') {
                        $recipients = $parsedResult['packet']['result']['message']['recipients']['recipient'];

                        if (empty($recipients)) {
                            $recipients = $parsedResult['packet']['result']['message']['recipients'];
                        }

                        foreach ($recipients as $each => $recipient) {
                            if (isset($recipient['smsid'])) {
                                $messageId = $recipient['smsid'];
                                $messageStatus = $recipient['status'];
                                $decodedMessageStatus = $this->decodeSkySmsStatusMessage($messageStatus);

                                $query = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                                    `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                                    `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                                    `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $messageId . "';";
                                nr_query($query);
                            }
                        }

                        log_register('SENDDOG SKYSMS checked SMS packet ' . $smsPacketID . ' send status');
                    } else {
                        $serverErrorMsg = $this->decodeSkySmsErrorMessage($serverAnswerCode);
                        log_register('SENDDOG SKYSMS failed to get SMS packet ' . $smsPacketID . ' send status. Server answer: ' . $serverErrorMsg . ( ($serverAnswerCode == '42') ? $result : ''));
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
    public function decodeSkySmsErrorMessage($errorMsgCode) {
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
    public function decodeSkySmsStatusMessage($statusMsgCode) {
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
