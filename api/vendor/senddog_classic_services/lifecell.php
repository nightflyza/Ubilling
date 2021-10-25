<?php

class lifecell extends SendDogProto {

    /**
     * Loads Lifecell service config
     * 
     * @return void
     */
    public function loadLifecellConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_LIFECELL_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'https://api.lifecell.com.ua/ip2sms/';
            zb_StorageSet('SENDDOG_LIFECELL_GATEWAY', $smsgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_LIFECELL_LOGIN');
        if (empty($smslogin)) {
            $smslogin = 'yourlogin';
            zb_StorageSet('SENDDOG_LIEFCELL_LOGIN', $smslogin);
        }

        $smspassword = zb_StorageGet('SENDDOG_LIFECELL_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = 'yourpassword';
            zb_StorageSet('SENDDOG_LIFECELL_PASSWORD', $smspassword);
        }
        $smssign = zb_StorageGet('SENDDOG_LIFECELL_SIGN');
        if (empty($smssign)) {
            $smssign = 'Alphaname';
            zb_StorageSet('SENDDOG_LIFECELL_SIGN', $smssign);
        }

        $this->settings['LIFECELL_GATEWAY'] = $smsgateway;
        $this->settings['LIFECELL_LOGIN'] = $smslogin;
        $this->settings['LIFECELL_PASSWORD'] = $smspassword;
        $this->settings['LIFECELL_SIGN'] = $smssign;
    }

    /**
     * Returns set of inputs, required for Lifecell service configuration
     * 
     * @return string
     */
    public function renderLifecellConfigInputs() {
        $inputs = wf_tag('h2') . __('Lifecell') . wf_tag('h2', true);
        $inputs .= wf_TextInput('editlifecellgateway', __('Lifecell API address'), $this->settings['LIFECELL_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editlifecelllogin', __('User login to access API'), $this->settings['LIFECELL_LOGIN'], true, 20);
        $inputs .= wf_TextInput('editlifecellpassword', __('User password for access API'), $this->settings['LIFECELL_PASSWORD'], true, 20);
        $inputs .= wf_TextInput('editlifecellsign', __('Lifecell') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['LIFECELL_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'lifecell') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use Lifecell as default SMS service'), 'lifecell', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {
        //Lifecell configuration
        if ($_POST['editlifecellgateway'] != $this->settings['LIFECELL_GATEWAY']) {
            zb_StorageSet('SENDDOG_LIFECELL_GATEWAY', $_POST['editlifecellgateway']);
            log_register('SENDDOG CONFIG SET LIFECELLGATEWAY `' . $_POST['editlifecellgateway'] . '`');
        }
        if ($_POST['editlifecelllogin'] != $this->settings['LIFECELL_LOGIN']) {
            zb_StorageSet('SENDDOG_LIFECELL_LOGIN', $_POST['editlifecelllogin']);
            log_register('SENDDOG CONFIG SET LIFECELLLOGIN `' . $_POST['editlifecelllogin'] . '`');
        }
        if ($_POST['editlifecellpassword'] != $this->settings['LIFECELL_PASSWORD']) {
            zb_StorageSet('SENDDOG_LIFECELL_PASSWORD', $_POST['editlifecellpassword']);
            log_register('SENDDOG CONFIG SET LIFECELLPASSWORD `' . $_POST['editlifecellpassword'] . '`');
        }
        if ($_POST['editlifecellsign'] != $this->settings['LIFECELL_SIGN']) {
            zb_StorageSet('SENDDOG_LIFECELL_SIGN', $_POST['editlifecellsign']);
            log_register('SENDDOG CONFIG SET LIFECELLSIGN `' . $_POST['editlifecellsign'] . '`');
        }
    }

    /**
     * Sends all sms storage via lifecell service
     * 
     * @return void
     */
    public function lifecellPushMessages() {
        $result = '';
        $apiUrl = $this->settings['LIFECELL_GATEWAY'];
        $source = $this->safeEscapeString($this->settings['LIFECELL_SIGN']);

        $login = $this->settings['LIFECELL_LOGIN'];
        $password = $this->settings['LIFECELL_PASSWORD'];

        $allSmsQueue = $this->smsQueue->getQueueData();
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
                $this->smsQueue->deleteSms($eachsms['filename']);
            }
        }
    }

}
