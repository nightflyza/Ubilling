<?php

class RedSms extends SMSServiceApi {
    public function __construct($smsServiceId, $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
    }

    public function getBalance() {
        $result = '';
        $timestamp = file_get_contents('https://lk.redsms.ru/get/timestamp.php');
        $api_key = $this->serviceApiKey;
        $login = $this->serviceLogin;
        $return = 'xml';
        $params = array(
            'timestamp' => $timestamp,
            'login' => $login,
            'return' => $return
        );
        ksort($params);
        reset($params);
        $signature = md5(implode($params) . $api_key);
        $query = "https://lk.redsms.ru/get/balance.php?login=" . $login . "&signature=" . $signature . "&timestamp=" . $timestamp . "&return=" . $return;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $query);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        //$result.= wf_BackLink(self::URL_ME, '', true);
        $result.= $this->instanceSendDog->getUbillingMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $response . ' RUR', 'info');
        //return ($result);
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        $result = '';
        $timestamp = file_get_contents('https://lk.redsms.ru/get/timestamp.php');
        $api_key = $this->serviceApiKey;
        $login = $this->serviceLogin;
        $return = 'xml';
        $sender = $this->serviceAlphaName;

        $allSmsQueue = $this->smsMessagePack;
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $io => $eachsms) {

                $phone = str_replace('+', '', $eachsms['number']); //numbers in international format without +
                $text = $eachsms['message'];


                $params = array(
                    'timestamp' => $timestamp,
                    'login' => $login,
                    'phone' => $phone,
                    'text' => $text,
                    'sender' => $sender,
                    'return' => $return);

                ksort($params);
                reset($params);
                $signature = md5(implode($params) . $api_key);
                $query = $this->serviceGatewayAddr . "?login=" . $login . "&signature=" . $signature . "&phone=" . $phone . "&sender=" . $sender . "&return=" . $return . "&timestamp=" . $timestamp . "&text=" . urlencode($text);
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $query);
                curl_setopt($curl, CURLOPT_ENCODING, "utf-8");
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
                curl_setopt($curl, CURLOPT_TIMEOUT, 120);
                curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);

                //remove old sent message
                $this->instanceSendDog->getSmsQueueInstance()->deleteSms($eachsms['filename']);
            }
        }
    }

    public function checkMessagesStatuses() {
        log_register('Checking statuses for [' . get_class($this) . '] SMS service is not implemented');
    }
}

?>