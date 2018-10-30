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
        $inputs.= wf_Submit(__('Show'));
        $dateform = wf_Form("", 'POST', $inputs, 'glamour');


        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Msg ID'));
        $cells.= wf_TableCell(__('Mobile'));
        $cells.= wf_TableCell(__('Sign'));
        $cells.= wf_TableCell(__('Message'));
        $cells.= wf_TableCell(__('Balance'));
        $cells.= wf_TableCell(__('Cost'));
        $cells.= wf_TableCell(__('Send time'));
        $cells.= wf_TableCell(__('Sended'));
        $cells.= wf_TableCell(__('Status'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($smsArray)) {
            foreach ($smsArray as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['msg_id']);
                $cells.= wf_TableCell($each['number']);
                $cells.= wf_TableCell($each['sign']);
                $msg = wf_modal(__('Show'), __('SMS'), $each['message'], '', '300', '200');
                $cells.= wf_TableCell($msg);
                $cells.= wf_TableCell($each['balance']);
                $cells.= wf_TableCell($each['cost']);
                $cells.= wf_TableCell($each['send_time']);
                $cells.= wf_TableCell($each['sended']);
                $cells.= wf_TableCell($each['status']);
                $rows.=wf_TableRow($cells, 'row5');
                $total++;
            }
        }

        $result.= wf_BackLink(self::URL_ME, '', true);
        $result.= $dateform;
        $result.= wf_TableBody($rows, '100%', '0', 'sortable');
        $result.= __('Total') . ': ' . $total;
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

        $result.= wf_BackLink(self::URL_ME, '', true);
        $result.= $this->messages->getStyledMessage(__('Current account balance') . ': ' . $response, 'info');
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

        $result.= wf_BackLink(self::URL_ME, '', true);
        $result.= $this->messages->getStyledMessage(__('Current account balance') . ': ' . $response . ' RUR', 'info');
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
        $result.=wf_BackLink(self::URL_ME, '', true);

        if (!empty($rawContacts)) {
            $cells = wf_TableCell(__('Chat ID'));
            $cells.= wf_TableCell(__('Type'));
            $cells.= wf_TableCell(__('Name'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($rawContacts as $io => $each) {
                $cells = wf_TableCell($each['chatid']);
                $cells.= wf_TableCell($each['type']);
                $cells.= wf_TableCell($each['name']);
                $rows.= wf_TableRow($cells, 'row3');
            }
            $result.= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
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
        $inputs.= wf_HiddenInput('editconfig', 'true');
        $inputs.= wf_TextInput('edittsmsgateway', __('TurboSMS gateway address'), $this->settings['TSMS_GATEWAY'], true, 30);
        $inputs.= wf_TextInput('edittsmslogin', __('User login to access TurboSMS gateway'), $this->settings['TSMS_LOGIN'], true, 20);
        $inputs.= wf_TextInput('edittsmspassword', __('User password for access TurboSMS gateway'), $this->settings['TSMS_PASSWORD'], true, 20);
        $inputs.= wf_TextInput('edittsmssign', __('TurboSMS') . ' ' . __('Sign'), $this->settings['TSMS_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'tsms') ? true : false;
        $inputs.= wf_RadioInput('defaultsmsservice', __('Use TurboSMS as default SMS service'), 'tsms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Returns set of inputs, required for SMS-Fly service configuration
     * 
     * @return string
     */
    protected function renderSmsflyConfigInputs() {
        $inputs = wf_tag('h2') . __('SMS-Fly') . ' ' . wf_Link(self::URL_ME . '&showmisc=smsflybalance', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs.= wf_TextInput('editsmsflygateway', __('SMS-Fly API address'), $this->settings['SMSFLY_GATEWAY'], true, 30);
        $inputs.= wf_TextInput('editsmsflylogin', __('User login to access SMS-Fly API'), $this->settings['SMSFLY_LOGIN'], true, 20);
        $inputs.= wf_TextInput('editsmsflypassword', __('User password for access SMS-Fly API'), $this->settings['SMSFLY_PASSWORD'], true, 20);
        $inputs.= wf_TextInput('editsmsflysign', __('SMS-Fly') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['SMSFLY_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'smsfly') ? true : false;
        $inputs.= wf_RadioInput('defaultsmsservice', __('Use SMS-Fly as default SMS service'), 'smsfly', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Returns set of inputs, required for RED-Sms service configuration
     * 
     * @return string
     */
    protected function renderRedsmsConfigInputs() {
        $inputs = wf_tag('h2') . __('RED-Sms') . ' ' . wf_Link(self::URL_ME . '&showmisc=redsmsbalance', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs.= wf_TextInput('editredsmsgateway', __('RED-Sms API address'), $this->settings['REDSMS_GATEWAY'], true, 30);
        $inputs.= wf_TextInput('editredsmsbilgateway', __('RED-Sms Balance API address'), $this->settings['REDSMS_BILGATEWAY'], true, 30);
        $inputs.= wf_TextInput('editredsmslogin', __('User login to access RED-Sms API'), $this->settings['REDSMS_LOGIN'], true, 20);
        $inputs.= wf_TextInput('editredsmsapikey', __('User API key for access RED-Sms API'), $this->settings['REDSMS_APIKEY'], true, 20);
        $inputs.= wf_TextInput('editredsmssign', __('RED-Sms') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['REDSMS_SIGN'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'redsms') ? true : false;
        $inputs.= wf_RadioInput('defaultsmsservice', __('Use RED-Sms as default SMS service'), 'redsms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Returns set of inputs, required for SMSPILOT configuration
     *
     * @return string
     */
    protected function renderSmsPilotConfigInputs() {
        $inputs = wf_tag('h2') . __('SMSPILOT') . ' ' . wf_Link(self::URL_ME . '&showmisc=smspilotbalance', wf_img_sized('skins/icon_dollar.gif', __('Balance'), '10', '10'), true) . wf_tag('h2', true);
        $inputs.= wf_TextInput('editsmspilotapikey', __('User API key for access SMSPILOT API'), $this->settings['SMSPILOT_APIKEY'], true, 20);
        $inputs.= wf_TextInput('editsmspilotsign', __('SMSPILOT') . ' ' . __('Sign') . ' (' . __('Alphaname') . ')', $this->settings['SMSPILOT_SIGN'], true, 20);
        $smsServiceFlag = $this->settings['SMS_SERVICE'] === 'SMSPILOT';
        $inputs.= wf_RadioInput('defaultsmsservice', __('Use SMSPILOT as default SMS service'), 'SMSPILOT', true, $smsServiceFlag);
        return $inputs;
    }

    /**
     * Returns set of inputs, required for Skyriver service configuration
     *
     * @return string
     */
    protected function renderSkyriverConfigInputs() {
        $inputs = wf_tag('h2') . 'Skyriver' . wf_tag('h2', true);
        $inputs.= wf_TextInput('editskysmsgateway', __('Skyriver API address'), $this->settings['SKYSMS_GATEWAY'], true, 30);
        $inputs.= wf_TextInput('editskysmslogin', __('User login to access Skyriver API (this is sign also)'), $this->settings['SKYSMS_LOGIN'], true, 20);
        $inputs.= wf_TextInput('editskysmspassword', __('User password for access Skyriver API'), $this->settings['SKYSMS_PASSWORD'], true, 20);
        $smsServiceFlag = ($this->settings['SMS_SERVICE'] == 'skysms') ? true : false;
        $inputs.= wf_RadioInput('defaultsmsservice', __('Use Skyriver as default SMS service'), 'skysms', true, $smsServiceFlag);
        return ($inputs);
    }

    /**
     * Returns set of inputs, required for SMS-Fly service configuration
     * 
     * @return string
     */
    protected function renderTelegramConfigInputs() {
        $inputs = wf_tag('h2') . __('Telegram') . ' ' . wf_Link(self::URL_ME . '&showmisc=telegramcontacts', wf_img_sized('skins/icon_search_small.gif', __('Telegram bot contacts'), '10', '10'), true) . wf_tag('h2', true);
        $inputs.= wf_TextInput('edittelegrambottoken', __('Telegram bot token'), $this->settings['TELEGRAM_BOTTOKEN'], true, 40);

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
        $inputs.= $this->renderSmsflyConfigInputs();
        $inputs.= $this->renderRedsmsConfigInputs();
        $inputs.= $this->renderSmsPilotConfigInputs();
        $inputs.= $this->renderSkyriverConfigInputs();
        $inputs.= $this->renderTelegramConfigInputs();

        $inputs.= wf_Submit(__('Save'));
        $result.= wf_Form('', 'POST', $inputs, 'glamour');


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
                $result.= curl_exec($ch);
                curl_close($ch);

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
        $Result         = '';
        $SkySMSAPIURL   = $this->settings['SKYSMS_GATEWAY'];
        $SkySMSAPILogin = $this->settings['SKYSMS_LOGIN'];
        $SkySMSAPIPassw = $this->settings['SKYSMS_PASSWORD'];

        $allSmsQueue = $this->smsQueue->getQueueData();
        if (!empty($allSmsQueue)) {
            global $ubillingConfig;
            $i = 0;
            $SMSHistoryEnabled = $ubillingConfig->getAlterParam('SMS_HISTORY_ON');
            $SMSHistoryTabFreshIDs = array();
            $PreSendStatus = __('Perparing for delivery');
            $Telepatia = new Telepathy(false);

            if ($SMSHistoryEnabled) {
                $Telepatia->flushPhoneTelepathyCache();
                $Telepatia->usePhones();
            }

            $XMLPacket = '<?xml version="1.0" encoding="utf-8"?>
                          <packet version="1.0">
                          <auth login="' . $SkySMSAPILogin . '" password="' . $SkySMSAPIPassw . '"/>
                          <command name="sendmessage">
                          <message id="0" type="sms">
                          <data charset="lat"></data>
                          <recipients>
                         ';

            foreach ($allSmsQueue as $io => $eachsms) {
                if ($SMSHistoryEnabled) {
                    $PhoneToSearch = $this->cutInternationalsFromPhoneNum($eachsms['number']);
                    $Login = $Telepatia->getByPhoneFast($PhoneToSearch);

                    $tQuery = "INSERT INTO `sms_history` (`login`, `phone`, `send_status`, `msg_text`) 
                                                  VALUES ('" . $Login . "', '" . $eachsms['number'] . "', '" . $PreSendStatus . "', '" . $eachsms['message'] . "');";
                    nr_query($tQuery);

                    $RecID = simple_get_lastid('sms_history');
                    $SMSHistoryTabFreshIDs[] = $RecID;

                    $XMLPacket .= '<recipient id="' . $RecID . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                } else {
                    $XMLPacket .= '<recipient id="' . ++$i . '" address="' . $eachsms['number'] . '">' . $eachsms['message'] . '</recipient>';
                }

                $this->smsQueue->deleteSms($eachsms['filename']);
            }

            $Telepatia->savePhoneTelepathyCache();

            $XMLPacket .= '</recipients>
                            </message>
                            </command>
                            </packet>
                          ';

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_URL, $SkySMSAPIURL);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLPacket);
            $Result = curl_exec($curl);
            curl_close($curl);

            $ParsedResult = zb_xml2array($Result);

            if ( !empty($ParsedResult) ) {
                $ServerAnswerCode = (isset($ParsedResult['packet']['result_attr']['type'])) ? $ParsedResult['packet']['result_attr']['type'] : '42';

                if ($ServerAnswerCode == '00') {
                    $SMSPacketID = $ParsedResult['packet']['result']['message_attr']['smsmsgid'];
                    log_register('SENDDOG SKYSMS packet ' . $SMSPacketID . ' sent successfully');

                    if ($SMSHistoryEnabled) {
                        $Recipients = $ParsedResult['packet']['result']['message']['recipients']['recipient'];

                        if ( empty($Recipients) ) { $Recipients = $ParsedResult['packet']['result']['message']['recipients']; }

                        foreach ($Recipients as $each => $Recipient) {
                            if ( isset($Recipient['id']) ) {
                                $tQuery = "UPDATE `sms_history` SET `srvmsgself_id` = '" . $Recipient['smsid'] . "', 
                                                                    `srvmsgpack_id` = '" . $SMSPacketID . "',                                                            
                                                                    `date_send` = '" . curdatetime() . "', 
                                                                    `send_status` = '" . __('Message queued') . "' 
                                                WHERE `id` = '" . $Recipient['id'] . "';";
                                nr_query($tQuery);
                            }
                        }
                    }
                } else {
                    $ServerErrorMsg = $this->decodeSkySMSErrorMsg($ServerAnswerCode);
                    log_register('SENDDOG SKYSMS failed to sent SMS packet. Server answer: ' . $ServerErrorMsg . ( ($ServerAnswerCode == '42') ? $Result : '') );

                    if ($SMSHistoryEnabled) {
                        $IDsAsStr = implode(',', $SMSHistoryTabFreshIDs);
                        $tQuery = "UPDATE `sms_history` SET `date_send` = '" . curdatetime() . "',
                                                            `date_statuschk` = '" . curdatetime() . "',
                                                            `no_statuschk` = '1', 
                                                            `send_status` = '" . __('Failed to send message') . ': ' . $ServerErrorMsg ."' 
                                        WHERE `id` IN (" . $IDsAsStr . ");";
                        nr_query($tQuery);
                    }
                }
            }

            /*//remove old sent message
            foreach ($allSmsQueue as $io => $eachsms) {
                $this->smsQueue->deleteSms($eachsms['filename']);
            }*/
        }
    }

    /**
     * Checks messages status for SKYSMS service
     *
     * @return void
     */
    protected function skysmsChkMsgStatus() {
        $SMSCheckStatusExpireDays = $this->altCfg['SMS_CHECKSTATUS_EXPIRE_DAYS'];
        $tQuery = "UPDATE `sms_history` SET `no_statuschk` = 1,
                                            `send_status` = '" . __('SMS status check period expired') . "'
                        WHERE ABS( DATEDIFF(NOW(), `date_send`) ) > " . $SMSCheckStatusExpireDays . " AND no_statuschk < 1;";
        nr_query($tQuery);

        $tQuery = "SELECT DISTINCT `srvmsgpack_id` FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1;";
        $ChkMessages = simple_queryall($tQuery);

        if ( !empty($ChkMessages) ) {
            $SkySMSAPIURL   = $this->settings['SKYSMS_GATEWAY'];
            $SkySMSAPILogin = $this->settings['SKYSMS_LOGIN'];
            $SkySMSAPIPassw = $this->settings['SKYSMS_PASSWORD'];

            foreach ($ChkMessages as $io => $EachMsg) {
                $SMSPAcketID = $EachMsg['srvmsgpack_id'];

                if ( empty($SMSPAcketID) ) { continue; }

                $XMLPacket = '<?xml version="1.0" encoding="utf-8"?>
                              <packet version="1.0">
                              <auth login="' . $SkySMSAPILogin . '" password="' . $SkySMSAPIPassw . '"/>
                              <command name="querymessage">
                              <message smsmsgid="' . $SMSPAcketID . '"/>
                              </command>
                              </packet>
                             ';

                $curl = curl_init();
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_URL, $SkySMSAPIURL);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/xml; charset=utf-8", "Accept: text/xml", "Cache-Control: no-cache"));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $XMLPacket);
                $Result = curl_exec($curl);
                curl_close($curl);

                $ParsedResult = zb_xml2array($Result);

                if ( !empty($ParsedResult) ) {
                    $ServerAnswerCode = (isset($ParsedResult['packet']['result_attr']['type'])) ? $ParsedResult['packet']['result_attr']['type'] : '42';

                    if ($ServerAnswerCode == '00') {
                        $Recipients = $ParsedResult['packet']['result']['message']['recipients']['recipient'];

                        if ( empty($Recipients) ) { $Recipients = $ParsedResult['packet']['result']['message']['recipients']; }

                        foreach ($Recipients as $each => $Recipient) {
                            if (isset($Recipient['smsid'])) {
                                $MsgSelfID         = $Recipient['smsid'];
                                $MsgStatus         = $Recipient['status'];
                                $DecodedMsgStatus  = $this->decodeSkySMSStatusMsg($MsgStatus);

                                $tQuery = "UPDATE `sms_history` SET `date_statuschk` = '". curdatetime() . "', 
                                                                    `delivered` = '" . $DecodedMsgStatus['DeliveredStatus'] . "', 
                                                                    `no_statuschk` = '" . $DecodedMsgStatus['NoStatusCheck'] . "', 
                                                                    `send_status` = '" . $DecodedMsgStatus['StatusMsg'] . "' 
                                                WHERE `srvmsgself_id` = '" . $MsgSelfID . "';";
                                nr_query($tQuery);
                            }
                        }

                        log_register('SENDDOG SKYSMS checked SMS packet ' . $SMSPAcketID . ' send status');
                    } else {
                        $ServerErrorMsg = $this->decodeSkySMSErrorMsg($ServerAnswerCode);
                        log_register('SENDDOG SKYSMS failed to get SMS packet ' . $SMSPAcketID . ' send status. Server answer: ' . $ServerErrorMsg . ( ($ServerAnswerCode == '42') ? $Result : '') );
                    }
                }
            }
        }
    }

    /**
     * Gets the error message code as a parameter and returns appropriate message string
     *
     * @param string $ErrorMsgCode
     * @return string
     */
    protected function decodeSkySMSErrorMsg($ErrorMsgCode) {
        switch ($ErrorMsgCode) {
            case '01':
                $Message = __('Incorrect parameters value or insufficient parameters count');
                break;
            case '02':
                $Message = __('Database server connection error');
                break;
            case '03':
                $Message = __('Database was not found');
                break;
            case '04':
                $Message = __('Authorization procedure error');
                break;
            case '05':
                $Message = __('Login or password is incorrect');
                break;
            case '06':
                $Message = __('Malfunction in user\'s configuration');
                break;
            default:
                $Message = __('Error code is unknown. Servers answer:') . '  ' . $ErrorMsgCode;
        }

        return $Message;
    }

    /**
     * Gets the status message code as a parameter and returns appropriate message string
     *
     * @param  string $StatusMsgCode
     * @return array
     */
    protected function decodeSkySMSStatusMsg($StatusMsgCode) {
        $StatusArray = array('StatusMsg' => '', 'DeliveredStatus' => 0, 'NoStatusCheck' => 0);

        switch ($StatusMsgCode) {
            case 'DELIVERED':
                $StatusArray['StatusMsg'] = __('Message is delivered to recipient');
                $StatusArray['DeliveredStatus'] = 1;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'TOSEND':
                $StatusArray['StatusMsg'] = __('Message is queued for delivering');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'ENROUTE':
                $StatusArray['StatusMsg'] = __('Message is sent but not yet delivered to recipient');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'PAUSED':
                $StatusArray['StatusMsg'] = __('Message delivering is paused');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            case 'CANCELED':
                $StatusArray['StatusMsg'] = __('Message delivering is canceled');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'FAILED':
                $StatusArray['StatusMsg'] = __('Failed to send message');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'EXPIRED':
                $StatusArray['StatusMsg'] = __('Failed to deliver message - delivery term is expired');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'UNDELIVERABLE':
                $StatusArray['StatusMsg'] = __('Message can not be delivered to recipient');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'REJECTED':
                $StatusArray['StatusMsg'] = __('Message is rejected by server');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'BADCOST':
                $StatusArray['StatusMsg'] = __('Message is not delivered to recipient - can not determine message cost');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
                break;

            case 'UNKNOWN':
                $StatusArray['StatusMsg'] = __('Message status is unknown');
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 0;
                break;

            default:
                $StatusArray['StatusMsg'] = __('Sending status code is unknown:') . '  ' . $StatusMsgCode;
                $StatusArray['DeliveredStatus'] = 0;
                $StatusArray['NoStatusCheck'] = 1;
        }

        return $StatusArray;
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
                case 'SMSPILOT':
                    $this->smspilotPushMessages();
                    break;
                case 'skysms':
                    $this->skysmsPushMessages();
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
            case 'SMSPILOT':
                break;
            case 'skysms':
                $this->skysmsChkMsgStatus();
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
    protected $SrvsAPIsIDs = array();

    /**
     * Placeholder for default SMS service ID
     *
     * @var string
     */
    protected $DefaultSMSServiceID = '';

    /**
     * Placeholder for default SMS service API name
     *
     * @var string
     */
    protected $DefaultSMSServiceAPI = '';

    /**
     * Contains path to files with services APIs implementations
     */
    const API_IMPL_PATH = 'content/sms_services_APIs/';

    public function __construct() {
        $this->loadAltCfg();
        $this->initSmsQueue();
        $this->initMessages();
        $this->loadTelegramConfig();
        $this->getSrvsAPIsIDs();
    }

    /**
     * Fills up $SrvsAPIsIDs with IDs => APINames
     *
     * @return void
     */
    protected function getSrvsAPIsIDs() {
        $AllSMSSrvs = $this->getSMSServicesConfigData();

        if ( !empty($AllSMSSrvs) ) {
            foreach ($AllSMSSrvs as $Index => $Record) {
                if ($Record['default_service']) {
                    $this->DefaultSMSServiceID = $Record['id'];
                    $this->DefaultSMSServiceAPI = $Record['api_file_name'];
                }

                $this->SrvsAPIsIDs[$Record['id']] = $Record['api_file_name'];
            }
        }
    }

    /**
     * Returns array with contents of API_IMPL_PATH dir with names of implemented services APIs
     *
     * @param bool $UseValueAsIndex - if true API name used as array index(key) also
     *
     * @return array
     */
    protected function getImplementedSMSSrvsAPIsNames($UseValueAsIndex = false) {
        $APIImplementations = rcms_scandir(self::API_IMPL_PATH, '*.php');

        foreach($APIImplementations as $index => $item) {
            $APIName = str_replace('.php', '', $item);
            $APIImplementations[$index] = $APIName;

            if ($UseValueAsIndex) {
                $APIImplementations[$APIName] = $APIImplementations[$index];
                unset($APIImplementations[$index]);
            }
        }

        return $APIImplementations;
    }

    /**
     * Gets SMS services config data from DB
     *
     * @param string $WHEREString
     *
     * @return array
     */
    public function getSMSServicesConfigData($WHEREString = '') {
        if ( empty($WHEREString) ) {
            $WHEREString = " ";
        }

        $tQuery = "SELECT * FROM `sms_services` " . $WHEREString . " ;";
        $Result = simple_queryall($tQuery);

        return $Result;
    }

    /**
     * Returns true if SMS service with such name already exists
     *
     * @param $SrvName
     * @param int $ExcludeEditedSrvID
     *
     * @return string
     */
    public function checkServiceNameExists($SrvName, $ExcludeEditedSrvID = 0) {
        $SrvName = trim($SrvName);

        if ( empty($ExcludeEditedSrvID) ) {
            $query = "SELECT `id` FROM `sms_services` WHERE `name` = '" . $SrvName . "';";
        } else {
            $query = "SELECT `id` FROM `sms_services` WHERE `name` = '" . $SrvName . "' AND `id` != '" . $ExcludeEditedSrvID . "';";
        }

        $result = simple_queryall($query);

        return ( empty($result) ) ? '' : $result[0]['id'];
    }

    /**
     * Returns reference to UbillingSMS object
     *
     * @return object
     */
    public function getSMSQueueInstance() {
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
        $inputs.= __('Telegram bot token') . '&nbsp' . wf_Link(self::URL_ME . '&showmisc=telegramcontacts', wf_img_sized('skins/icon_search_small.gif', __('Telegram bot contacts'), '16', '16'));
        $inputs.= wf_tag('h2', true);
        $inputs.= wf_TextInput('edittelegrambottoken', '', $this->settings['TELEGRAM_BOTTOKEN'], false, '50');

        return ($inputs);
    }

    /**
     * Renders JSON for JQDT
     *
     * @param $QueryData
     */
    public function renderJSON($QueryData) {
        global $ubillingConfig;
        $json = new wf_JqDtHelper();

        if ( !empty($QueryData) ) {
            $data = array();

            foreach ($QueryData as $EachRec) {
                foreach ($EachRec as $FieldName => $FieldVal) {
                    switch ($FieldName) {
                        case 'default_service':
                            $data[] = ($FieldVal == 1) ? web_green_led() : web_red_led();
                            break;

                        case 'passwd':
                            if ( !$ubillingConfig->getAlterParam('PASSWORDSHIDE') ) {
                                $data[] = $FieldVal;
                            }
                            break;

                        default:
                            $data[] = $FieldVal;
                    }
                }

                $LnkID   = wf_InputId();
                $LnkID2  = wf_InputId();
                $LnkID3  = wf_InputId();
                $Actions = wf_JSAlert('#', web_delete_icon(), 'Removing this may lead to irreparable results',
                         'deleteSMSSrv(' . $EachRec['id'] . ', \'' . self::URL_ME . '\', \'deleteSMSSrv\', \'' . wf_InputId() . '\')') . ' ';
                $Actions .= wf_tag('a', false, '', 'id="' . $LnkID . '" href="#"');
                $Actions .= web_edit_icon();
                $Actions .= wf_tag('a', true);
                $Actions .= wf_nbsp();
                $Actions .= wf_tag('a', false, '', 'id="' . $LnkID2 . '" href="#"');
                $Actions .= wf_img_sized('skins/icon_dollar.gif', __('Balance'), '16', '16');
                $Actions .= wf_tag('a', true);
                $Actions .= wf_nbsp();
                $Actions .= wf_tag('a', false, '', 'id="' . $LnkID3 . '" href="#"');
                $Actions .= wf_img_sized('skins/icon_sms_micro.gif', __('View SMS sending queue'), '16', '16');
                $Actions .= wf_tag('a', true);
                $Actions .= wf_tag('script', false, '', 'type="text/javascript"');
                $Actions .= '
                                $(\'#' . $LnkID . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME .'",
                                        data: { 
                                                action:"editSMSSrv",
                                                smssrvid:"' . $EachRec['id'] . '",                                                                                                                
                                                ModalWID:"dialog-modal_' . $LnkID . '", 
                                                ModalWBID:"body_dialog-modal_' . $LnkID . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $LnkID . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                                
                                $(\'#' . $LnkID2 . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME .'",
                                        data: { 
                                                action:"getBalance",
                                                smssrvid:"' . $EachRec['id'] . '",                                                                                                                
                                                SMSAPIName:"' . $EachRec['api_file_name'] . '",
                                                ModalWID:"dialog-modal_' . $LnkID2 . '", 
                                                ModalWBID:"body_dialog-modal_' . $LnkID2 . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $LnkID2 . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                                
                                $(\'#' . $LnkID3 . '\').click(function(evt) {
                                    $.ajax({
                                        type: "POST",
                                        url: "' . self::URL_ME .'",
                                        data: { 
                                                action:"getSMSQueue",
                                                smssrvid:"' . $EachRec['id'] . '",                                                                                                                
                                                SMSAPIName:"' . $EachRec['api_file_name'] . '",
                                                ModalWID:"dialog-modal_' . $LnkID3 . '", 
                                                ModalWBID:"body_dialog-modal_' . $LnkID3 . '"                                                        
                                               },
                                        success: function(result) {
                                                    $(document.body).append(result);
                                                    $(\'#dialog-modal_' . $LnkID3 . '\').dialog("open");
                                                 }
                                    });
            
                                    evt.preventDefault();
                                    return false;
                                });
                            ';
                $Actions .= wf_tag('script', true);

                $data[] = $Actions;

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
        $AjaxURLStr     = '' . self::URL_ME . '&ajax=true' . '';
        $JQDTID         = 'jqdt_' . md5($AjaxURLStr);
        $ErrorModalWID  = wf_InputId();
        $HidePasswords  = $ubillingConfig->getAlterParam('PASSWORDSHIDE');
        $СolumnTarget1  = ($HidePasswords) ? '4' : '5';
        $СolumnTarget2  = ($HidePasswords) ? '6' : '7';
        $СolumnTarget3  = ($HidePasswords) ? '7' : '8';
        $СolumnTarget4  = ($HidePasswords) ? '[5, 6, 7, 8]' : '[6, 7, 8, 9]';
        $columns        = array();
        $opts           = ' "order": [[ 0, "desc" ]], 
                            "columnDefs": [ {"className": "dt-head-center", "targets": [0, 1, 2, 3, 4]},
                                            {"width": "20%", "className": "dt-head-center jqdt_word_wrap", "targets": ' . $СolumnTarget1 . '}, 
                                            {"width": "8%", "targets": ' . $СolumnTarget2 . '},
                                            {"width": "10%", "targets": ' . $СolumnTarget3 . '},
                                            {"className": "dt-center", "targets": ' . $СolumnTarget4 . '} ]';
        $columns[] = ('ID');
        $columns[] = __('Name');
        $columns[] = __('Login');
        if ( !$HidePasswords ) {
            $columns[] = __('Password');
        }
        $columns[] = __('Gateway URL/IP');
        $columns[] = __('API key');
        $columns[] = __('Alpha name');
        $columns[] = __('Default service');
        $columns[] = __('API implementation file');
        $columns[] = __('Actions');

        $result = wf_JqDtLoader($columns, $AjaxURLStr, false,  __('results'), 100, $opts);

        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= wf_JSEmptyFunc();
        $result .= wf_JSElemInsertedCatcherFunc();
        $result .= '
                    // making an event binding for "SMS service edit form" Submit action 
                    // to be able to create "SMS service add/edit form" dynamically                    
                    function toggleAlphaNameFieldReadonly() {
                        if ( $(".__SMSSrvAlphaAsLoginChk").is(\':checked\') ) {
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
                        var FrmData          = $(".__SMSSrvForm").serialize() + \'&smssrvalphaaslogin=\' + AlphaNameAsLogin + \'&smssrvdefault=\' + DefaultService + \'&errfrmid=' . $ErrorModalWID . '\'; 
                        var ModalWID         = $(".__SMSSrvForm").closest(\'div\').attr(\'id\');
                        evt.preventDefault();
                    
                        $.ajax({
                            type: "POST",
                            url: FrmAction,
                            data: FrmData,
                            success: function(result) {
                                        if ( !empty(result) ) {                                            
                                            $(document.body).append(result);                                                
                                            $( \'#' . $ErrorModalWID . '\' ).dialog("open");                                                
                                        } else {
                                            $(\'#' . $JQDTID . '\').DataTable().ajax.reload();
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
                                            
                                            $(\'#' . $JQDTID . '\').DataTable().ajax.reload();
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
    public function renderAddForm($ModalWID) {
        global $ubillingConfig;
        $FormID             = 'Form_' . wf_InputId();
        $AlphaAsLoginChkID  = 'AlphaAsLoginChkID_' . wf_InputId();
        $DefaultServChkID   = 'DefaultServChkID_' . wf_InputId();
        $DefaultServHidID   = 'DefaultServHidID_' . wf_InputId();
        $CloseFrmChkID      = 'CloseFrmChkID_' . wf_InputId();

        $APIImplementations = $this->getImplementedSMSSrvsAPIsNames(true);

        // check if there is any services already added
        $tQuery = "SELECT `id` FROM `sms_services`;";
        $Result = simple_queryall($tQuery);
        $UseAsDefaultSrv = (empty($Result));    // if no services yet - use the first added as default

        $inputs = wf_TextInput('smssrvname', __('Name'), '', true);
        $inputs .= wf_TextInput('smssrvlogin', __('Login'), '', true);
        $inputs .= wf_CheckInput('smssrvalphaaslogin', __('Use login as alpha name'), true, false, $AlphaAsLoginChkID, '__SMSSrvAlphaAsLoginChk');
        $inputs .= ($ubillingConfig->getAlterParam('PASSWORDSHIDE')) ? wf_PasswordInput('smssrvpassw', __('Password'), '', true) :
                                                                              wf_TextInput('smssrvpassw', __('Password'), '', true);
        $inputs .= wf_TextInput('smssrvurlip', __('Gateway URL/IP'), '', true);
        $inputs .= wf_TextInput('smssrvapikey', __('API key'), '', true);
        $inputs .= wf_TextInput('smssrvalphaname', __('Alpha name'), '', true, '', '', '__SMSSrvAlphaName');
        $inputs .= wf_Selector('smssrvapiimplementation', $APIImplementations, __('API implementation file'), '', true);

        if ($UseAsDefaultSrv) {
            $inputs .= wf_tag('span', false, '', 'style="display: block; margin: 5px 2px"');
            $inputs .= __('Will be used as a default SMS service');
            $inputs .= wf_tag('span', true);
            $inputs .= wf_HiddenInput('smssrvdefault', 'true', $DefaultServHidID, '__DefaultServHidID');
        } else {
            $inputs .= wf_CheckInput('smssrvdefault', __('Use as default SMS service'), true, false, $DefaultServChkID, '__SMSSrvDefaultSrvChk');
        }

        $inputs.= wf_HiddenInput('', $ModalWID, '', '__SMSSrvFormModalWindowID');
        $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), false, true, $CloseFrmChkID);
        $inputs .= wf_HiddenInput('smssrvcreate', 'true');
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Create'));
        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __SMSSrvForm', '', $FormID);

        return ($form);
    }

    /**
     * Returns SMS service editing form
     *
     * @return string
     */
    public function renderEditForm($SMSSrvID, $ModalWID) {
        global $ubillingConfig;
        $FormID             = 'Form_' . wf_InputId();
        $AlphaAsLoginChkID  = 'AlphaAsLoginChkID_' . wf_InputId();
        $DefaultServChkID   = 'DefaultServChkID_' . wf_InputId();
        $CloseFrmChkID      = 'CloseFrmChkID_' . wf_InputId();

        $APIImplementations = $this->getImplementedSMSSrvsAPIsNames(true);
        $SMSSrvData = $this->getSMSServicesConfigData(" WHERE `id` = " . $SMSSrvID);

        $SrvName        = $SMSSrvData[0]['name'];
        $SrvLogin       = $SMSSrvData[0]['login'];
        $SrvPassword    = $SMSSrvData[0]['passwd'];
        $SrvGatewayAddr = $SMSSrvData[0]['url_addr'];
        $SrvAlphaName   = $SMSSrvData[0]['alpha_name'];
        $SrvAPIKey      = $SMSSrvData[0]['api_key'];
        $SrvIsDefault   = $SMSSrvData[0]['default_service'];
        $SrvAPIFile     = $SMSSrvData[0]['api_file_name'];

        $inputs = wf_TextInput('smssrvname', __('Name'), $SrvName, true);
        $inputs .= wf_TextInput('smssrvlogin', __('Login'), $SrvLogin, true);
        $inputs .= wf_CheckInput('smssrvalphaaslogin', __('Use login as alpha name'), true, (!empty($SrvLogin) and $SrvLogin == $SrvAlphaName), $AlphaAsLoginChkID, '__SMSSrvAlphaAsLoginChk');
        $inputs .= ($ubillingConfig->getAlterParam('PASSWORDSHIDE')) ? wf_PasswordInput('smssrvpassw', __('Password'), $SrvPassword, true) :
                                                                              wf_TextInput('smssrvpassw', __('Password'), $SrvPassword, true);
        $inputs .= wf_TextInput('smssrvurlip', __('Gateway URL/IP'), $SrvGatewayAddr, true);
        $inputs .= wf_TextInput('smssrvapikey', __('API key'), $SrvAPIKey, true);
        $inputs .= wf_TextInput('smssrvalphaname', __('Alpha name'), $SrvAlphaName, true, '', '', '__SMSSrvAlphaName');
        $inputs .= wf_Selector('smssrvapiimplementation', $APIImplementations, __('API implementation file'), $SrvAPIFile, true);
        $inputs .= wf_CheckInput('smssrvdefault', __('Use as default SMS service'), true, $SrvIsDefault, $DefaultServChkID, '__SMSSrvDefaultSrvChk');
        $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), false, true, $CloseFrmChkID);
        $inputs.= wf_HiddenInput('', $ModalWID, '', '__SMSSrvFormModalWindowID');
        $inputs.= wf_HiddenInput('action', 'editSMSSrv');
        $inputs.= wf_HiddenInput('smssrvid', $SMSSrvID);
        $inputs .= wf_delimiter();
        $inputs .= wf_Submit(__('Edit'));

        $form = wf_Form(self::URL_ME, 'POST', $inputs, 'glamour __SMSSrvForm', '', $FormID);

        return $form;
    }

    /**
     * Adds SMS service to DB
     *
     * @param $SMSSrvName
     * @param $SMSSrvLogin
     * @param $SMSSrvPass
     * @param $SMSSrvBaseURL
     * @param $SMSSrvAPIKey
     * @param $SMSSrvAlphaName
     * @param $SMSSrvAPIImplName
     * @param int $UseAsDefaultSrv
     */
    public function addSMSService(  $SMSSrvName, $SMSSrvLogin, $SMSSrvPass,
                                    $SMSSrvBaseURL, $SMSSrvAPIKey, $SMSSrvAlphaName,
                                    $SMSSrvAPIImplName, $UseAsDefaultSrv = 0 ) {

        if ($UseAsDefaultSrv) {
            $tQuery = "UPDATE `sms_services` SET `default_service` = 0;";
            nr_query($tQuery);
        }

        $tQuery = "INSERT INTO `sms_services` ( `id`,`name`,`login`,`passwd`, `url_addr`, `api_key`, `alpha_name`, `default_service`, `api_file_name`) 
                                      VALUES  ( NULL, '" . $SMSSrvName . "','" . $SMSSrvLogin . "','" . $SMSSrvPass . "','" . $SMSSrvBaseURL . "','" .
                                                $SMSSrvAPIKey . "','" . $SMSSrvAlphaName . "','" . $UseAsDefaultSrv . "','" . $SMSSrvAPIImplName  . "');";
        nr_query($tQuery);
        log_register('CREATE SMS service [' . $SMSSrvName . '] alpha name: `' . $SMSSrvAlphaName . '`');
    }

    /**
     * Edits SMS service
     *
     * @param $SMSSrvID
     * @param $SMSSrvName
     * @param $SMSSrvLogin
     * @param $SMSSrvPass
     * @param $SMSSrvBaseURL
     * @param $SMSSrvAPIKey
     * @param $SMSSrvAlphaName
     * @param $SMSSrvAPIImplName
     * @param int $UseAsDefaultSrv
     */
    public function editSMSService( $SMSSrvID, $SMSSrvName, $SMSSrvLogin, $SMSSrvPass,
                                    $SMSSrvBaseURL, $SMSSrvAPIKey, $SMSSrvAlphaName,
                                    $SMSSrvAPIImplName, $UseAsDefaultSrv = 0 ) {

        if ($UseAsDefaultSrv) {
            $tQuery = "UPDATE `sms_services` SET `default_service` = 0;";
            nr_query($tQuery);
        }

        $tQuery = "UPDATE `sms_services` 
                        SET `name` = '" . $SMSSrvName . "', 
                            `login` = '" . $SMSSrvLogin . "', 
                            `passwd` = '" . $SMSSrvPass . "', 
                            `url_addr` = '" . $SMSSrvBaseURL . "', 
                            `api_key` = '" . $SMSSrvAPIKey . "', 
                            `alpha_name` = '" . $SMSSrvAlphaName . "', 
                            `default_service` = '" . $UseAsDefaultSrv . "', 
                            `api_file_name` = '" . $SMSSrvAPIImplName . "' 
                    WHERE `id`= '" . $SMSSrvID . "' ;";
        nr_query($tQuery);
        log_register('CHANGE SMS service [' . $SMSSrvID . '] `' . $SMSSrvName . '` alpha name: `' . $SMSSrvAlphaName . '`' );
    }

    /**
     * Deletes SMS service
     *
     * @param $SMSSrvID
     * @param string $SMSSrvName
     * @param string $SMSSrvAlphaName
     */
    public function deleteSMSService($SMSSrvID, $SMSSrvName = '', $SMSSrvAlphaName = '') {
        $tQuery = "DELETE FROM `sms_services` WHERE `id` = '" . $SMSSrvID . "';";
        nr_query($tQuery);
        log_register('DELETE SMS service [' . $SMSSrvID . '] `' . $SMSSrvName . '` alpha name: `' . $SMSSrvAlphaName . '`' );
    }

    /**
     * Check if SMS service is protected from deletion
     *
     * @param $SrvID
     *
     * @return bool
     */
    public function checkSMSSrvProtected($SrvID) {
        $tQuery = "SELECT `id` FROM `sms_services_relations` WHERE `sms_srv_id` = " . $SrvID . ";";
        $Result = simple_queryall($tQuery);

        return (!empty($Result));
    }

    /**
     * Loads and sends all stored SMS from system queue
     * Or checks statuses of already sent SMS
     *
     * @return mixed
     */
    public function smsProcessing($CheckStatuses = false) {
        $AllMessages = array();
        $SmsCount = 0;

        if ($CheckStatuses) {
            $SMSCheckStatusExpireDays = $this->altCfg['SMS_CHECKSTATUS_EXPIRE_DAYS'];
            $tQuery = "UPDATE `sms_history` SET `no_statuschk` = 1,
                                            `send_status` = '" . __('SMS status check period expired') . "'
                        WHERE ABS( DATEDIFF(NOW(), `date_send`) ) > " . $SMSCheckStatusExpireDays . " AND no_statuschk < 1;";
            nr_query($tQuery);

            $tQuery = "SELECT * FROM `sms_history` WHERE `no_statuschk` < 1 AND `delivered` < 1;";
            $Messages = simple_queryall($tQuery);
            $SmsCount = count($Messages);
            if ($SmsCount > 0) { $AllMessages = zb_sortArray($Messages, 'smssrvid'); }
        } else {
            $SmsCount = $this->smsQueue->getQueueCount();
            if ($SmsCount > 0) { $AllMessages = zb_sortArray($this->smsQueue->getQueueData(), 'smssrvid'); }
        }

        /*
        Annie, are you okay, you okay, you okay, Annie?
        Annie, are you okay, you okay, you okay, Annie?
        Annie, are you okay, you okay, you okay, Annie?
        Annie, are you okay, you okay, you okay, Annie?
        */
        if ( !empty($SmsCount) ) {
            $NextSrvID = null;
            $CurSrvID = null;
            $TmpSrvAPI = '';
            $TmpMessPack = array();
            $ArrayEnd = false;

            end($AllMessages);
            $LastArrayKey = key($AllMessages);

            foreach ($AllMessages as $io => $EachMsg) {
                // checking, if we're at the end of array and current element is the last one
                if ($io === $LastArrayKey) {
                    $ArrayEnd = true;
                    // if we're at the end of array and $TmpMessPack is empty - that means that probably array consists only of one element
                    if ( empty($TmpMessPack) ) { $TmpMessPack[] = $EachMsg; }
                }

                if ( is_null($NextSrvID) and is_null($CurSrvID) ) {
                    // init the values on the very begining of the array
                    $NextSrvID = $EachMsg['smssrvid'];
                    $CurSrvID = $EachMsg['smssrvid'];
                } else {
                    // just getting next SMS service ID
                    $NextSrvID = $EachMsg['smssrvid'];
                }
                // checking if SMS service ID is changed comparing to previous one or we reached the end of an array
                // if so - we need to process accumulated messages in $TmpMessPack
                // if not - keep going to the next array element and accumulate messages to $TmpMessPack
                if ( ($NextSrvID !== $CurSrvID or $ArrayEnd) and !empty($TmpMessPack) ) {
                    $this->actualSMSProcessing($TmpMessPack, $CurSrvID, $CheckStatuses);

                    $TmpMessPack = array();
                }

                $TmpMessPack[] = $EachMsg;

                // checking and processing the very last element of the $AllMessages array if it has different SMS service ID
                if ( ($NextSrvID !== $CurSrvID and $ArrayEnd) and !empty($TmpMessPack) ) {
                    $this->actualSMSProcessing($TmpMessPack, $NextSrvID, $CheckStatuses);
                }

                $CurSrvID = $EachMsg['smssrvid'];
            }
        }

        return ($SmsCount);
    }

    /**
     * Creates SMS service object from given API file name and processes the
     *
     * @param $MessagePack
     * @param int $SrvID
     * @param bool $ChkStatuses
     *
     * @return void
     */
    protected function actualSMSProcessing($MessagePack, $SrvID = 0, $ChkStatuses = false) {
        // if for some reason $SrvID is empty - use SMS service chosen as default
        if ( empty($SrvID) or $SrvID == $this->DefaultSMSServiceID ) {
            $SrvID = $this->DefaultSMSServiceID;
            $SrvAPI = $this->DefaultSMSServiceAPI;
        } else {
            $SrvAPI = $this->SrvsAPIsIDs[$SrvID];
        }

        if ( !empty($SrvAPI) ) {
            include (self::API_IMPL_PATH . $SrvAPI . '.php');
            $tmpApiObj = new $SrvAPI($SrvID, $MessagePack);

            if ($ChkStatuses) {
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
 * Class SMSSrvAPI to be inherited by real SMS services APIs implementations
 * located in 'content\sms_service_APIs' to provide re-usability and common interaction interface for SendDogAdvanced class
 */
abstract class SMSSrvAPI {
    /**
     * SendDogAdvanced instance plceholder
     *
     * @var null
     */
    protected $SendDog = null;

    /**
     * Placeholder for settings record data from sms_services table
     *
     * @var array
     */
    protected $APISettingsRaw = array();

    /**
     * SMS service ID in sms_services table
     *
     * @var int
     */
    protected $SrvID = 0;

    /**
     * SMS service login
     *
     * @var string
     */
    protected $SrvLogin = '';

    /**
     * SMS service password
     *
     * @var string
     */
    protected $SrvPassword = '';

    /**
     * SMS service base URL/IP
     *
     * @var string
     */
    protected $SrvGatewayAddr = '';

    /**
     * SMS service alpha name
     *
     * @var string
     */
    protected $SrvAlphaName = '';

    /**
     * SMS service API key
     *
     * @var string
     */
    protected $SrvAPIKey = '';

    /**
     * Assigned as a default SMS service
     *
     * @var bool
     */
    protected $IsDefaultService = false;

    /**
     * Messages to be processed by push method
     *
     * @var array
     */
    protected $SMSMsgPack = array();


    public function __construct($SMSSrvID, $SMSPack = array()) {
        $this->SrvID = $SMSSrvID;
        $this->SendDog = new SendDogAdvanced();
        $this->APISettingsRaw = $this->SendDog->getSMSServicesConfigData(" WHERE `id` = " . $SMSSrvID);
        $this->getSettings();
        $this->SMSMsgPack = $SMSPack;
    }

    /**
     * Fills up the config placeholders for a particular SMS service
     */
    protected function getSettings() {
        if ( !empty($this->APISettingsRaw) ) {
            $this->SrvLogin         = $this->APISettingsRaw[0]['login'];
            $this->SrvPassword      = $this->APISettingsRaw[0]['passwd'];
            $this->SrvGatewayAddr   = $this->APISettingsRaw[0]['url_addr'];
            $this->SrvAlphaName     = $this->APISettingsRaw[0]['alpha_name'];
            $this->SrvAPIKey        = $this->APISettingsRaw[0]['api_key'];
            $this->IsDefaultService = $this->APISettingsRaw[0]['default_service'];
        }
    }

    /**
     * Returns styled error message about not supported features
     */
    protected function showErrorFeatureIsNotSupported() {
        $errormes = $this->SendDog->getUbillingMsgHelperInstance()->getStyledMessage( __('This SMS service does not support this function'),
                                                                                      'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
        die(wf_modalAutoForm(__('Error'), $errormes, $_POST['ModalWID'], '', true));
    }

    public abstract function getBalance();
    public abstract function getSMSQueue();
    public abstract function pushMessages();
    public abstract function checkMessagesStatuses();
}
?>
