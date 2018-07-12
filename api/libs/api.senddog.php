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
        $smsServiceFlag = $this->settings['SMS_SERVICE'] === 'smspilot';
        $inputs.= wf_RadioInput('defaultsmsservice', __('Use SMSPILOT as default SMS service'), 'smspilot', true, $smsServiceFlag);
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
            $i = 0;
            $SMSHistoryEnabled = $this->altCfg['SMS_HISTORY_ON'];
            $SMSHistoryTabFreshIDs = array();
            $PreSendStatus = __('Perparing for delivery');

            if ($SMSHistoryEnabled) {
                $Telepatia = new Telepathy(false);
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

            foreach ($ChkMessages as $io => $EachMSg) {
                $SMSPAcketID = $EachMSg['srvmsgpack_id'];

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
                case 'smspilot':
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
            case 'smspilot':
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
        // appended "+38" or "+7" to the beginning of it - we need to remove that prefix
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

?>
