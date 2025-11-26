<?php

class smspilot extends SendDogProto {

    /**
     * Returns set of inputs, required for SMSPILOT configuration
     *
     * @return string
     */
    public function renderSmsPilotConfigInputs() {
        $inputs = wf_tag('h2') . __('SMSPILOT') . ' ' . wf_Link(self::URL_ME . '&showmisc=smspilot', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs .= wf_TextInput('editsmspilotapikey', __('User API key for access SMSPILOT API'), $this->settings['SMSPILOT_APIKEY'], true, 20);
        $inputs .= wf_TextInput('editsmspilotsign', __('SMSPILOT') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['SMSPILOT_SIGN'], true, 20);
        $smsServiceFlag = $this->settings['SMS_SERVICE'] === 'smspilot';
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use SMSPILOT as default SMS service'), 'smspilot', true, $smsServiceFlag);
        return $inputs;
    }

    /**
     * Loads SMSPILOT.RU service config
     *
     * @return void
     */
    public function loadSmsPilotConfig() {
        $smsapikey = zb_StorageGet('SENDDOG_SMSPILOT_APIKEY');
        if (empty($smsapikey)) {
            $smsapikey = 'XXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZXXXXXXXXXXXXYYYYYYYYYYYYZZZZZZZZ';
            zb_StorageSet('SENDDOG_SMSPILOT_APIKEY', $smsapikey);
        }
        $smssign = zb_StorageGet('SENDDOG_SMSPILOT_SIGN');
        $this->settings['SMSPILOT_APIKEY'] = $smsapikey;
        $this->settings['SMSPILOT_SIGN'] = $smssign;
    }

    /**
     * Sends all sms storage via SMSPILOT.RU service
     *
     * @return void
     */
    public function smspilotPushMessages() {

        $apikey = $this->settings['SMSPILOT_APIKEY'];
        $sender = $this->settings['SMSPILOT_SIGN'];

        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $sms) {

                $url = 'http://smspilot.ru/api.php'
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
                if (PHP_VERSION_ID < 80000) {
                    curl_close($ch); // Deprecated in PHP 8.5
                }

                $j = json_decode($json);
                if ($j && isset($j->error)) {
                    trigger_error($j->description_ru, E_USER_WARNING);
                }
//remove old sent message
                $this->smsQueue->deleteSms($sms['filename']);
            }
        }
    }

    /**
     * Renders SMSPILOT user balance
     *
     * @return string
     */
    public function renderSMSPILOTBalance() {

        $balance = file_get_contents('http://smspilot.ru/api.php'
                . '?balance=rur'
                . '&apikey=' . $this->settings['SMSPILOT_APIKEY']
        );

        $result = wf_BackLink(self::URL_ME, '', true);
        $result .= $this->messages->getStyledMessage(__('Current account balance') . ': ' . $balance . ' RUR', 'info');
        return $result;
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {
        //SMSPILOT configuration
        if ($_POST['editsmspilotapikey'] != $this->settings['SMSPILOT_APIKEY']) {
            zb_StorageSet('SENDDOG_SMSPILOT_APIKEY', $_POST['editsmspilotapikey']);
            log_register('SENDDOG CONFIG SET SMSPILOT_APIKEY `' . $_POST['editsmspilotapikey'] . '`');
        }
        if ($_POST['editsmspilotsign'] != $this->settings['SMSPILOT_SIGN']) {
            zb_StorageSet('SENDDOG_SMSPILOT_SIGN', $_POST['editsmspilotsign']);
            log_register('SENDDOG CONFIG SET SMSPILOT_SIGN `' . $_POST['editsmspilotsign'] . '`');
        }
    }

}
