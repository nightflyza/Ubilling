<?php

class alphasms extends SendDogProto {

    /**
     * Returns set of inputs, required for AlphaSMS service configuration
     *
     * @return string
     */
    public function renderAlphasmsConfigInputs() {
        $inputs = wf_tag('h2') . __('AlphaSMS') . ' ' . wf_Link(self::URL_ME . '&showmisc=alphasms', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs .= wf_TextInput('editalphasmsgateway', __('API address') . ' ' . __('AlphaSMS'), $this->settings['ALPHASMS_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editalphasmslogin', __('User login to access API'), $this->settings['ALPHASMS_LOGIN'], true, 20);
        $inputs .= wf_TextInput('editalphasmspassword', __('User password for access API'), $this->settings['ALPHASMS_PASSWORD'], true, 20);
        $inputs .= wf_TextInput('editalphasmsapikey', __('User API key for') . ' ' . __('AlphaSMS'), $this->settings['ALPHASMS_APIKEY'], true, 20);
        $inputs .= wf_TextInput('editalphasmssign', __('AlphaSMS') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['ALPHASMS_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'alphasms') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use') . ' ' . __('AlphaSMS') . ' ' . __('as default SMS service'), 'alphasms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Loads AlphaSMS service config
     *
     * @return void
     */
    public function loadAlphasmsConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_ALPHASMS_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'https://alphasms.ua/api/http.php';
            zb_StorageSet('SENDDOG_ALPHASMS_GATEWAY', $smsgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_ALPHASMS_LOGIN');
        if (empty($smslogin)) {
            $smslogin = 'yourlogin';
            zb_StorageSet('SENDDOG_ALPHASMS_LOGIN', $smslogin);
        }

        $smspassword = zb_StorageGet('SENDDOG_ALPHASMS_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = 'yourpassword';
            zb_StorageSet('SENDDOG_ALPHASMS_PASSWORD', $smspassword);
        }
        $smssign = zb_StorageGet('SENDDOG_ALPHASMS_SIGN');
        if (empty($smssign)) {
            $smssign = 'test';
            zb_StorageSet('SENDDOG_ALPHASMS_SIGN', $smssign);
        }

        $smsapikey = zb_StorageGet('SENDDOG_ALPHASMS_APIKEY');
        if (empty($smsapikey)) {
            $smsapikey = 'XXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZXXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZ';
            zb_StorageSet('SENDDOG_ALPHASMS_APIKEY', $smsapikey);
        }

        $this->settings['ALPHASMS_GATEWAY'] = $smsgateway;
        $this->settings['ALPHASMS_LOGIN'] = $smslogin;
        $this->settings['ALPHASMS_PASSWORD'] = $smspassword;
        $this->settings['ALPHASMS_SIGN'] = $smssign;
        $this->settings['ALPHASMS_APIKEY'] = $smsapikey;
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {

//AlphaSMS configuration
        if ($_POST['editalphasmsgateway'] != $this->settings['ALPHASMS_GATEWAY']) {
            zb_StorageSet('SENDDOG_ALPHASMS_GATEWAY', $_POST['editalphasmsgateway']);
            log_register('SENDDOG CONFIG SET ALPHASMSGATEWAY `' . $_POST['editalphasmsgateway'] . '`');
        }
        if ($_POST['editalphasmslogin'] != $this->settings['ALPHASMS_LOGIN']) {
            zb_StorageSet('SENDDOG_ALPHASMS_LOGIN', $_POST['editalphasmslogin']);
            log_register('SENDDOG CONFIG SET ALPHASMSLOGIN `' . $_POST['editalphasmslogin'] . '`');
        }
        if ($_POST['editalphasmspassword'] != $this->settings['ALPHASMS_PASSWORD']) {
            zb_StorageSet('SENDDOG_ALPHASMS_PASSWORD', $_POST['editalphasmspassword']);
            log_register('SENDDOG CONFIG SET ALPHASMSPASSWORD `' . $_POST['editalphasmspassword'] . '`');
        }
        if ($_POST['editalphasmssign'] != $this->settings['ALPHASMS_SIGN']) {
            zb_StorageSet('SENDDOG_ALPHASMS_SIGN', $_POST['editalphasmssign']);
            log_register('SENDDOG CONFIG SET ALPHASMSSIGN `' . $_POST['editalphasmssign'] . '`');
        }
        if ($_POST['editalphasmsapikey'] != $this->settings['ALPHASMS_APIKEY']) {
            zb_StorageSet('SENDDOG_ALPHASMS_APIKEY', $_POST['editalphasmsapikey']);
            log_register('SENDDOG CONFIG SET ALPHASMSAPIKEY `' . $_POST['editalphasmsapikey'] . '`');
        }
    }

    /**
     * Sends all sms storage via AlphaSMS service
     *
     * @return void
     */
    public function alphaPushMessages() {
        $apikey = $this->settings['ALPHASMS_APIKEY'];
        $sender = $this->settings['ALPHASMS_SIGN'];

        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $sms) {
                $formattedPhone = $this->cutInternationalsFromPhoneNum($sms['number']);
                $params = array('from' => urlencode($sender),
                    'to' => urlencode($formattedPhone),
                    'message' => $sms['message'],
                    'login' => $this->settings['ALPHASMS_LOGIN'],
                    'password' => $this->settings['ALPHASMS_PASSWORD'],
                    'key' => urlencode($apikey),
                    'command' => 'send');
                $params_url = '';
                foreach ($params as $key => $value) {
                    $params_url .= '&' . $key . '=' . $this->base64_url_encode($value);
                }

                //cURL HTTPS POST
                $ch = curl_init($this->settings['ALPHASMS_GATEWAY']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_POST, count($params));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params_url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                $response = @curl_exec($ch);
                if (PHP_VERSION_ID < 80000) {
                    curl_close($ch); // Deprecated in PHP 8.5
                }

                //remove old sent message
                $this->smsQueue->deleteSms($sms['filename']);
            }
        }
    }

    /**
     * Renders current AlphaSMS service user balance
     *
     * @return string
     */
    public function renderAlpasmsBalance() {
        $result = '';
        $params = array();
        $params['login'] = $this->settings['ALPHASMS_LOGIN'];
        $params['password'] = $this->settings['ALPHASMS_PASSWORD'];
        $params['key'] = $this->settings['ALPHASMS_APIKEY'];
        $params['command'] = 'balance';
        $params_url = '';
        foreach ($params as $key => $value) {
            $params_url .= '&' . $key . '=' . $this->base64_url_encode($value);
        }

        //cURL HTTPS POST
        $curl = curl_init($this->settings['ALPHASMS_GATEWAY']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_POST, count($params));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params_url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($curl);
        if (PHP_VERSION_ID < 80000) {
            curl_close($curl); // Deprecated in PHP 8.5
        }

        $response = @unserialize($this->base64_url_decode($response));

        $result .= wf_BackLink(self::URL_ME, '', true);
        $result .= $this->messages->getStyledMessage(__('Current account balance') . ': ' . @$response['balance'] . ' UAN', 'info');
        return ($result);
    }

    /**
     * Returns bas64 encoded URL
     * 
     * @param string $input
     * 
     * @return string
     */
    protected function base64_url_encode($input) {
        return strtr(base64_encode($input), '+/=', '-_,');
    }

    /**
     * Returns bas64 decoded URL
     * 
     * @param string $input
     * 
     * @return string
     */
    protected function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_,', '+/='));
    }

}
