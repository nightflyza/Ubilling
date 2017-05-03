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
        //time shift settings
        $timezone = '2';
        $tz_offset = (2 - $timezone) * 3600;
        $date = date("Y-m-d H:i:s", time() + $tz_offset);

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
            }
        }
        return ($smsCount);
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

}

?>