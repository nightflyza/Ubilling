<?php

/**
 * Notificore API implementation
 * https://github.com/Notificore/notificore-php
 */
class notificoresms extends SendDogProto {

    /**
     * Contains default external lib path
     */
    const VENDOR_LIB = 'api/vendor/notificore/Notificore.php';

    /**
     * Defines default log path
     */
    const LOG_PATH = 'exports/senddog_notificoresms.log';

    /**
     * Renders account balance
     * 
     * @return string
     */
    public function showMiscInfo() {
        $result = '';
        require_once (self::VENDOR_LIB);
        $api = new Notificore($this->settings['NOTIFICORE_APIKEY']);
        $client = $api->getSmsClient();
        $balanceRaw = $client->getBalance();
        if (!empty($balanceRaw)) {
            if ($balanceRaw['error'] == 0) {
                if (isset($balanceRaw['amount'])) {
                    $balance = $balanceRaw['amount'] . ' ' . $balanceRaw['currency'] . ', ' . __('Threshold') . ': ' . $balanceRaw['limit'];
                    $type = 'success';
                    if ($balance < 3000) {
                        $type = 'warning';
                    }

                    if ($balance < 0) {
                        $type = 'error';
                    }
                    $result .= $this->messages->getStyledMessage(__('Balance') . ': ' . $balance, $type);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . implode(' ', $balanceRaw), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . implode(' ', $balanceRaw), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Empty reply received'), 'error');
        }
        $result .= wf_delimiter();
        $result .= wf_BackLink(self::URL_ME);

        return($result);
    }

    /**
     * Sends all messages from queue
     *
     * @return void
     */
    public function pushMessages() {
        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            require_once (self::VENDOR_LIB);
            $sign = $this->safeEscapeString($this->settings['NOTIFICORE_SIGN']);
            $api = new Notificore($this->settings['NOTIFICORE_APIKEY'], $sign);
            $smsClient = $api->getSmsClient();

            //processing messages queue
            foreach ($allSmsQueue as $eachSms) {
                if (!empty($eachSms['number']) AND ! empty($eachSms['message'])) {
                    $recipient = $eachSms['number'];
                    $text = $eachSms['message'];
                    $reference = 'ubsms' . time() . str_replace('.', '', microtime(true));
                    $sendingResult = $smsClient->sendSms($recipient, $text, $reference);
                    if (!empty($sendingResult)) {
                        if (isset($sendingResult['result']['error'])) {
                            if (empty($sendingResult['result']['error'])) {
                                //Message sent. Now we can delete it from queue.
                                $this->smsQueue->deleteSms($eachSms['filename']);
                            } else {
                                $this->putLog('MESSAGE SENDING FAILED: ' . print_r($sendingResult, true));
                            }
                        } else {
                            $this->putLog($sendingResult);
                        }
                    } else {
                        //something went wrong
                        $this->putLog('EMPTY SENDING RESULT RECEIVED');
                    }
                }
            }
        }
    }

    /**
     * Loads config from database
     * 
     * @return void
     */
    public function loadConfig() {
        $apikey = zb_StorageGet('SENDDOG_NOTIFICORE_APIKEY');
        if (empty($apikey)) {
            $apikey = 'yourapikey';
            zb_StorageSet('SENDDOG_NOTIFICORE_APIKEY', $apikey);
        }
        $smssign = zb_StorageGet('SENDDOG_NOTIFICORE_SIGN');
        if (empty($smssign)) {
            $smssign = 'Alphaname';
            zb_StorageSet('SENDDOG_NOTIFICORE_SIGN', $smssign);
        }

        $this->settings['NOTIFICORE_APIKEY'] = $apikey;
        $this->settings['NOTIFICORE_SIGN'] = $smssign;
    }

    /**
     * Return set of inputs, required for service configuration
     * 
     * @return string
     */
    public function renderConfigInputs() {
        $miscInfo = wf_Link(self::URL_ME . '&showmisc=notificoresms', wf_img_sized('skins/icon_dollar.gif', __('Balance'), 10), true);
        $inputs = wf_tag('h2') . __('Notificore') . ' ' . $miscInfo . wf_tag('h2', true);
        $inputs .= wf_HiddenInput('editconfig', 'true');
        $inputs .= wf_TextInput('editnotificoreapikey', __('API key') . ' ' . __('Notificore'), $this->settings['NOTIFICORE_APIKEY'], true, 35);
        $inputs .= wf_TextInput('editnotificoresign', __('Notificore') . ' ' . __('Sign'), $this->settings['NOTIFICORE_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'notificoresms') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use') . ' ' . __('Notificore') . ' ' . __('as default SMS service'), 'notificoresms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {
        if (ubRouting::post('editnotificoreapikey') != $this->settings['NOTIFICORE_APIKEY']) {
            zb_StorageSet('SENDDOG_NOTIFICORE_APIKEY', ubRouting::post('editnotificoreapikey'));
            log_register('SENDDOG CONFIG SET NOTIFICOREAPIKEY `' . ubRouting::post('editnotificoreapikey') . '`');
        }
        if (ubRouting::post('editnotificoresign') != $this->settings['NOTIFICORE_SIGN']) {
            zb_StorageSet('SENDDOG_NOTIFICORE_SIGN', ubRouting::post('editnotificoresign'));
            log_register('SENDDOG CONFIG SET NOTIFICORESIGN `' . ubRouting::post('editnotificoresign') . '`');
        }
    }

    /**
     * Writes some to log
     * 
     * @param mixed $data
     * 
     * @return void
     */
    protected function putLog($data) {
        $time = curdatetime();
        if (is_array($data)) {
            $data = print_r($data, true);
        }
        $logData = $time . ' ' . $data . PHP_EOL;
        file_put_contents(self::LOG_PATH, $logData, FILE_APPEND);
    }

}
