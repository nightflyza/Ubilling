<?php

class PilotSMS extends SMSSrvAPI {
    public function __construct($SMSSrvID, $SMSPack = array()) {
        parent::__construct($SMSSrvID, $SMSPack);
    }

    public function getBalance() {
        $balance = file_get_contents($this->SrvGatewayAddr
            . '?balance=rur'
            . '&apikey=' . $this->SrvAPIKey
        );

        //$result = wf_BackLink(self::URL_ME, '', true);
        $result = $this->SendDog->getUbillingMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $balance . ' RUR', 'info');
        //return $result;
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['ModalWID'], '', true, 'false', '700'));
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        $apikey = $this->SrvAPIKey;
        $sender = $this->SrvAlphaName;

        $allSmsQueue = $this->SMSMsgPack;
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $sms) {

                $url = $this->SrvGatewayAddr
                    . '?send=' . urlencode($sms['message'])
                    . '&to=' . urlencode($sms['number'])
                    . '&from=' . urlencode($sender)
                    . '&apikey=' . urlencode($apikey)
                    . '&format=json';

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $json = curl_exec($ch);
                curl_close($ch);

                $j = json_decode($json);
                if ($j && isset($j->error)) {
                    trigger_error($j->description_ru, E_USER_WARNING);
                }
                //remove old sent message
                $this->SendDog->getSMSQueueInstance()->deleteSms($sms['filename']);
            }
        }
    }

    public  function checkMessagesStatuses() {
        log_register('Checking statuses for [' . get_class($this) . '] SMS service is not implemented');
    }
}

?>