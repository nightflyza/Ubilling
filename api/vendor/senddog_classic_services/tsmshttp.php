<?php

/**
 * TurboSMS HTTP API implementation
 * https://turbosms.ua/api.html
 */
class tsmshttp extends SendDogProto {

    /**
     * Sucess sending codes list
     *
     * @var array
     */
    protected $successCodes = array(
        800 => 'SUCCESS_MESSAGE_ACCEPTED',
        801 => 'SUCCESS_MESSAGE_SENT',
        802 => 'SUCCESS_MESSAGE_PARTIAL_ACCEPTED',
        803 => 'SUCCESS_MESSAGE_PARTIAL_SENT'
    );

    /**
     * Defines default log path
     */
    const LOG_PATH = 'exports/senddog_tsmshttp.log';

    /**
     * Some predefined routes here
     */
    const ROUTE_BALANCE = '/user/balance.json';
    const ROUTE_PUSH = '/message/send.json';

    /**
     * Sends all messages from queue
     *
     * @return void
     */
    public function pushMessages() {
        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            $sign = $this->safeEscapeString($this->settings['TSMSHTTP_SIGN']);
            //basic API callback URL
            $apiCallback = $this->settings['TSMSHTTP_GATEWAY'] . self::ROUTE_PUSH;
            $apiKey = $this->settings['TSMSHTTP_APIKEY'];
            //creating service API handler
            $turboSmsApi = new OmaeUrl($apiCallback);
            //setting request wait timeout the same as inter-message
            if ($this->settings['TSMSHTTP_TIMEOUT']) {
                $turboSmsApi->setTimeout($this->settings['TSMSHTTP_TIMEOUT']);
            }

            //processing messages queue
            foreach ($allSmsQueue as $eachSms) {
                if (!empty($eachSms['number']) AND ! empty($eachSms['message'])) {
                    $recipients = array($eachSms['number']);


                    $turboSmsApi->dataGet('token', $apiKey);
                    $turboSmsApi->dataGet('recipients[0]', $eachSms['number']);
                    $turboSmsApi->dataGet('sms[sender]', $sign);
                    $turboSmsApi->dataGet('sms[text]', urlencode($eachSms['message']));

                    $sendingResult = $turboSmsApi->response();

                    if ($turboSmsApi->error()) {
                        //log error to log
                        $this->putLog($turboSmsApi->error());
                    }

                    //decode reply
                    if ($sendingResult) {
                        @$sendingResult = json_decode($sendingResult, true);
                        if (!empty($sendingResult)) {
                            if (isset($sendingResult['response_code'])) {
                                $responseCode = $sendingResult['response_code'];
                                if (isset($this->successCodes[$responseCode])) {
                                    //Message sent. Now we can delete it from queue.
                                    $this->smsQueue->deleteSms($eachSms['filename']);
                                } else {
                                    $this->putLog('MESSAGE SENDING FAILED: ' . print_r($sendingResult, true));
                                }
                            } else {
                                $this->putLog('SOMETHING WENT WRONG: ' . print_r($sendingResult, true));
                            }
                        } else {
                            $this->putLog('BROKEN SENDING REPLY RECEIVED');
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
     * Renders account balance
     * 
     * @return string
     */
    public function showMiscInfo() {
        $result = '';

        $apiCallback = $this->settings['TSMSHTTP_GATEWAY'] . self::ROUTE_BALANCE;
        $apiKey = $this->settings['TSMSHTTP_APIKEY'];
        $turboSmsApi = new OmaeUrl($apiCallback);
        $turboSmsApi->dataGet('token', $apiKey);
        $balanceRaw = $turboSmsApi->response();

        if (!empty($balanceRaw)) {
            @$balanceRaw = json_decode($balanceRaw, true);
            if (!empty($balanceRaw)) {
                if (isset($balanceRaw['response_result'])) {
                    if (isset($balanceRaw['response_result']['balance'])) {
                        $balance = $balanceRaw['response_result']['balance'];
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
                $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
            }
        } else {
            $httpError = $turboSmsApi->error();
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Empty reply received'), 'error');
            $result .= $this->messages->getStyledMessage(implode(' ', $httpError), 'error');
        }

        $result .= wf_delimiter();
        $result .= wf_BackLink('?module=senddog');
        return($result);
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

    /**
     * Loads config from database
     * 
     * @return void
     */
    public function loadConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_TSMSHTTP_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'https://api.turbosms.ua/';
            zb_StorageSet('SENDDOG_TSMSHTTP_GATEWAY', $smsgateway);
        }

        $apikey = zb_StorageGet('SENDDOG_TSMSHTTP_APIKEY');
        if (empty($apikey)) {
            $apikey = 'yourapikey';
            zb_StorageSet('SENDDOG_TSMSHTTP_APIKEY', $apikey);
        }
        $smssign = zb_StorageGet('SENDDOG_TSMSHTTP_SIGN');
        if (empty($smssign)) {
            $smssign = 'Alphaname';
            zb_StorageSet('SENDDOG_TSMSHTTP_SIGN', $smssign);
        }

        $smstimeout = zb_StorageGet('SENDDOG_TSMSHTTP_TIMEOUT');
        if ($smstimeout == '') {
            $smstimeout = 0;
            zb_StorageSet('SENDDOG_TSMSHTTP_TIMEOUT', $smstimeout);
        }


        $this->settings['TSMSHTTP_GATEWAY'] = $smsgateway;
        $this->settings['TSMSHTTP_APIKEY'] = $apikey;
        $this->settings['TSMSHTTP_SIGN'] = $smssign;
        $this->settings['TSMSHTTP_TIMEOUT'] = $smstimeout;
    }

    /**
     * Return set of inputs, required for service configuration
     * 
     * @return string
     */
    public function renderConfigInputs() {
        $miscInfo = wf_Link(self::URL_ME . '&showmisc=tsmshttp', wf_img_sized('skins/icon_dollar.gif', __('Balance'), 10), true);
        $inputs = wf_tag('h2') . __('TurboSMS HTTP') . ' ' . $miscInfo . wf_tag('h2', true);
        $inputs .= wf_HiddenInput('editconfig', 'true');
        $inputs .= wf_TextInput('edittsmshttpgateway', __('API address') . ' ' . __('TurboSMS HTTP'), $this->settings['TSMSHTTP_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('edittsmshttpapikey', __('API key') . ' ' . __('TurboSMS HTTP'), $this->settings['TSMSHTTP_APIKEY'], true, 35);
        $inputs .= wf_TextInput('edittsmshttpsign', __('TurboSMS HTTP') . ' ' . __('Sign'), $this->settings['TSMSHTTP_SIGN'], true, 20);
        $inputs .= wf_TextInput('edittsmshttptimeout', __('TurboSMS HTTP') . ' ' . __('Timeout') . ' (' . __('sec.') . ')', $this->settings['TSMSHTTP_TIMEOUT'], true, 2, 'digits');
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'tsmshttp') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use') . ' ' . __('TurboSMS HTTP') . ' ' . __('as default SMS service'), 'tsmshttp', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Saves service settings to database
     * 
     * @return void
     */
    public function saveSettings() {
        if (ubRouting::post('edittsmshttpgateway') != $this->settings['TSMSHTTP_GATEWAY']) {
            zb_StorageSet('SENDDOG_TSMSHTTP_GATEWAY', ubRouting::post('edittsmshttpgateway'));
            log_register('SENDDOG CONFIG SET TSMSHTTPGATEWAY `' . ubRouting::post('edittsmshttpgateway') . '`');
        }

        if (ubRouting::post('edittsmshttpapikey') != $this->settings['TSMSHTTP_APIKEY']) {
            zb_StorageSet('SENDDOG_TSMSHTTP_APIKEY', ubRouting::post('edittsmshttpapikey'));
            log_register('SENDDOG CONFIG SET TSMSHTTPAPIKEY `' . ubRouting::post('edittsmshttpapikey') . '`');
        }
        if (ubRouting::post('edittsmshttpsign') != $this->settings['TSMSHTTP_SIGN']) {
            zb_StorageSet('SENDDOG_TSMSHTTP_SIGN', ubRouting::post('edittsmshttpsign'));
            log_register('SENDDOG CONFIG SET TSMSHTTPSIGN `' . ubRouting::post('edittsmshttpsign') . '`');
        }

        if (ubRouting::post('edittsmshttptimeout') != $this->settings['TSMSHTTP_TIMEOUT']) {
            zb_StorageSet('SENDDOG_TSMSHTTP_TIMEOUT', ubRouting::post('edittsmshttptimeout'));
            log_register('SENDDOG CONFIG SET TSMSHTTPTIMEOUT `' . ubRouting::post('edittsmshttptimeout') . '`');
        }
    }

}
