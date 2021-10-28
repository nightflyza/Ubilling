<?php

/**
 * bulksms.md SMS service implementation
 * API documentation: http://store.nightfly.biz/bulksmsdocumentatiedeintegrare_2021.docx
 */
class bulksms extends SendDogProto {

    /**
     * Option name that enabled record of service debug log
     */
    const DEBUG_OPTION = 'SENDDOG_BULKSMS_DEBUG';

    /**
     * Default debug log path
     */
    const DEBUGLOG_PATH = 'exports/bulksmsdebug.log';

    /**
     * Sends all messages from queue using bulksms.md service
     *
     * @return void
     */
    public function bulkSmsPushMessages() {
        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            //basic API callback URL
            $apiCallback = $this->settings['BULKSMS_GATEWAY'] . '/UnifunBulkSMSAPI.asmx/SendSMSSimple';

            //creating service API handler
            $bulkSmsApi = new OmaeUrl($apiCallback);
            //setting request wait timeout the same as inter-message
            if ($this->settings['BULKSMS_TIMEOUT']) {
                $bulkSmsApi->setTimeout($this->settings['BULKSMS_TIMEOUT']);
            }

            //processing messages queue
            foreach ($allSmsQueue as $eachSms) {
                if (!empty($eachSms['number']) AND ! empty($eachSms['message'])) {
                    //appending credentials and SMS content data
                    $sendToNumber = $eachSms['number']; //may be some postprocessing will be required here
                    $sendToNumber = str_replace('+', '', $sendToNumber); // service accepts phones only without + symbol
                    $messageTextEncoded = urlencode($eachSms['message']); //just urlencoded message

                    $bulkSmsApi->dataGet('username', $this->settings['BULKSMS_LOGIN']);
                    $bulkSmsApi->dataGet('password', $this->settings['BULKSMS_PASSWORD']);
                    $bulkSmsApi->dataGet('from', $this->settings['BULKSMS_SIGN']);
                    $bulkSmsApi->dataGet('to', $sendToNumber);
                    $bulkSmsApi->dataGet('text', $messageTextEncoded);

                    $sendResult = $bulkSmsApi->response(); //push request
                    //debug log is here and optional
                    if ($this->ubConfig->getAlterParam(self::DEBUG_OPTION)) {
                        file_put_contents(self::DEBUGLOG_PATH, curdatetime() . PHP_EOL, FILE_APPEND);
                        file_put_contents(self::DEBUGLOG_PATH, '===============' . PHP_EOL, FILE_APPEND);
                        file_put_contents(self::DEBUGLOG_PATH, $sendResult . PHP_EOL, FILE_APPEND);
                        file_put_contents(self::DEBUGLOG_PATH, 'HTTP Code: ' . print_r($bulkSmsApi->httpCode(), true) . PHP_EOL, FILE_APPEND);
                        file_put_contents(self::DEBUGLOG_PATH, 'Errors: ' . print_r($bulkSmsApi->error(), true) . PHP_EOL, FILE_APPEND);
                        file_put_contents(self::DEBUGLOG_PATH, 'Request info: ' . print_r($bulkSmsApi->lastRequestInfo(), true) . PHP_EOL, FILE_APPEND);
                        file_put_contents(self::DEBUGLOG_PATH, '===============' . PHP_EOL, FILE_APPEND);
                    }
                    $bulkSmsApi->dataGet(); //flush get data for next request
                    //remove already sent message
                    $this->smsQueue->deleteSms($eachSms['filename']);
                    //optional timeout
                    if ($this->settings['BULKSMS_TIMEOUT']) {
                        sleep($this->settings['BULKSMS_TIMEOUT']);
                    }
                }
            }
        }
    }

    /**
     * Return set of inputs, required for BulkSMS service configuration
     * 
     * @return string
     */
    public function renderBulksmsConfigInputs() {
        $inputs = wf_tag('h2') . __('BulkSMS.md') . wf_tag('h2', true);
        $inputs .= wf_HiddenInput('editconfig', 'true');
        $inputs .= wf_TextInput('editbulksmsgateway', __('API address') . ' ' . __('BulkSMS'), $this->settings['BULKSMS_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editbulksmslogin', __('User login to access API') . ' ' . ('BulkSMS'), $this->settings['BULKSMS_LOGIN'], true, 20);
        $inputs .= wf_TextInput('editbulksmspassword', __('User password for access API') . ' ' . __('BulkSMS'), $this->settings['BULKSMS_PASSWORD'], true, 20);
        $inputs .= wf_TextInput('editbulksmssign', __('BulkSMS') . ' ' . __('Sign'), $this->settings['BULKSMS_SIGN'], true, 20);
        $inputs .= wf_TextInput('editbulksmstimeout', __('BulkSMS') . ' ' . __('Timeout') . ' (' . __('sec.') . ')', $this->settings['BULKSMS_TIMEOUT'], true, 2, 'digits');
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'bulksms') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use') . ' ' . __('BulkSMS') . ' ' . __('as default SMS service'), 'bulksms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Loads BulkSMS config
     * 
     * @return void
     */
    public function loadBulksmsConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_BULKSMS_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'https://api.bulksms.md:4432';
            zb_StorageSet('SENDDOG_BULKSMS_GATEWAY', $smsgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_BULKSMS_LOGIN');
        if (empty($smslogin)) {
            $smslogin = 'yourlogin';
            zb_StorageSet('SENDDOG_BULKSMS_LOGIN', $smslogin);
        }

        $smspassword = zb_StorageGet('SENDDOG_BULKSMS_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = 'yourpassword';
            zb_StorageSet('SENDDOG_BULKSMS_PASSWORD', $smspassword);
        }
        $smssign = zb_StorageGet('SENDDOG_BULKSMS_SIGN');
        if (empty($smssign)) {
            $smssign = 'Alphaname';
            zb_StorageSet('SENDDOG_BULKSMS_SIGN', $smssign);
        }

        $smstimeout = zb_StorageGet('SENDDOG_BULKSMS_TIMEOUT');
        if ($smstimeout == '') {
            $smstimeout = 7;
            zb_StorageSet('SENDDOG_BULKSMS_TIMEOUT', $smstimeout);
        }


        $this->settings['BULKSMS_GATEWAY'] = $smsgateway;
        $this->settings['BULKSMS_LOGIN'] = $smslogin;
        $this->settings['BULKSMS_PASSWORD'] = $smspassword;
        $this->settings['BULKSMS_SIGN'] = $smssign;
        $this->settings['BULKSMS_TIMEOUT'] = $smstimeout;
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {
        if (ubRouting::post('editbulksmsgateway') != $this->settings['BULKSMS_GATEWAY']) {
            zb_StorageSet('SENDDOG_BULKSMS_GATEWAY', ubRouting::post('editbulksmsgateway'));
            log_register('SENDDOG CONFIG SET BULKSMSGATEWAY `' . ubRouting::post('editbulksmsgateway') . '`');
        }
        if (ubRouting::post('editbulksmslogin') != $this->settings['BULKSMS_LOGIN']) {
            zb_StorageSet('SENDDOG_BULKSMS_LOGIN', ubRouting::post('editbulksmslogin'));
            log_register('SENDDOG CONFIG SET BULKSMSLOGIN `' . ubRouting::post('editbulksmslogin') . '`');
        }
        if (ubRouting::post('editbulksmspassword') != $this->settings['BULKSMS_PASSWORD']) {
            zb_StorageSet('SENDDOG_BULKSMS_PASSWORD', ubRouting::post('editbulksmspassword'));
            log_register('SENDDOG CONFIG SET BULKSMSPASSWORD `' . ubRouting::post('editbulksmspassword') . '`');
        }
        if (ubRouting::post('editbulksmssign') != $this->settings['BULKSMS_SIGN']) {
            zb_StorageSet('SENDDOG_BULKSMS_SIGN', ubRouting::post('editbulksmssign'));
            log_register('SENDDOG CONFIG SET BULKSMSSIGN `' . ubRouting::post('editbulksmssign') . '`');
        }

        if (ubRouting::post('editbulksmstimeout') != $this->settings['BULKSMS_TIMEOUT']) {
            zb_StorageSet('SENDDOG_BULKSMS_TIMEOUT', ubRouting::post('editbulksmstimeout'));
            log_register('SENDDOG CONFIG SET BULKSMSTIMEOUT `' . ubRouting::post('editbulksmstimeout') . '`');
        }
    }

}
