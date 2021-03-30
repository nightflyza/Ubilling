<?php

class SmsFly extends SMSServiceApi {
    public function __construct($smsServiceId, $smsPack = array()) {
        parent::__construct($smsServiceId, $smsPack);
    }

    public function getBalance() {
        $result = '';

        $myXML = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $myXML .= "<request>";
        $myXML .= "<operation>GETBALANCE</operation>";
        $myXML .= "</request>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, $this->serviceLogin . ':' . $this->servicePassword);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->serviceGatewayAddr);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
        $response = curl_exec($ch);
        curl_close($ch);

        //$result.= wf_BackLink($this->sendDog->getBaseUrl(), '', true);
        $result.= $this->instanceSendDog->getUBMsgHelperInstance()->getStyledMessage(__('Current account balance') . ': ' . $response, 'info');
        //return ($result);
        die(wf_modalAutoForm(__('Balance'), $result, $_POST['modalWindowId'], '', true, 'false', '700'));
    }

    public function getSMSQueue() {
        $this->showErrorFeatureIsNotSupported();
    }

    public function pushMessages() {
        $result = '';
        $apiUrl = $this->serviceGatewayAddr;
        $source = $this->instanceSendDog->safeEscapeString($this->serviceAlphaName);
        $description = "Ubilling_" . zb_rand_string(8);
        $start_time = 'AUTO';
        $end_time = 'AUTO';
        $rate = 1;
        $lifetime = 4;

        $user = $this->serviceLogin;
        $password = $this->servicePassword;

        $allSmsQueue = $this->smsMessagePack;
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $io => $eachsms) {
                $number = str_replace('+', '', $eachsms['number']); //numbers in international format without +
                $myXML = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
                $myXML .= "<request>";
                $myXML .= "<operation>SENDSMS</operation>";
                $myXML .= '		<message start_time="' . $start_time . '" end_time="' . $end_time . '" lifetime="' . $lifetime . '" rate="' . $rate . '" desc="' . $description . '" source="' . $source . '">' . "\n";
                $myXML .= "		<body>" . $eachsms['message'] . "</body>";
                $myXML .= "		<recipient>" . $number . "</recipient>";
                $myXML .= "</message>";
                $myXML .= "</request>";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, $apiUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
                $result.= curl_exec($ch);
                curl_close($ch);

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