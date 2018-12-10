<?php

class SendDog {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains senddog config
     *
     * @var array
     */
    protected $settings = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * System SMS queue object placeholder
     *
     * @var object
     */
    protected $smsQueue = '';

    /**
     * contains default interface module URL
     */
    const URL_ME = '?module=senddog';

    public function __construct() {
        $this->loadAltCfg();
        $this->initSmsQueue();
        $this->initMessages();
        $this->loadBaseConfig();
        $this->loadTelegramConfig();
        $this->loadTurbosmsConfig();
        $this->loadSmsflyConfig();
        $this->loadRedsmsConfig();
        $this->loadSmsPilotConfig();
        $this->loadSkyriverConfig();
        $this->loadLifecellConfig();
    }

    /**
     * Loads system alter config into protected property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAltCfg() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits system SMS queue object
     * 
     * @return void
     */
    protected function initSmsQueue() {
        $this->smsQueue = new UbillingSMS();
    }

    /**
     * Inits message helper object for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads basic send dog settings
     * 
     * @return void
     */
    protected function loadBaseConfig() {
        $defaultSMSservice = zb_StorageGet('SENDDOG_SMS_SERVICE');
        if (empty($defaultSMSservice)) {
            $defaultSMSservice = 'tsms';
            zb_StorageSet('SENDDOG_SMS_SERVICE', $defaultSMSservice);
        }
        $this->settings['SMS_SERVICE'] = $defaultSMSservice;
    }

    /**
     * Returns base module URL
     * 
     * @return string
     */
    public function getBaseUrl() {
        return (self::URL_ME);
    }

    /**
     * Dirty input data filtering 
     * 
     * @param $string - string to filter
     * 
     * @return string
     */
    protected function safeEscapeString($string) {
        @$result = preg_replace("#[~@\?\%\/\;=\*\>\<\"\']#Uis", '', $string);

        return ($result);
    }

    /**
     * Loads telegram config
     * 
     * @return void
     */
    protected function loadTelegramConfig() {
        $telegramBotToken = zb_StorageGet('SENDDOG_TELEGRAM_BOTTOKEN');
        if (empty($telegramBotToken)) {
            $telegramBotToken = 'input_token_here';
            zb_StorageSet('SENDDOG_TELEGRAM_BOTTOKEN', $telegramBotToken);
        }
        $this->settings['TELEGRAM_BOTTOKEN'] = $telegramBotToken;
    }

    /**
     * Loads TurboSMS config
     * 
     * @return void
     */
    protected function loadTurbosmsConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_TSMS_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = $this->altCfg['TSMS_GATEWAY'];
            zb_StorageSet('SENDDOG_TSMS_GATEWAY', $smsgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_TSMS_LOGIN');
        if (empty($smslogin)) {
            $smslogin = $this->altCfg['TSMS_LOGIN'];
            zb_StorageSet('SENDDOG_TSMS_LOGIN', $smslogin);
        }

        $smspassword = zb_StorageGet('SENDDOG_TSMS_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = $this->altCfg['TSMS_PASSWORD'];
            zb_StorageSet('SENDDOG_TSMS_PASSWORD', $smspassword);
        }
        $smssign = zb_StorageGet('SENDDOG_TSMS_SIGN');
        if (empty($smssign)) {
            $smssign = 'Ubilling';
            zb_StorageSet('SENDDOG_TSMS_SIGN', $smssign);
        }


        $this->settings['TSMS_GATEWAY'] = $smsgateway;
        $this->settings['TSMS_LOGIN'] = $smslogin;
        $this->settings['TSMS_PASSWORD'] = $smspassword;
        $this->settings['TSMS_SIGN'] = $smssign;
    }

    /**
     * Loads SMS-Fly service config
     * 
     * @return void
     */
    protected function loadSmsflyConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_SMSFLY_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'http://sms-fly.com/api/api.php';
            zb_StorageSet('SENDDOG_SMSFLY_GATEWAY', $smsgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_SMSFLY_LOGIN');
        if (empty($smslogin)) {
            $smslogin = '380501234567';
            zb_StorageSet('SENDDOG_SMSFLY_LOGIN', $smslogin);
        }

        $smspassword = zb_StorageGet('SENDDOG_SMSFLY_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = 'MySecretPassword';
            zb_StorageSet('SENDDOG_SMSFLY_PASSWORD', $smspassword);
        }
        $smssign = zb_StorageGet('SENDDOG_SMSFLY_SIGN');
        if (empty($smssign)) {
            $smssign = 'InfoCentr';
            zb_StorageSet('SENDDOG_SMSFLY_SIGN', $smssign);
        }


        $this->settings['SMSFLY_GATEWAY'] = $smsgateway;
        $this->settings['SMSFLY_LOGIN'] = $smslogin;
        $this->settings['SMSFLY_PASSWORD'] = $smspassword;
        $this->settings['SMSFLY_SIGN'] = $smssign;
    }

    /**
     * Loads Lifecell service config
     * 
     * @return void
     */
    protected function loadLifecellConfig() {
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
     * Loads RED-sms service config
     * 
     * @return void
     */
    protected function loadRedsmsConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_REDSMS_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'https://lk.redsms.ru/get/send.php';
            zb_StorageSet('SENDDOG_REDSMS_GATEWAY', $smsgateway);
        }

