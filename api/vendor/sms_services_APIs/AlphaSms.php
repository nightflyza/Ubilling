<?php

class AlphaSms extends SMSServiceApi {
    public function __construct($smsServiceId, $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
    }

    public function getBalance() {
        $balance = '';
        $balance = $this->execute('balance');
        $result = $this->instanceSendDog->getUbillingMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $balance['balance'] . ' UAN', 'info');
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public  function checkMessagesStatuses() {
        log_register('Checking statuses for [' . get_class($this) . '] SMS service is not implemented');
    }

    public function pushMessages() {
        $apikey = $this->serviceApiKey;
        $sender = $this->serviceAlphaName;

        $allSmsQueue = $this->smsMessagePack;
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $sms) {
                $formattedPhone = $this->makePhoneFormat($sms['number']);
                $data = array('from' => urlencode($sender),
                               'to' => urlencode($formattedPhone),
                               'message'=> urlencode($sms['message']));
                $result = $this->execute('send', $data);
                //remove old sent message
                $this->instanceSendDog->getSmsQueueInstance()->deleteSms($sms['filename']);
            }
        }
    }

    protected function execute($command, $params = array()) {
            $params['login'] = $this->serviceLogin;
            $params['password'] = $this->servicePassword;
            $params['key'] = $this->serviceApiKey;
            $params['command'] = $command;
            $params_url = '';
            foreach($params as $key => $value) {
                $params_url .= '&' . $key . '=' . $this->base64_url_encode($value);
            }

            //cURL HTTPS POST
            $ch = curl_init($this->serviceGatewayAddr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_POST, count($params));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = @curl_exec($ch);
            curl_close($ch);

            $this->response = @unserialize($this->base64_url_decode($response));

            return $this->response;
    }

    protected function base64_url_encode($input) {
        return strtr(base64_encode($input), '+/=', '-_,');
    }

    protected function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_,', '+/='));
    }

    /**
     * As AlphaSMS needs phone numbers to be only in 38 0YY XXX XX XX format
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