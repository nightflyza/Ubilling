<?php

class LifeCell extends SMSServiceApi {
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
        $result = '';
        $apiUrl = $this->serviceGatewayAddr;
        $source = $this->instanceSendDog->safeEscapeString($this->serviceAlphaName);

        $login = $this->serviceLogin;
        $password = $this->servicePassword;

        $allSmsQueue = $this->smsMessagePack;
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $io => $eachsms) {
                $number = str_replace('+', '', $eachsms['number']); //numbers in international format without +
                $params = array('http' =>
                    array(
                        'method' => 'POST',
                        'header' => array('Authorization: Basic ' . base64_encode($login . ":" . $password), 'Content-Type:text/xml'),
                        'content' => '<message><service id="single" source="' . $source . '"/>
                            <to>' . $number . '</to>
                            <body content-type="text/plain">' . $eachsms['message'] . '</body></message>'));

                $ctx = stream_context_create($params);
                $fp = @fopen($apiUrl, 'rb', FALSE, $ctx);
                if ($fp) {
                    $response = @stream_get_contents($fp);
                }

                //remove old sent message
                $this->instanceSendDog->getSmsQueueInstance()->deleteSms($eachsms['filename']);
            }
        }
    }

    public function checkMessagesStatuses() {
        log_register('Checking statuses for [' . get_class($this) . '] SMS service is not implemented');
    }
}