        $smsbilgateway = zb_StorageGet('SENDDOG_REDSMS_BILGATEWAY');
        if (empty($smsbilgateway)) {
            $smsbilgateway = 'https://lk.redsms.ru/get/balance.php';
            zb_StorageSet('SENDDOG_REDSMS_BILGATEWAY', $smsbilgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_REDSMS_LOGIN');
        if (empty($smslogin)) {
            $smslogin = 'Login';
            zb_StorageSet('SENDDOG_REDSMS_LOGIN', $smslogin);
        }

        $smsapikey = zb_StorageGet('SENDDOG_REDSMS_APIKEY');
        if (empty($smsapikey)) {
            $smsapikey = 'MyAPIKey';
            zb_StorageSet('SENDDOG_REDSMS_APIKEY', $smsapikey);
        }
        $smssign = zb_StorageGet('SENDDOG_REDSMS_SIGN');
        if (empty($smssign)) {
            $smssign = 'InfoCentr';
            zb_StorageSet('SENDDOG_REDSMS_SIGN', $smssign);
        }


        $this->settings['REDSMS_GATEWAY'] = $smsgateway;
        $this->settings['REDSMS_BILGATEWAY'] = $smsbilgateway;
        $this->settings['REDSMS_LOGIN'] = $smslogin;
        $this->settings['REDSMS_APIKEY'] = $smsapikey;
        $this->settings['REDSMS_SIGN'] = $smssign;
    }

    /**
     * Loads SMSPILOT.RU service config
     *
     * @return void
     */
    protected function loadSmsPilotConfig() {
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
     * Loads Skyriver service config
     *
     * @return void
     */
    protected function loadSkyriverConfig() {
        $smsgateway = zb_StorageGet('SENDDOG_SKYSMS_GATEWAY');
        if (empty($smsgateway)) {
            $smsgateway = 'http://sms.skysms.net/api/bulk_sm';
            zb_StorageSet('SENDDOG_SKYSMS_GATEWAY', $smsgateway);
        }

        $smslogin = zb_StorageGet('SENDDOG_SKYSMS_LOGIN');
        if (empty($smslogin)) {
            $smslogin = 'InfoCentr';
            zb_StorageSet('SENDDOG_SKYSMS_LOGIN', $smslogin);
        }

        $smspassword = zb_StorageGet('SENDDOG_SKYSMS_PASSWORD');
        if (empty($smspassword)) {
            $smspassword = 'MySecretPassword';
            zb_StorageSet('SENDDOG_SKYSMS_PASSWORD', $smspassword);
        }

        $this->settings['SKYSMS_GATEWAY'] = $smsgateway;
        $this->settings['SKYSMS_LOGIN'] = $smslogin;
        $this->settings['SKYSMS_PASSWORD'] = $smspassword;
    }

    /**
     * Render TurboSMS server-side queue
     * 
     * @return string
     */
    public function renderTurboSMSQueue() {
        $result = '';
        $tsms_host = $this->settings['TSMS_GATEWAY'];
        $tsms_db = 'users';
        $tsms_login = $this->settings['TSMS_LOGIN'];
        $tsms_password = $this->settings['TSMS_PASSWORD'];
        $tsms_table = $this->settings['TSMS_LOGIN'];
        $smsArray = array();
        $total = 0;

        $TsmsDB = new DbConnect($tsms_host, $tsms_login, $tsms_password, $tsms_db, $error_reporting = true, $persistent = false);
        $TsmsDB->open() or die($TsmsDB->error());
        $TsmsDB->query('SET NAMES utf8;');

        if (wf_CheckPost(array('showdate'))) {
            $date = mysql_real_escape_string($_POST['showdate']);
        } else {
            $date = '';
        }

        if (!empty($date)) {
            $where = " WHERE `send_time` LIKE '" . $date . "%' ORDER BY `id` DESC;";
        } else {
            $where = '  ORDER BY `id` DESC LIMIT 50;';
        }

        $query = "SELECT * from `" . $tsms_table . "`" . $where;
        $TsmsDB->query($query);

        while ($row = $TsmsDB->fetchassoc()) {
            $smsArray[] = $row;
        }


//close old datalink
        $TsmsDB->close();

//rendering result
        $inputs = wf_DatePickerPreset('showdate', curdate());
        $inputs .= wf_Submit(__('Show'));
        $dateform = wf_Form("", 'POST', $inputs, 'glamour');


        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Msg ID'));
        $cells .= wf_TableCell(__('Mobile'));
        $cells .= wf_TableCell(__('Sign'));
        $cells .= wf_TableCell(__('Message'));
        $cells .= wf_TableCell(__('Balance'));
        $cells .= wf_TableCell(__('Cost'));
        $cells .= wf_TableCell(__('Send time'));
        $cells .= wf_TableCell(__('Sended'));
        $cells .= wf_TableCell(__('Status'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($smsArray)) {
            foreach ($smsArray as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['msg_id']);
                $cells .= wf_TableCell($each['number']);
                $cells .= wf_TableCell($each['sign']);
                $msg = wf_modal(__('Show'), __('SMS'), $each['message'], '', '300', '200');
                $cells .= wf_TableCell($msg);
                $cells .= wf_TableCell($each['balance']);
                $cells .= wf_TableCell($each['cost']);
                $cells .= wf_TableCell($each['send_time']);
                $cells .= wf_TableCell($each['sended']);
                $cells .= wf_TableCell($each['status']);
                $rows .= wf_TableRow($cells, 'row5');
                $total++;
            }
        }

        $result .= wf_BackLink(self::URL_ME, '', true);
        $result .= $dateform;
        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= __('Total') . ': ' . $total;
        return ($result);
    }

    /**
     * Renders current SMS-Fly service user balance
     * 
     * @return string
     */
    public function renderSmsflyBalance() {
        $result = '';

        $myXML = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $myXML .= "<request>";
        $myXML .= "<operation>GETBALANCE</operation>";
        $myXML .= "</request>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, $this->settings['SMSFLY_LOGIN'] . ':' . $this->settings['SMSFLY_PASSWORD']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->settings['SMSFLY_GATEWAY']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
        $response = curl_exec($ch);
        curl_close($ch);

        $result .= wf_BackLink(self::URL_ME, '', true);
        $result .= $this->messages->getStyledMessage(__('Current account balance') . ': ' . $response, 'info');
        return ($result);
    }

    /**
     * Renders current RED-Sms service user balance
     * 
     * @return string
     */
    public function renderRedsmsBalance() {
        $result = '';
        $timestamp = file_get_contents('https://lk.redsms.ru/get/timestamp.php');
        $api_key = $this->settings['REDSMS_APIKEY'];
        $login = $this->settings['REDSMS_LOGIN'];
        $return = 'xml';
        $params = array(
            'timestamp' => $timestamp,
            'login' => $login,
            'return' => $return
        );
        ksort($params);
        reset($params);
        $signature = md5(implode($params) . $api_key);
        $query = $this->settings['REDSMS_BILGATEWAY'] . "?login=" . $login . "&signature=" . $signature . "&timestamp=" . $timestamp . "&return=" . $return;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $query);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        $result .= wf_BackLink(self::URL_ME, '', true);
        $result .= $this->messages->getStyledMessage(__('Current account balance') . ': ' . $response . ' RUR', 'info');
        return ($result);
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
     * Renders current telegram bot contacts
     * 
     * @return string
     */
    public function renderTelegramContacts() {
        $result = '';
        $telegram = new UbillingTelegram();
        $telegram->setToken($this->settings['TELEGRAM_BOTTOKEN']);
        $rawContacts = $telegram->getBotContacts();
        $result .= wf_BackLink(self::URL_ME, '', true);

        if (!empty($rawContacts)) {
            $cells = wf_TableCell(__('Chat ID'));
            $cells .= wf_TableCell(__('Type'));
            $cells .= wf_TableCell(__('Name'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($rawContacts as $io => $each) {
                $cells = wf_TableCell($each['chatid']);
                $cells .= wf_TableCell($each['type']);
                $cells .= wf_TableCell($each['name']);
                $rows .= wf_TableRow($cells, 'row3');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }
        return ($result);
    }

    /**
     * Return set of inputs, required for TurboSMS service configuration
     * 
     * @return string
     */
    protected function renderTsmsConfigInputs() {
        $inputs = wf_tag('h2') . __('TurboSMS') . ' ' . wf_Link(self::URL_ME . '&showmisc=tsms', wf_img('skins/icon_sms_micro.gif', __('View SMS sending queue')), true) . wf_tag('h2', true);
        $inputs .= wf_HiddenInput('editconfig', 'true');
        $inputs .= wf_TextInput('edittsmsgateway', __('TurboSMS gateway address'), $this->settings['TSMS_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('edittsmslogin', __('User login to access TurboSMS gateway'), $this->settings['TSMS_LOGIN'], true, 20);
        $inputs .= wf_TextInput('edittsmspassword', __('User password for access TurboSMS gateway'), $this->settings['TSMS_PASSWORD'], true, 20);
        $inputs .= wf_TextInput('edittsmssign', __('TurboSMS') . ' ' . __('Sign'), $this->settings['TSMS_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'tsms') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use TurboSMS as default SMS service'), 'tsms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Returns set of inputs, required for SMS-Fly service configuration
     * 
     * @return string
     */
    protected function renderSmsflyConfigInputs() {
        $inputs = wf_tag('h2') . __('SMS-Fly') . ' ' . wf_Link(self::URL_ME . '&showmisc=smsflybalance', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs .= wf_TextInput('editsmsflygateway', __('SMS-Fly API address'), $this->settings['SMSFLY_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editsmsflylogin', __('User login to access SMS-Fly API'), $this->settings['SMSFLY_LOGIN'], true, 20);
        $inputs .= wf_TextInput('editsmsflypassword', __('User password for access SMS-Fly API'), $this->settings['SMSFLY_PASSWORD'], true, 20);
        $inputs .= wf_TextInput('editsmsflysign', __('SMS-Fly') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['SMSFLY_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'smsfly') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use SMS-Fly as default SMS service'), 'smsfly', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Returns set of inputs, required for Lifecell service configuration
     * 
     * @return string
     */
    protected function renderLifecellConfigInputs() {
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
     * Returns set of inputs, required for RED-Sms service configuration
     * 
     * @return string
     */
    protected function renderRedsmsConfigInputs() {
        $inputs = wf_tag('h2') . __('RED-Sms') . ' ' . wf_Link(self::URL_ME . '&showmisc=redsmsbalance', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs .= wf_TextInput('editredsmsgateway', __('RED-Sms API address'), $this->settings['REDSMS_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editredsmsbilgateway', __('RED-Sms Balance API address'), $this->settings['REDSMS_BILGATEWAY'], true, 30);
        $inputs .= wf_TextInput('editredsmslogin', __('User login to access RED-Sms API'), $this->settings['REDSMS_LOGIN'], true, 20);
        $inputs .= wf_TextInput('editredsmsapikey', __('User API key for access RED-Sms API'), $this->settings['REDSMS_APIKEY'], true, 20);
        $inputs .= wf_TextInput('editredsmssign', __('RED-Sms') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['REDSMS_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'redsms') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use RED-Sms as default SMS service'), 'redsms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Returns set of inputs, required for SMSPILOT configuration
     *
     * @return string
     */
    protected function renderSmsPilotConfigInputs() {
        $inputs = wf_tag('h2') . __('SMSPILOT') . ' ' . wf_Link(self::URL_ME . '&showmisc=smspilotbalance', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs .= wf_TextInput('editsmspilotapikey', __('User API key for access SMSPILOT API'), $this->settings['SMSPILOT_APIKEY'], true, 20);
        $inputs .= wf_TextInput('editsmspilotsign', __('SMSPILOT') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['SMSPILOT_SIGN'], true, 20);
        $smsServiceFlag = $this->settings['SMS_SERVICE'] === 'smspilot';
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use SMSPILOT as default SMS service'), 'smspilot', true, $smsServiceFlag);
        return $inputs;
    }

    /**
     * Returns set of inputs, required for Skyriver service configuration
     *
     * @return string
     */
    protected function renderSkyriverConfigInputs() {
        $inputs = wf_tag('h2') . 'Skyriver' . wf_tag('h2', true);
        $inputs .= wf_TextInput('editskysmsgateway', __('Skyriver API address'), $this->settings['SKYSMS_GATEWAY'], true, 30);
        $inputs .= wf_TextInput('editskysmslogin', __('User login to access Skyriver API (this is sign also)'), $this->settings['SKYSMS_LOGIN'], true, 20);
        $inputs .= wf_TextInput('editskysmspassword', __('User password for access Skyriver API'), $this->settings['SKYSMS_PASSWORD'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'skysms') ? true : false;
        $inputs .= wf_RadioInput('defaultsmsservice', __('Use Skyriver as default SMS service'), 'skysms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Returns set of inputs, required for SMS-Fly service configuration
     * 
     * @return string
     */
    protected function renderTelegramConfigInputs() {
        $inputs = wf_tag('h2') . __('Telegram') . ' ' . wf_Link(self::URL_ME . '&showmisc=telegramcontacts', wf_img_sized('skins/icon_search_small.gif', __('Telegram bot contacts'), '10', '10'), true) . wf_tag('h2', true);
        $inputs .= wf_TextInput('edittelegrambottoken', __('Telegram bot token'), $this->settings['TELEGRAM_BOTTOKEN'], true, 55);

        return ($inputs);
    }

    /**
     * Renders SendDog config interface
     * 
     * @return string
     */
    public function renderConfigForm() {
        $result = '';
        $inputs = $this->renderTsmsConfigInputs();
        $inputs .= $this->renderSmsflyConfigInputs();
        $inputs .= $this->renderRedsmsConfigInputs();
        $inputs .= $this->renderSmsPilotConfigInputs();
        $inputs .= $this->renderSkyriverConfigInputs();
        $inputs .= $this->renderLifecellConfigInputs();
        $inputs .= $this->renderTelegramConfigInputs();

        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');


        return ($result);
    }

    /**
     * Saves config in database
     * 
     * @return void
     */
    public function saveConfig() {
//TurboSMS configuration
        if ($_POST['edittsmsgateway'] != $this->settings['TSMS_GATEWAY']) {
            zb_StorageSet('SENDDOG_TSMS_GATEWAY', $_POST['edittsmsgateway']);
            log_register('SENDDOG CONFIG SET TSMSGATEWAY `' . $_POST['edittsmsgateway'] . '`');
        }
        if ($_POST['edittsmslogin'] != $this->settings['TSMS_LOGIN']) {
            zb_StorageSet('SENDDOG_TSMS_LOGIN', $_POST['edittsmslogin']);
            log_register('SENDDOG CONFIG SET TSMSLOGIN `' . $_POST['edittsmslogin'] . '`');
        }
        if ($_POST['edittsmspassword'] != $this->settings['TSMS_PASSWORD']) {
            zb_StorageSet('SENDDOG_TSMS_PASSWORD', $_POST['edittsmspassword']);
            log_register('SENDDOG CONFIG SET TSMSPASSWORD `' . $_POST['edittsmspassword'] . '`');
        }
        if ($_POST['edittsmssign'] != $this->settings['TSMS_SIGN']) {
            zb_StorageSet('SENDDOG_TSMS_SIGN', $_POST['edittsmssign']);
            log_register('SENDDOG CONFIG SET TSMSSIGN `' . $_POST['edittsmssign'] . '`');
        }
//SMS-Fly configuration
        if ($_POST['editsmsflygateway'] != $this->settings['SMSFLY_GATEWAY']) {
            zb_StorageSet('SENDDOG_SMSFLY_GATEWAY', $_POST['editsmsflygateway']);
            log_register('SENDDOG CONFIG SET SMSFLYGATEWAY `' . $_POST['editsmsflygateway'] . '`');
        }
        if ($_POST['editsmsflylogin'] != $this->settings['SMSFLY_LOGIN']) {
            zb_StorageSet('SENDDOG_SMSFLY_LOGIN', $_POST['editsmsflylogin']);
            log_register('SENDDOG CONFIG SET SMSFLYLOGIN `' . $_POST['editsmsflylogin'] . '`');
        }
        if ($_POST['editsmsflypassword'] != $this->settings['SMSFLY_PASSWORD']) {
            zb_StorageSet('SENDDOG_SMSFLY_PASSWORD', $_POST['editsmsflypassword']);
            log_register('SENDDOG CONFIG SET SMSFLYPASSWORD `' . $_POST['editsmsflypassword'] . '`');
        }
        if ($_POST['editsmsflysign'] != $this->settings['SMSFLY_SIGN']) {
            zb_StorageSet('SENDDOG_SMSFLY_SIGN', $_POST['editsmsflysign']);
            log_register('SENDDOG CONFIG SET SMSFLYSIGN `' . $_POST['editsmsflysign'] . '`');
        }

//RED-Sms configuration
        if ($_POST['editredsmsgateway'] != $this->settings['REDSMS_GATEWAY']) {
            zb_StorageSet('SENDDOG_REDSMS_GATEWAY', $_POST['editredsmsgateway']);
            log_register('SENDDOG CONFIG SET REDSMSGATEWAY `' . $_POST['editredsmsgateway'] . '`');
        }
        if ($_POST['editredsmsbilgateway'] != $this->settings['REDSMS_BILGATEWAY']) {
            zb_StorageSet('SENDDOG_REDSMS_BILGATEWAY', $_POST['editredsmsbilgateway']);
            log_register('SENDDOG CONFIG SET REDSMSBILGATEWAY `' . $_POST['editredsmsbilgateway'] . '`');
        }
        if ($_POST['editredsmslogin'] != $this->settings['REDSMS_LOGIN']) {
            zb_StorageSet('SENDDOG_REDSMS_LOGIN', $_POST['editredsmslogin']);
            log_register('SENDDOG CONFIG SET REDSMSLOGIN `' . $_POST['editredsmslogin'] . '`');
        }
        if ($_POST['editredsmsapikey'] != $this->settings['REDSMS_APIKEY']) {
            zb_StorageSet('SENDDOG_REDSMS_APIKEY', $_POST['editredsmsapikey']);
            log_register('SENDDOG CONFIG SET REDSMSAPIKEY `' . $_POST['editredsmsapikey'] . '`');
        }
        if ($_POST['editredsmssign'] != $this->settings['REDSMS_SIGN']) {
            zb_StorageSet('SENDDOG_REDSMS_SIGN', $_POST['editredsmssign']);
            log_register('SENDDOG CONFIG SET REDSMSSIGN `' . $_POST['editredsmssign'] . '`');
        }

//SMSPILOT configuration
        if ($_POST['editsmspilotapikey'] != $this->settings['SMSPILOT_APIKEY']) {
            zb_StorageSet('SENDDOG_SMSPILOT_APIKEY', $_POST['editsmspilotapikey']);
            log_register('SENDDOG CONFIG SET SMSPILOT_APIKEY `' . $_POST['editsmspilotapikey'] . '`');
        }
        if ($_POST['editsmspilotsign'] != $this->settings['SMSPILOT_SIGN']) {
            zb_StorageSet('SENDDOG_SMSPILOT_SIGN', $_POST['editsmspilotsign']);
            log_register('SENDDOG CONFIG SET SMSPILOT_SIGN `' . $_POST['editsmspilotsign'] . '`');
        }


//Skyriver configuration
        if ($_POST['editskysmsgateway'] != $this->settings['SKYSMS_GATEWAY']) {
            zb_StorageSet('SENDDOG_SKYSMS_GATEWAY', $_POST['editskysmsgateway']);
            log_register('SENDDOG CONFIG SET SKYSMSGATEWAY `' . $_POST['editskysmsgateway'] . '`');
        }
        if ($_POST['editskysmslogin'] != $this->settings['SKYSMS_LOGIN']) {
            zb_StorageSet('SENDDOG_SKYSMS_LOGIN', $_POST['editskysmslogin']);
            log_register('SENDDOG CONFIG SET SKYSMSLOGIN `' . $_POST['editskysmslogin'] . '`');
        }
        if ($_POST['editskysmspassword'] != $this->settings['SKYSMS_PASSWORD']) {
            zb_StorageSet('SENDDOG_SKYSMS_PASSWORD', $_POST['editskysmspassword']);
            log_register('SENDDOG CONFIG SET SKYSMSPASSWORD `' . $_POST['editskysmspassword'] . '`');
        }

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


//telegram bot token configuration
        if ($_POST['edittelegrambottoken'] != $this->settings['TELEGRAM_BOTTOKEN']) {
            zb_StorageSet('SENDDOG_TELEGRAM_BOTTOKEN', $_POST['edittelegrambottoken']);
            log_register('SENDDOG CONFIG SET TELEGRAMBOTTOKEN');
        }


//default sms service
        if ($_POST['defaultsmsservice'] != $this->settings['SMS_SERVICE']) {
            zb_StorageSet('SENDDOG_SMS_SERVICE', $_POST['defaultsmsservice']);
            log_register('SENDDOG CONFIG SET SMSSERVICE `' . $_POST['defaultsmsservice'] . '`');
        }
    }

    /**
     * Sends all sms storage via TurboSMS service
     *  
     * @return void
     */
    protected function turbosmsPushMessages() {
        $sign = $this->safeEscapeString($this->settings['TSMS_SIGN']);
        $date = date("Y-m-d H:i:s");

        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
//open new database connection
            $TsmsDB = new DbConnect($this->settings['TSMS_GATEWAY'], $this->settings['TSMS_LOGIN'], $this->settings['TSMS_PASSWORD'], 'users', $error_reporting = true, $persistent = false);
            $TsmsDB->open() or die($TsmsDB->error());
            $TsmsDB->query('SET NAMES utf8;');
            foreach ($allSmsQueue as $eachsms) {

                if ((isset($eachsms['number'])) AND ( isset($eachsms['message']))) {
                    $query = "INSERT INTO `" . $this->settings['TSMS_LOGIN'] . "` ( `number`, `sign`, `message`, `wappush`,  `send_time`) VALUES
                    ('" . $eachsms['number'] . "', '" . $sign . "', '" . $eachsms['message'] . "', '', '" . $date . "');
                ";
//push new sms to database
                    $TsmsDB->query($query);
                }
//remove old sent message
                $this->smsQueue->deleteSms($eachsms['filename']);
            }
//close old datalink
            $TsmsDB->close();
        }
    }

    /**
     * Sends all sms storage via sms-fly.com service
     * 
     * @return void
     */
    protected function smsflyPushMessages() {
        $result = '';
        $apiUrl = $this->settings['SMSFLY_GATEWAY'];
        $source = $this->safeEscapeString($this->settings['SMSFLY_SIGN']);
        $description = "Ubilling_" . zb_rand_string(8);
        $start_time = 'AUTO';
        $end_time = 'AUTO';
        $rate = 1;
        $lifetime = 4;

        $user = $this->settings['SMSFLY_LOGIN'];
        $password = $this->settings['SMSFLY_PASSWORD'];

        $allSmsQueue = $this->smsQueue->getQueueData();
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
                $result .= curl_exec($ch);
                curl_close($ch);

//remove old sent message
                $this->smsQueue->deleteSms($eachsms['filename']);
            }
        }
    }

    /**
     * Sends all sms storage via lifecell service
     * 
     * @return void
     */
    protected function lifecellPushMessages() {
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

    /**
     * Sends all sms storage via redsms.ru service
     * 
     * @return void
     */
    protected function redsmsPushMessages() {
        $result = '';
        $timestamp = file_get_contents('https://lk.redsms.ru/get/timestamp.php');
        $api_key = $this->settings['REDSMS_APIKEY'];
        $login = $this->settings['REDSMS_LOGIN'];
        $return = 'xml';
        $sender = $this->settings['REDSMS_SIGN'];

        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            foreach ($allSmsQueue as $io => $eachsms) {

                $phone = str_replace('+', '', $eachsms['number']); //numbers in international format without +
                $text = $eachsms['message'];


                $params = array(
                    'timestamp' => $timestamp,
                    'login' => $login,
                    'phone' => $phone,
                    'text' => $text,
                    'sender' => $sender,
                    'return' => $return);

                ksort($params);
                reset($params);
                $signature = md5(implode($params) . $api_key);
                $query = $this->settings['REDSMS_GATEWAY'] . "?login=" . $login . "&signature=" . $signature . "&phone=" . $phone . "&sender=" . $sender . "&return=" . $return . "&timestamp=" . $timestamp . "&text=" . urlencode($text);
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $query);
                curl_setopt($curl, CURLOPT_ENCODING, "utf-8");
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
                curl_setopt($curl, CURLOPT_TIMEOUT, 120);
                curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($curl);
                curl_close($curl);

//remove old sent message
                $this->smsQueue->deleteSms($eachsms['filename']);
            }
        }
    }

    /**
     * Sends all sms storage via SMSPILOT.RU service
     *
     * @return void
     */
    protected function smspilotPushMessages() {

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
                curl_close($ch);

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
     * Sends all sms storage via SKYSMS service
     *
     * @return void
     */
    protected function skysmsPushMessages() {
        $result = '';
        $skySmsApiUrl = $this->settings['SKYSMS_GATEWAY'];
        $skySmsApiLogin = $this->settings['SKYSMS_LOGIN'];
        $skySsmApiPassw = $this->settings['SKYSMS_PASSWORD'];

        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            global $ubillingConfig;
            $i = 0;
            $smsHistoryEnabled = $ubillingConfig->getAlterParam('SMS_HISTORY_ON');
            $smsHistoryTabFreshIds = array();
            $preSendStatus = __('Perparing for delivery');
            $telepatia = new Telepathy(false);

            if ($smsHistoryEnabled) {
                $telepatia->flushPhoneTelepathyCache();
                $telepatia->usePhones();
            }

            $xmlPacket = '<?xml version="1.0" encoding="utf-8"?>
                          <packet version="1.0">
                          <auth login="' . $skySmsApiLogin . '" password="' . $skySsmApiPassw . '"/>
                          <command name="sendmessage">
                          <message id="0" type="sms">
                          <data charset="lat"></data>
                          <recipients>
                         ';

            foreach ($allSmsQueue as $io => $eachsms) {
                if ($smsHistoryEnabled) {
                    $phoneToSearch = $this->cutInternationalsFromPhoneNum($eachsms['number']);
                    $login = $telepatia->getByPhoneFast($phoneToSearch);

                    $query = "INSERT INTO `sms_history` (`login`, `phone`, `send_status`, `msg_text`) 
                                                  VALUES ('" . $login . "', '" . $eachsms['number'] . "', '" . $preSendStatus . "', '" . $eachsms['message'] . "');";
                    nr_query($query);

                    $recId = simple_get_lastid('sms_history');
                    $smsHistoryTabFreshIds[] = $recId;

                    $xmlPacket .= '<recipient id="' . $recId . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                } else {
                    $xmlPacket .= '<recipient id="' . ++$i . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                }

                $this->smsQueue->deleteSms($eachsms['filename']);
            }

            $telepatia->savePhoneTelepathyCache();

            $xmlPacket .= '</recipients>
                            </message>
                            </command>
                            </packet>
                          ';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_URL, $skySmsApiUrl);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlPacket);
            $result = curl_exec($curl);
            curl_close($curl);

            $parsedResult = zb_xml2array($result);

            if (!empty($parsedResult)) {
                $serverAnswerCode = (isset($parsedResult['packet']['result_attr']['type'])) ? $parsedResult['packet']['result_attr']['type'] : '42';

                if ($serverAnswerCode == '00') {
                    $smsPacketID = $parsedResult['packet']['result']['message_attr']['smsmsgid'];
                    log_register('SENDDOG SKYSMS packet ' . $smsPacketID . ' sent successfully');

                    if ($smsHistoryEnabled) {
                        $recipients = $parsedResult['packet']['result']['message']['recipients']['recipient'];

                        if (empty($recipients)) {
                            $recipients = $parsedResult['packet']['result']['message']['recipients'];
                        }

                        foreach ($recipients as $each => $Recipient) {
                            if (isset($Recipient['id'])) {
                                $query = "UPDATE `sms_history` SET `srvmsgself_id` = '" . $Recipient['smsid'] . "', 
                                                                    `srvmsgpack_id` = '" . $smsPacketID . "',                                                            
                                                                    `date_send` = '" . curdatetime() . "', 
                                                                    `send_status` = '" . __('Message queued') . "' 
                                                WHERE `id` = '" . $Recipient['id'] . "';";
                                nr_query($query);
                            }
                        }
                    }
                } else {
                    $serverErrorMsg = $this->decodeSkySmsErrorMessage($serverAnswerCode);
                    log_register('SENDDOG SKYSMS failed to sent SMS packet. Server answer: ' . $serverErrorMsg . ( ($serverAnswerCode == '42') ? $result : ''));

                    if ($smsHistoryEnabled) {
                        $idsAsStr = implode(',', $smsHistoryTabFreshIds);
                        $query = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "',
                                                            `date_statuschk` = '" . curdatetime() . "',
                                                            `no_statuschk` = '1', 
                                                            `send_status` = '" . __('Failed to send message') . ': ' . $serverErrorMsg . "' 
                                        WHERE `id` IN (" . $idsAsStr . ");";
                        nr_query($query);
                    }
                }
            }
      }
    }

    /**
     * Checks messages status for SKYSMS service
     *
     * @return void
     */
    protected function skysmsCheckMessagesStatus() {
        $smsCheckStatusExpireDays = $this->altCfg['SMS_CHECKSTATUS_EXPIRE_DAYS'];
        $query = "UPDATE `sms_history` SET `no_statuschk` = 1,
                                            `send_status` = '" . __('SMS status check period expired') . "'
                        WHERE ABS( DATEDIFF(NOW(), `date_send`) ) > " . $smsCheckStatusExpireDays . " AND no_statuschk < 1;";
        nr_query($query);

        $query = "SELECT DISTINCT `srvmsgpack_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1;";
        $chkMessages = simple_queryall($query);

        if (!empty($chkMessages)) {
            $skySmsApiUrl = $this->settings['SKYSMS_GATEWAY'];
            $skySmsApiLogin = $this->settings['SKYSMS_LOGIN'];
            $skySmsApiPassw = $this->settings['SKYSMS_PASSWORD'];

            foreach ($chkMessages as $io => $eachmessage) {
                $smsPacketID = $eachmessage['srvmsgpack_id'];

                if (empty($smsPacketID)) {
                    continue;
                }

                $xmlPacket = '<?xml version="1.0" encoding="utf-8"?>
                              <packet version="1.0">
                              <auth login="' . $skySmsApiLogin . '" password="' . $skySmsApiPassw . '"/>
                              <command name="querymessage">
                              <message smsmsgid="' . $smsPacketID . '"/>
                              </command>
                              </packet>
                             ';

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_URL, $skySmsApiUrl);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $xmlPacket);
                $result = curl_exec($curl);
                curl_close($curl);

                $parsedResult = zb_xml2array($result);

                if (!empty($parsedResult)) {
                    $serverAnswerCode = (isset($parsedResult['packet']['result_attr']['type'])) ? $parsedResult['packet']['result_attr']['type'] : '42';

                    if ($serverAnswerCode == '00') {
                        $recipients = $parsedResult['packet']['result']['message']['recipients']['recipient'];

                        if (empty($recipients)) {
                            $recipients = $parsedResult['packet']['result']['message']['recipients'];
                        }

                        foreach ($recipients as $each => $recipient) {
                            if (isset($recipient['smsid'])) {
                                $messageId = $recipient['smsid'];
                                $messageStatus = $recipient['status'];
                                $decodedMessageStatus = $this->decodeSkySmsStatusMessage($messageStatus);

                                $query = "UPDATE `sms_history` SET `date_statuschk` = '" . curdatetime() . "', 
                                                                    `delivered` = '" . $decodedMessageStatus['DeliveredStatus'] . "', 
                                                                    `no_statuschk` = '" . $decodedMessageStatus['NoStatusCheck'] . "', 
                                                                    `send_status` = '" . $decodedMessageStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $messageId . "';";
                                nr_query($query);
                            }
                        }

                        log_register('SENDDOG SKYSMS checked SMS packet ' . $smsPacketID . ' send status');
                    } else {
                        $serverErrorMsg = $this->decodeSkySmsErrorMessage($serverAnswerCode);
                        log_register('SENDDOG SKYSMS failed to get SMS packet ' . $smsPacketID . ' send status. Server answer: ' . $serverErrorMsg . ( ($serverAnswerCode == '42') ? $result : ''));
                    }
                }
            }
        }
    }

    /**
     * Gets the error message code as a parameter and returns appropriate message string
     *
     * @param string $errorMsgCode
     * @return string
     */
    protected function decodeSkySmsErrorMessage($errorMsgCode) {
        switch ($errorMsgCode) {
            case '01':
                $message = __('Incorrect parameters value or insufficient parameters count');
                break;
            case '02':
                $message = __('Database server connection error');
                break;
            case '03':
                $message = __('Database was not found');
                break;
            case '04':
                $message = __('Authorization procedure error');
                break;
            case '05':
                $message = __('Login or password is incorrect');
                break;
            case '06':
                $message = __('Malfunction in user\'s configuration');
                break;
            default:
                $message = __('Error code is unknown. Servers answer:') . '  ' . $errorMsgCode;
        }

        return $message;
    }

    /**
     * Gets the status message code as a parameter and returns appropriate message string
     *
     * @param  string $statusMsgCode
     * @return array
     */
    protected function decodeSkySmsStatusMessage($statusMsgCode) {
        $statusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);

        switch ($statusMsgCode) {
            case 'DELIVERED':
                $statusArray['StatusMsg'] = __('Message is delivered to recipient');
                $statusArray['DeliveredStatus'] = 1;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'TOSEND':
                $statusArray['StatusMsg'] = __('Message is queued for delivering');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'ENROUTE':
                $statusArray['StatusMsg'] = __('Message is sent but not yet delivered to recipient');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'PAUSED':
                $statusArray['StatusMsg'] = __('Message delivering is paused');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            case 'CANCELED':
                $statusArray['StatusMsg'] = __('Message delivering is canceled');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'FAILED':
                $statusArray['StatusMsg'] = __('Failed to send message');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'EXPIRED':
                $statusArray['StatusMsg'] = __('Failed to deliver message - delivery term is expired');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'UNDELIVERABLE':
                $statusArray['StatusMsg'] = __('Message can not be delivered to recipient');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'REJECTED':
                $statusArray['StatusMsg'] = __('Message is rejected by server');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'BADCOST':
                $statusArray['StatusMsg'] = __('Message is not delivered to recipient - can not determine message cost');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
                break;

            case 'UNKNOWN':
                $statusArray['StatusMsg'] = __('Message status is unknown');
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 0;
                break;

            default:
                $statusArray['StatusMsg'] = __('Sending status code is unknown:') . '  ' . $statusMsgCode;
                $statusArray['DeliveredStatus'] = 0;
                $statusArray['NoStatusCheck'] = 1;
        }

        return $statusArray;
    }

    /**
     * Loads and sends all email messages from system queue
     * 
     * @return int
     */
    public function emailProcessing() {
        $email = new UbillingMail();
        $messagesCount = $email->getQueueCount();
        if ($messagesCount > 0) {
            $allMessagesData = $email->getQueueData();
            if (!empty($allMessagesData)) {
                foreach ($allMessagesData as $io => $eachmessage) {
                    $email->directPushEmail($eachmessage['email'], $eachmessage['subj'], $eachmessage['message']);
                    $email->deleteEmail($eachmessage['filename']);
                }
            }
        }
        return ($messagesCount);
    }

    /**
     * Loads and sends all stored SMS from system queue
     * 
     * @return int
     */
    public function smsProcessing() {
        $smsCount = $this->smsQueue->getQueueCount();
        if ($smsCount > 0) {
            switch ($this->settings['SMS_SERVICE']) {
                case 'tsms':
                    $this->turbosmsPushMessages();
                    break;
                case 'smsfly':
                    $this->smsflyPushMessages();
                    break;
                case 'redsms':
                    $this->redsmsPushMessages();
                    break;
                case 'smspilot':
                    $this->smspilotPushMessages();
                    break;
                case 'skysms':
                    $this->skysmsPushMessages();
                    break;
                case 'lifecell':
                    $this->lifecellPushMessages();
                    break;
            }
        }
        return ($smsCount);
    }

    /**
     * Goes through sms_history table and checks statuses for messages
     *
     * @return void
     */
    public function smsHistoryProcessing() {
        switch ($this->settings['SMS_SERVICE']) {
            case 'tsms':
                break;
            case 'smsfly':
                break;
            case 'redsms':
                break;
            case 'smspilot':
                break;
            case 'skysms':
                $this->skysmsCheckMessagesStatus();
                break;
        }
    }

    /**
     * Loads and sends all stored Telegram messages from system queue
     * 
     * @return int
     */
    public function telegramProcessing() {
        $telegram = new UbillingTelegram($this->settings['TELEGRAM_BOTTOKEN']);
        $messagesCount = $telegram->getQueueCount();
        if ($messagesCount > 0) {
            $allMessagesData = $telegram->getQueueData();
            if (!empty($allMessagesData)) {
                foreach ($allMessagesData as $io => $eachmessage) {
                    $telegram->directPushMessage($eachmessage['chatid'], $eachmessage['message']);
                    $telegram->deleteMessage($eachmessage['filename']);
                }
            }
        }
        return ($messagesCount);
    }

    /**
     * Cuts international codes like "+38", "+7" from phone number
     * This function might be supplemented with new country codes and refactored
     *
     * @param $PhoneNumber
     *
     * @return bool|mixed|string
     */
    public function cutInternationalsFromPhoneNum($PhoneNumber) {
// if we have users phones in DB like "0991234567" and some function/module
// appended "+38" or "+7" to the beginning of it and if we need to remove that prefix
// for MYSQL "LIKE" to search properly
        $PhoneNumber = str_replace(array('+7', '+38', '+'), '', $PhoneNumber);

// sometimes phone number may be stored without leading "+"
// and we still need to remove international codes
        $Prefix = '38';
        if (substr($PhoneNumber, 0, strlen($Prefix)) == $Prefix) {
            $PhoneNumber = substr($PhoneNumber, strlen($Prefix));
        }

        $Prefix = '7';
        if (substr($PhoneNumber, 0, strlen($Prefix)) == $Prefix) {
            $PhoneNumber = substr($PhoneNumber, strlen($Prefix));
        }

        return $PhoneNumber;
    }

}

class SendDogAdvanced extends SendDog {

    /**
     * Placeholder for SMS services IDs => APINames
     *
     * @var array
     */
    protected $servicesApiId = array();

    /**
     * Placeholder for default SMS service ID
     *
     * @var string
     */
    protected $defaultSmsServiceId = '';

    /**
     * Placeholder for default SMS service API name
     *
     * @var string
     */
    protected $defaultSmsServiceApi = '';

    /**
     * Contains path to files with services APIs implementations
     */
    const API_IMPL_PATH = 'api/vendor/sms_services_APIs/';

    public function __construct() {
        $this->loadAltCfg();
        $this->initSmsQueue();
        $this->initMessages();
        $this->loadTelegramConfig();
        $this->getServicesAPIsIDs();
    }

    /**
     * Fills up $SrvsAPIsIDs with IDs => APINames
     *
     * @return void
     */
    protected function getServicesAPIsIDs() {
        $allSmsServices = $this->getSmsServicesConfigData();

        if (!empty($allSmsServices)) {
            foreach ($allSmsServices as $index => $record) {
                if ($record['default_service']) {
                    $this->defaultSmsServiceId = $record['id'];
                    $this->defaultSmsServiceApi = $record['api_file_name'];
                }

                $this->servicesApiId[$record['id']] = $record['api_file_name'];
            }
        }
    }

    /**
     * Returns array with contents of API_IMPL_PATH dir with names of implemented services APIs
     *
     * @param bool $useValueAsIndex - if true API name used as array index(key) also
     *
     * @return array
     */
    protected function getImplementedSmsServicesApiNames($useValueAsIndex = false) {
        $apiImplementations = rcms_scandir(self::API_IMPL_PATH, '*.php');

        foreach ($apiImplementations as $index => $item) {
            $apiName = str_replace('.php', '', $item);
            $apiImplementations[$index] = $apiName;

            if ($useValueAsIndex) {
                $apiImplementations[$apiName] = $apiImplementations[$index];
                unset($apiImplementations[$index]);
            }
        }

        return $apiImplementations;
    }

    /**
     * Gets SMS services config data from DB
     *
     * @param string $whereString of the query, including ' WHERE ' keyword
     *
     * @return array
     */
    public function getSmsServicesConfigData($whereString = '') {
        if (empty($whereString)) {
            $whereString = " ";
        }

        $query = "SELECT * FROM `sms_services` " . $whereString . " ;";
        $result = simple_queryall($query);

        return $result;
    }

    /**
     * Returns true if SMS service with such name already exists
     *
     * @param $serviceName
     * @param int $excludeEditedServiceId
     *
     * @return string
     */
    public function checkServiceNameExists($serviceName, $excludeEditedServiceId = 0) {
        $serviceName = trim($serviceName);

        if (empty($excludeEditedServiceId)) {
            $query = "SELECT `id` FROM `sms_services` WHERE `name` = '" . $serviceName . "';";
        } else {
            $query = "SELECT `id` FROM `sms_services` WHERE `name` = '" . $serviceName . "' AND `id` != '" . $excludeEditedServiceId . "';";
        }

        $result = simple_queryall($query);

        return ( empty($result) ) ? '' : $result[0]['id'];
    }

    /**
     * Returns reference to UbillingSMS object
     *
     * @return object
     */
    public function getSmsQueueInstance() {
        return $this->smsQueue;
    }

    /**
     * Returns reference to UbillingMessageHelper object
     *
     * @return object
     */
    public function getUbillingMsgHelperInstance() {
        return $this->messages;
    }

    /**
     * Returns set of inputs, required for Telegram service configuration
     *
     * @return string
     */
    public function renderTelegramConfigInputs() {
        $inputs = wf_tag('h2');
        $inputs .= __('Telegram bot token') . '&nbsp' . wf_Link(self::URL_ME . '&showmisc=telegramcontacts', wf_img_sized('skins/icon_search_small.gif', __('Telegram bot contacts'), '16', '16'));
        $inputs .= wf_tag('h2', true);
        $inputs .= wf_TextInput('edittelegrambottoken', '', $this->settings['TELEGRAM_BOTTOKEN'], false, '50');

        return ($inputs);
    }

    /**
     * Renders JSON for JQDT
     *
     * @param $queryData
     */
    public function renderJSON($queryData) {
        global $ubillingConfig;
        $json = new wf_JqDtHelper();

        if (!empty($queryData)) {
            $data = array();

            foreach ($queryData as $eachRec) {
                foreach ($eachRec as $fieldName => $fieldVal) {
                    switch ($fieldName) {
                        case 'default_service':
                            $data[] = ($fieldVal == 1) ? web_green_led() : web_red_led();
                            break;

                        case 'passwd':
                            if (!$ubillingConfig->getAlterParam('PASSWORDSHIDE')) {
                                $data[] = $fieldVal;
                            }
                            break;

                        default:
                            $data[] = $fieldVal;
                    }
                }

                $linkId = wf_InputId();
                $linkId2 = wf_InputId();
                $linkId3 = wf_InputId();
                $actions = wf_JSAlert('#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteSMSSrv(' . $eachRec['id'] . ', \'' . self::URL_ME . '\', \'deleteSMSSrv\', \'' . wf_InputId() . '\')') . ' ';
                $actions .= wf_tag('a', false, '', 'id="' . $linkId . '" href="#"');
                $actions .= web_edit_icon();
                $actions .= wf_tag('a', true);
                $actions .= wf_nbsp();
                $actions .= wf_tag('a', false, '', 'id="' . $linkId2 . '" href="#"');
                $actions .= wf_img_sized('skins/icon_dollar.gif', __('Balance'), '16', '16');
                $actions .= wf_tag('a', true);
                $actions .= wf_nbsp();
                $actions .= wf_tag('a', false, '', 'id="' . $linkId3 . '" href="#"');
                $actions .= wf_img_sized('skins/icon_sms_micro.gif', __('View SMS sending queue'), '16', '16');
                $actions .= wf_tag('a', true);
                $actions .= wf_tag('script', false, '', 'type="text/javascript"');
                $actions .= '
                                $(\'#' . $linkId . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME . '",
                                        data: { 
                                                action:"editSMSSrv",
                                                smssrvid:"' . $eachRec['id'] . '",                                                                                                                
                                                modalWindowId:"dialog-modal_' . $linkId . '", 
                                                ModalWBID:"body_dialog-modal_' . $linkId . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $linkId . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                                
                                $(\'#' . $linkId2 . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME . '",
                                        data: { 
                                                action:"getBalance",
                                                smssrvid:"' . $eachRec['id'] . '",                                                                                                                
                                                SMSAPIName:"' . $eachRec['api_file_name'] . '",
                                                modalWindowId:"dialog-modal_' . $linkId2 . '", 
                                                ModalWBID:"body_dialog-modal_' . $linkId2 . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $linkId2 . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                                
                                $(\'#' . $linkId3 . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME . '",
                                        data: { 
                                                action:"getSMSQueue",
                                                smssrvid:"' . $eachRec['id'] . '",                                                                                                                
                                                SMSAPIName:"' . $eachRec['api_file_name'] . '",
                                                modalWindowId:"dialog-modal_' . $linkId3 . '", 
                                                ModalWBID:"body_dialog-modal_' . $linkId3 . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $linkId3 . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                            ';
                $actions .= wf_tag('script', true);

                $data[] = $actions;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns JQDT control and some JS bindings for dynamic forms
     *
     * @return string
     */
    public function renderJQDT() {
        global $ubillingConfig;
        $ajaxUrlStr = '' . self::URL_ME . '&ajax=true' . '';
        $jqdtId = 'jqdt_' . md5($ajaxUrlStr);
        $errorModalWindowId = wf_InputId();
        $hidePasswords = $ubillingConfig->getAlterParam('PASSWORDSHIDE');
        $columnTarget1 = ($hidePasswords) ? '4' : '5';
        $columnTarget2 = ($hidePasswords) ? '6' : '7';
        $columnTarget3 = ($hidePasswords) ? '7' : '8';
        $columnTarget4 = ($hidePasswords) ? '[5, 6, 7, 8]' : '[6, 7, 8, 9]';
        $columnTarget5 = ($hidePasswords) ? '[0, 1, 2, 3]' : '[0, 1, 2, 3, 4]';
        $columns = array();
        $opts = ' "order": [[ 0, "desc" ]], 
                                "columnDefs": [ {"className": "dt-head-center", "targets": ' . $columnTarget5 . '},
                                                {"width": "20%", "className": "dt-head-center jqdt_word_wrap", "targets": ' . $columnTarget1 . '}, 
                                                {"width": "8%", "targets": ' . $columnTarget2 . '},
                                                {"width": "10%", "targets": ' . $columnTarget3 . '},
                                                {"className": "dt-center", "targets": ' . $columnTarget4 . '} ]';
        $columns[] = ('ID');
        $columns[] = __('Name');
        $columns[] = __('Login');
        if (!$hidePasswords) {
            $columns[] = __('Password');
        }
        $columns[] = __('Gateway URL/IP');
        $columns[] = __('API key');
        $columns[] = __('Alpha name');
        $columns[] = __('Default service');
        $columns[] = __('API implementation file');
        $columns[] = __('Actions');

        $result = wf_JqDtLoader($columns, $ajaxUrlStr, false, __('results'), 100, $opts);

        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= wf_JSEmptyFunc();
        $result .= wf_JSElemInsertedCatcherFunc();
        $result .= '
                    // making an event binding for "SMS service edit form" Submit action 
                    // to be able to create "SMS service add/edit form" dynamically                    
                    function toggleAlphaNameFieldReadonly() {
                        if ( $(".__SMSSrvAlphaAsLoginChk").is(\':checked\') ) {
                            $(".__SMSSrvAlphaName").val("");
                            $(".__SMSSrvAlphaName").attr("readonly", "readonly");
                            $(".__SMSSrvAlphaName").css(\'background-color\', \'#CECECE\');
                        } else {
                            $(".__SMSSrvAlphaName").removeAttr("readonly");               
                            $(".__SMSSrvAlphaName").css(\'background-color\', \'#FFFFFF\');
                        }
                    }

                    onElementInserted(\'body\', \'.__SMSSrvAlphaAsLoginChk\', function(element) {
                        toggleAlphaNameFieldReadonly();
                    });
                   
                    $(document).on("change", ".__SMSSrvAlphaAsLoginChk", function(evt) {
                          toggleAlphaNameFieldReadonly();
                    });

                    $(document).on("submit", ".__SMSSrvForm", function(evt) {
                        var AlphaNameAsLogin = ( $(".__SMSSrvAlphaAsLoginChk").is(\':checked\') ) ? 1 : 0;
                        //var DefaultService   = ( $(".__SMSSrvDefaultSrvChk").is(\':checked\') ) ? 1 : 0;
                        var DefaultService   = ( $(".__SMSSrvDefaultSrvChk").is(\':checked\') ) ? 1 : ( $(".__DefaultServHidID").val() ) ? 1 : 0;
                        var FrmAction        = $(".__SMSSrvForm").attr("action");
                        var FrmData          = $(".__SMSSrvForm").serialize() + \'&smssrvalphaaslogin=\' + AlphaNameAsLogin + \'&smssrvdefault=\' + DefaultService + \'&errfrmid=' . $errorModalWindowId . '\'; 
                        var modalWindowId         = $(".__SMSSrvForm").closest(\'div\').attr(\'id\');
                        evt.preventDefault();
                    
                        $.ajax({
                            type: "POST",
                            url: FrmAction,
                            data: FrmData,
                            success: function(result) {
                                        if ( !empty(result) ) {                                            
                                            $(document.body).append(result);                                                
                                            $( \'#' . $errorModalWindowId . '\' ).dialog("open");                                                
                                        } else {
                                            $(\'#' . $jqdtId . '\').DataTable().ajax.reload();
                                            $( \'#\'+$(".__SMSSrvFormModalWindowID").val() ).dialog("close");
                                        }
                                    }
                        });                       
                    });
    
                    function deleteSMSSrv(SMSSrvID, AjaxURL, ActionName, ErrFrmID) {
                        $.ajax({
                                type: "POST",
                                url: AjaxURL,
                                data: {action:ActionName, smssrvid:SMSSrvID, errfrmid:ErrFrmID},
                                success: function(result) {                                    
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);
                                                $(\'#\'+ErrFrmID).dialog("open");
                                            }
                                            
                                            $(\'#' . $jqdtId . '\').DataTable().ajax.reload();
                                         }
                        });
                    }
                ';
        $result .= wf_tag('script', true);

        return $result;
    }

    /**
     * Returns SMS srvice addition form
     *
     * @return string
     */
    public function renderAddForm($modalWindowId) {
        global $ubillingConfig;
        $formId = 'Form_' . wf_InputId();
        $alphaAsLoginChkId = 'AlphaAsLoginChkID_' . wf_InputId();
        $defaultServiceChkId = 'DefaultServChkID_' . wf_InputId();
        $defaultServiceHidId = 'DefaultServHidID_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();

        $apiImplementations = $this->getImplementedSmsServicesApiNames(true);

// check if there is any services already added
        $query = "SELECT `id` FROM `sms_services`;";
        $result = simple_queryall($query);
        $useAsDefaultService = ( empty($result) );    // if no services yet - use the first added as default

        $inputs = wf_TextInput('smssrvname', __('Name'), '', true);
        $inputs .= wf_TextInput('smssrvlogin', __('Login'), '', true);
        $inputs .= wf_CheckInput('smssrvalphaaslogin', __('Use login as alpha name'), true, false, $alphaAsLoginChkId, '__SMSSrvAlphaAsLoginChk');
        $inputs .= ($ubillingConfig->getAlterParam('PASSWORDSHIDE')) ? wf_PasswordInput('smssrvpassw', __('Password'), '', true) :
                wf_TextInput('smssrvpassw', __('Password'), '', true);
        $inputs .= wf_TextInput('smssrvurlip', __('Gateway URL/IP'), '', true);
        $inputs .= wf_TextInput('smssrvapikey', __('API key'), '', true);
        $inputs .= wf_TextInput('smssrvalphaname', __('Alpha name'), '', true, '', '', '__SMSSrvAlphaName');
        $inputs .= wf_Selector('smssrvapiimplementation', $apiImplementations, __('API implementation file'), '', true);

        if ($useAsDefaultService) {
            $inputs .= wf_tag('span', false, '', 'style="display: block; margin: 5px 2px"');
            $inputs .= __('Will be used as a default SMS service');
            $inputs .= wf_tag('span', true);
            $inputs .= wf_HiddenInput('smssrvdefault', 'true', $defaultServiceHidId, '__DefaultServHidID');
        } else {
            $inputs .= wf_CheckInput('smssrvdefault', __('Use as default SMS service'), true, false, $defaultServiceChkId, '__SMSSrvDefaultSrvChk');
        }

        $inputs .= wf_HiddenInput('', $modalWindowId, '', '__SMSSrvFormModalWindowID');
        $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), false, true, $closeFormChkId);
        $inputs .= wf_HiddenInput('smssrvcreate', 'true');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __SMSSrvForm', '', $formId);

        return ($form);
    }

    /**
     * Returns SMS service editing form
     *
     * @return string
     */
    public function renderEditForm($smsServiceId, $modalWindowId) {
        global $ubillingConfig;
        $formId = 'Form_' . wf_InputId();
        $alphaAsLoginChkId = 'AlphaAsLoginChkID_' . wf_InputId();
        $defaultServiceChkId = 'DefaultServChkID_' . wf_InputId();
        $closeFormChkId = 'CloseFrmChkID_' . wf_InputId();

        $apiImplementations = $this->getImplementedSmsServicesApiNames(true);
        $smsServiceData = $this->getSmsServicesConfigData(" WHERE `id` = " . $smsServiceId);

        $serviceName = $smsServiceData[0]['name'];
        $serviceLogin = $smsServiceData[0]['login'];
        $servicePassword = $smsServiceData[0]['passwd'];
        $serviceGatewayAddr = $smsServiceData[0]['url_addr'];
        $serviceAlphaName = $smsServiceData[0]['alpha_name'];
        $serviceApiKey = $smsServiceData[0]['api_key'];
        $serviceIsDefault = $smsServiceData[0]['default_service'];
        $serviceApiFile = $smsServiceData[0]['api_file_name'];

        $inputs = wf_TextInput('smssrvname', __('Name'), $serviceName, true);
        $inputs .= wf_TextInput('smssrvlogin', __('Login'), $serviceLogin, true);
        $inputs .= wf_CheckInput('smssrvalphaaslogin', __('Use login as alpha name'), true, (!empty($serviceLogin) and $serviceLogin == $serviceAlphaName), $alphaAsLoginChkId, '__SMSSrvAlphaAsLoginChk');
        $inputs .= ($ubillingConfig->getAlterParam('PASSWORDSHIDE')) ? wf_PasswordInput('smssrvpassw', __('Password'), $servicePassword, true) :
                wf_TextInput('smssrvpassw', __('Password'), $servicePassword, true);
        $inputs .= wf_TextInput('smssrvurlip', __('Gateway URL/IP'), $serviceGatewayAddr, true);
        $inputs .= wf_TextInput('smssrvapikey', __('API key'), $serviceApiKey, true);
        $inputs .= wf_TextInput('smssrvalphaname', __('Alpha name'), $serviceAlphaName, true, '', '', '__SMSSrvAlphaName');
        $inputs .= wf_Selector('smssrvapiimplementation', $apiImplementations, __('API implementation file'), $serviceApiFile, true);
        $inputs .= wf_CheckInput('smssrvdefault', __('Use as default SMS service'), true, $serviceIsDefault, $defaultServiceChkId, '__SMSSrvDefaultSrvChk');
        $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), false, true, $closeFormChkId);
        $inputs .= wf_HiddenInput('', $modalWindowId, '', '__SMSSrvFormModalWindowID');
        $inputs .= wf_HiddenInput('action', 'editSMSSrv');
        $inputs .= wf_HiddenInput('smssrvid', $smsServiceId);
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Edit'));

        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __SMSSrvForm', '', $formId);

        return $form;
    }

    /**
     * Adds SMS service to DB
     *
     * @param $smsServiceName
     * @param $smsServiceLogin
     * @param $smsServicePassword
     * @param $smsServiceBaseUrl
     * @param $smsServiceApiKey
     * @param $smsServiceAlphaName
     * @param $smsServiceApiImplName
     * @param int $useAsDefaultService
     */
    public function addSmsService($smsServiceName, $smsServiceLogin, $smsServicePassword, $smsServiceBaseUrl, $smsServiceApiKey, $smsServiceAlphaName, $smsServiceApiImplName, $useAsDefaultService = 0) {

        if ($useAsDefaultService) {
            $tQuery = "UPDATE `sms_services` SET `default_service` = 0;";
            nr_query($tQuery);
        }

        $tQuery = "INSERT INTO `sms_services` ( `id`,`name`,`login`,`passwd`, `url_addr`, `api_key`, `alpha_name`, `default_service`, `api_file_name`) 
                                      VALUES  ( NULL, '" . $smsServiceName . "','" . $smsServiceLogin . "','" . $smsServicePassword . "','" . $smsServiceBaseUrl . "','" .
                $smsServiceApiKey . "','" . $smsServiceAlphaName . "','" . $useAsDefaultService . "','" . $smsServiceApiImplName . "');";
        nr_query($tQuery);
        log_register('CREATE SMS service [' . $smsServiceName . '] alpha name: `' . $smsServiceAlphaName . '`');
    }

    /**
     * Edits SMS service
     *
     * @param $smsServiceId
     * @param $smsServiceName
     * @param $smsServiceLogin
     * @param $smsServicePassword
     * @param $smsServiceBaseUrl
     * @param $smsServiceApiKey
     * @param $smsServiceAlphaName
     * @param $smsServiceApiImplName
     * @param int $useAsDefaultService
     */
    public function editSmsService($smsServiceId, $smsServiceName, $smsServiceLogin, $smsServicePassword, $smsServiceBaseUrl, $smsServiceApiKey, $smsServiceAlphaName, $smsServiceApiImplName, $useAsDefaultService = 0) {

        if ($useAsDefaultService) {
            $tQuery = "UPDATE `sms_services` SET `default_service` = 0;";
            nr_query($tQuery);
        }

        $tQuery = "UPDATE `sms_services` 
                        SET `name` = '" . $smsServiceName . "', 
                            `login` = '" . $smsServiceLogin . "', 
                            `passwd` = '" . $smsServicePassword . "', 
                            `url_addr` = '" . $smsServiceBaseUrl . "', 
                            `api_key` = '" . $smsServiceApiKey . "', 
                            `alpha_name` = '" . $smsServiceAlphaName . "', 
                            `default_service` = '" . $useAsDefaultService . "', 
                            `api_file_name` = '" . $smsServiceApiImplName . "' 
                    WHERE `id`= '" . $smsServiceId . "' ;";
        nr_query($tQuery);
        log_register('CHANGE SMS service [' . $smsServiceId . '] `' . $smsServiceName . '` alpha name: `' . $smsServiceAlphaName . '`');
    }

    /**
     * Deletes SMS service
     *
     * @param $smsServiceId
     * @param string $smsServiceName
     * @param string $smsServiceAlphaName
     */
    public function deleteSmsService($smsServiceId, $smsServiceName = '', $smsServiceAlphaName = '') {
        $query = "DELETE FROM `sms_services` WHERE `id` = '" . $smsServiceId . "';";
        nr_query($query);
        log_register('DELETE SMS service [' . $smsServiceId . '] `' . $smsServiceName . '` alpha name: `' . $smsServiceAlphaName . '`');
    }

    /**
     * Check if SMS service is protected from deletion
     *
     * @param $smsServiceId
     *
     * @return bool
     */
    public function checkSmsServiceProtected($smsServiceId) {
        $query = "SELECT `id` FROM `sms_services_relations` WHERE `sms_srv_id` = " . $smsServiceId . ";";
        $result = simple_queryall($query);

        return (!empty($result));
    }

    /**
     * Loads and sends all stored SMS from system queue
     * Or checks statuses of already sent SMS
     *
     * @return mixed
     */
    public function smsProcessing($checkStatuses = false) {
        $allMessages = array();
        $smsCount = 0;

        if ($checkStatuses) {
            $smsCheckStatusExpireDays = $this->altCfg['SMS_CHECKSTATUS_EXPIRE_DAYS'];
            $query = "UPDATE `sms_history` SET `no_statuschk` = 1,
                                               `send_status` = '" . __('SMS status check period expired') . "'
                        WHERE ABS( DATEDIFF(NOW(), `date_send`) ) > " . $smsCheckStatusExpireDays . " 
                              AND no_statuschk < 1 AND `delivered` < 1;";
            nr_query($query);

            $query = "SELECT * FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1;";
            $messages = simple_queryall($query);
            $smsCount = count($messages);
            if ($smsCount > 0) {
                $allMessages = zb_sortArray($messages, 'smssrvid');
            }
        } else {
            $smsCount = $this->smsQueue->getQueueCount();
            if ($smsCount > 0) {
                $allMessages = zb_sortArray($this->smsQueue->getQueueData(), 'smssrvid');
            }
        }

        /*
          Annie, are you okay, you okay, you okay, Annie?
          Annie, are you okay, you okay, you okay, Annie?
          Annie, are you okay, you okay, you okay, Annie?
          Annie, are you okay, you okay, you okay, Annie?
         */
        if (!empty($smsCount)) {
            $nextServiceId = null;
            $currentServiceId = null;
            $tmpMessagePack = array();
            $arrayEnd = false;

            end($allMessages);
            $lastArrayKey = key($allMessages);

            foreach ($allMessages as $io => $eachmessage) {
// checking, if we're at the end of array and current element is the last one
                if ($io === $lastArrayKey) {
                    $arrayEnd = true;
// if we're at the end of array and $TmpMessPack is empty - that means that probably array consists only of one element
                    if (empty($tmpMessagePack)) {
                        $tmpMessagePack[] = $eachmessage;
                    }
                }

                if (is_null($nextServiceId) and is_null($currentServiceId)) {
// init the values on the very begining of the array
                    $nextServiceId = $eachmessage['smssrvid'];
                    $currentServiceId = $eachmessage['smssrvid'];
                } else {
// just getting next SMS service ID
                    $nextServiceId = $eachmessage['smssrvid'];
                }
// checking if SMS service ID is changed comparing to previous one or we reached the end of an array
// if so - we need to process accumulated messages in $TmpMessPack
// if not - keep going to the next array element and accumulate messages to $TmpMessPack
                if (($nextServiceId !== $currentServiceId or $arrayEnd) and ! empty($tmpMessagePack)) {
                    $this->actualSmsProcessing($tmpMessagePack, $currentServiceId, $checkStatuses);

                    $tmpMessagePack = array();
                }

                $tmpMessagePack[] = $eachmessage;

// checking and processing the very last element of the $AllMessages array if it has different SMS service ID
                if (($nextServiceId !== $currentServiceId and $arrayEnd) and ! empty($tmpMessagePack)) {
                    $this->actualSmsProcessing($tmpMessagePack, $nextServiceId, $checkStatuses);
                }

                $currentServiceId = $eachmessage['smssrvid'];
            }
        }

        return ($smsCount);
    }

    /**
     * Creates SMS service object from given API file name and processes the
     *
     * @param $messagePack
     * @param int $serviceId
     * @param bool $checkStatuses
     *
     * @return void
     */
    protected function actualSmsProcessing($messagePack, $serviceId = 0, $checkStatuses = false) {
// if for some reason $serviceId is empty - use SMS service chosen as default
        if (empty($serviceId) or $serviceId == $this->defaultSmsServiceId) {
            $serviceId = $this->defaultSmsServiceId;
            $serviceApi = $this->defaultSmsServiceApi;
        } else {
            $serviceApi = $this->servicesApiId[$serviceId];
        }

        if (!empty($serviceApi)) {
            include_once (self::API_IMPL_PATH . $serviceApi . '.php');
            $tmpApiObj = new $serviceApi($serviceId, $messagePack);

            if ($checkStatuses) {
                $tmpApiObj->checkMessagesStatuses();
            } else {
                $tmpApiObj->pushMessages();
            }
        }
    }

    /**
     * Dirty input data filtering
     *
     * @param $string - string to filter
     *
     * @return string
     */
    public function safeEscapeString($string) {
        @$result = preg_replace("#[~@\?\%\/\;=\*\>\<\"\']#Uis", '', $string);

        return ($result);
    }

}

/**
 * Class SMSServiceApi to be inherited by real SMS services APIs implementations
 * located in 'api/vendor/sms_service_APIs' to provide re-usability and common interaction interface for SendDogAdvanced class
 */
abstract class SMSServiceApi {

    /**
     * SendDogAdvanced instance plceholder
     *
     * @var null
     */
    protected $instanceSendDog = null;

    /**
     * Placeholder for settings record data from sms_services table
     *
     * @var array
     */
    protected $apiSettingsRaw = array();

    /**
     * SMS service ID in sms_services table
     *
     * @var int
     */
    protected $serviceId = 0;

    /**
     * SMS service login
     *
     * @var string
     */
    protected $serviceLogin = '';

    /**
     * SMS service password
     *
     * @var string
     */
    protected $servicePassword = '';

    /**
     * SMS service base URL/IP
     *
     * @var string
     */
    protected $serviceGatewayAddr = '';

    /**
     * SMS service alpha name
     *
     * @var string
     */
    protected $serviceAlphaName = '';

    /**
     * SMS service API key
     *
     * @var string
     */
    protected $serviceApiKey = '';

    /**
     * Assigned as a default SMS service
     *
     * @var bool
     */
    protected $isDefaultService = false;

    /**
     * Messages to be processed by push method
     *
     * @var array
     */
    protected $smsMessagePack = array();

    public function __construct($smsServiceId, $smsPack = array()) {
        $this->serviceId = $smsServiceId;
        $this->instanceSendDog = new SendDogAdvanced();
        $this->apiSettingsRaw = $this->instanceSendDog->getSmsServicesConfigData(" WHERE `id` = " . $smsServiceId);
        $this->getSettings();
        $this->smsMessagePack = $smsPack;
    }

    /**
     * Fills up the config placeholders for a particular SMS service
     */
    protected function getSettings() {
        if (!empty($this->apiSettingsRaw)) {
            $this->serviceLogin = $this->apiSettingsRaw[0]['login'];
            $this->servicePassword = $this->apiSettingsRaw[0]['passwd'];
            $this->serviceGatewayAddr = $this->apiSettingsRaw[0]['url_addr'];
            $this->serviceAlphaName = $this->apiSettingsRaw[0]['alpha_name'];
            $this->serviceApiKey = $this->apiSettingsRaw[0]['api_key'];
            $this->isDefaultService = $this->apiSettingsRaw[0]['default_service'];
        }
    }

    /**
     * Returns styled error message about not supported features
     */
    protected function showErrorFeatureIsNotSupported() {
        $errormes = $this->instanceSendDog->getUbillingMsgHelperInstance()->getStyledMessage(__('This SMS service does not support this function'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
        die(wf_modalAutoForm(__('Error'), $errormes, $_POST['modalWindowId'], '', true));
    }

    public abstract function getBalance();

    public abstract function getSMSQueue();

    public abstract function pushMessages();

    public abstract function checkMessagesStatuses();
}

?>
