<?php

class RedSms extends SMSServiceApi {

    public function __construct($smsServiceId, $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
    }

    public function getBalance() {
        $result = '';
        $timestamp = microtime() . rand(0, 10000);
        $api_key = $this->serviceApiKey;
        $login = $this->serviceLogin;

        $signature = md5($timestamp . $api_key);
        $query = $this->serviceGatewayAddr . "/client/info?login=" . $login . "&secret=" . $signature . "&ts=" . $timestamp;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $query);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        $response = curl_exec($curl);
        $response = json_decode($response);
        curl_close($curl);

        if (empty($response->{"error_message"})) {
            $ballance = __('Current account balance') . ': ' . $response->{"info"}->{"balance"} . ' RUR';
            $msgType  = 'info';
        } else {
            $ballance = $response->{"error_message"};
            $msgType  = 'warning';
        }

        //$result.= wf_BackLink(self::URL_ME, '', true);
        $result.= $this->instanceSendDog->getUbillingMsgHelperInstance()->getStyledMessage($ballance, $msgType);
        //return ($result);
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        $result = '';
        $timestamp = microtime().rand(0, 10000);
        $api_key = $this->serviceApiKey;
        $login = $this->serviceLogin;
        $sender = $this->serviceAlphaName;

        $allSmsQueue = $this->smsMessagePack;
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $io => $eachsms) {

                $phone = $eachsms['number'];
                $text = $eachsms['message'];
                


                $signature = md5($timestamp . $api_key);
                $data = array(
                    'login' => $login,
                    'secret' => $signature,
                    'to' => $phone,
                    'from' => $sender,
                    'ts' => $timestamp,
                    'text' => $text
                );
                $postdata = http_build_query($data);
                $query = $this->serviceGatewayAddr . "/message?" . $postdata;
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $query);
                curl_setopt($curl, CURLOPT_ENCODING, "utf-8");
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
                curl_setopt($curl, CURLOPT_TIMEOUT, 120);
                curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
                curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($curl, CURLOPT_POST, true);
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
