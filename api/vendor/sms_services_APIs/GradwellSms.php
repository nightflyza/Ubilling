<?php

class GradwellSms extends SMSServiceApi {
    public function __construct($smsServiceId, array $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
    }

    public function pushMessages() {
        global $ubillingConfig;
        $apikey = $this->serviceApiKey;
        $sender = $this->serviceAlphaName;
        $smsHistoryEnabled = $ubillingConfig->getAlterParam('SMS_HISTORY_ON');
        $smsAdvancedEnabled = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
        $telepatia = new Telepathy(false);

        if ($smsHistoryEnabled) {
            $telepatia->flushPhoneTelepathyCache();
            $telepatia->usePhones();
        }

        $allSmsQueue = $this->smsMessagePack;
        if (!empty($allSmsQueue)) {
            $smsPacketId = strtoupper(md5(uniqid(rand(), true)));
            log_register('SENDDOG GRADWELLSMS sending SMS packet ' . $smsPacketId);

            foreach ($allSmsQueue as $eachsms) {
                $url = $this->serviceGatewayAddr
                    . '?auth=' . $apikey
                    . '&originator=' . $sender
                    . '&destination=' . str_replace(array('+', '440'), array('', '44'), $eachsms['number'])
                    . '&message=' . urlencode($eachsms['message']);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);

                if (strpos($result, 'OK:') !== false) {
                    if (strpos($result, 'id') !== false and $smsHistoryEnabled) {
                        $idPos  = strpos($result, 'id');
                        $messID = trim(substr($result, $idPos + 2));

                        //$PhoneToSearch = $this->sendDog->cutInternationalsFromPhoneNum($eachsms['number']);
                        $Login = $telepatia->getByPhoneFast($eachsms['number']);

                        if ($smsAdvancedEnabled) {
                            $query = "INSERT INTO `sms_history` (`smssrvid`, `login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `date_send`, `send_status`, `msg_text`) 
                                                  VALUES (" . $this->serviceId . ", '" . $Login . "', '" . $eachsms['number'] . "', '" . $messID . "', '" . $smsPacketId . "', '" . curdatetime() . "', '" . __('Message queued') . "', '" . $eachsms['message'] . "');";
                        } else {
                            $query = "INSERT INTO `sms_history` (`login`, `phone`, `srvmsgself_id`, `srvmsgpack_id`, `date_send`, `send_status`, `msg_text`) 
                                                  VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '" . $messID . "', '" . $smsPacketId . "', '" . curdatetime() . "', '" . __('Message queued') . "', '" . $eachsms['message'] . "');";
                        }
                        nr_query($query);
                    } else {
                        log_register('SENDDOG GRADWELLSMS message ID not found - can\'t write history:  ' . $result);
                    }
                } elseif (strpos($result, 'ERR:') !== false) {
                    log_register('SENDDOG GRADWELLSMS delivery failed with error:  ' . $result);
                } else {
                    log_register('SENDDOG GRADWELLSMS unknown server answer:  ' . $result);
                }

                //remove old sent message
                $this->instanceSendDog->getSmsQueueInstance()->deleteSms($eachsms['filename']);
            }
        }
    }

    public function getBalance() {
        $this->showErrorFeatureIsNotSupported();
    }
    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public  function checkMessagesStatuses() {
        log_register('Checking statuses for [' . get_class($this) . '] SMS service is not implemented');
    }
}