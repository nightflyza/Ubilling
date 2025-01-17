<?php

/**
 * SMS/Telegram/Email messages sending implementation
 */
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
     * Placeholder for UbillingConfig object
     *
     * @var object
     */
    protected $ubConfig = null;

    /**
     * Contains array of SMS services that will be loaded as serviceId=>serviceParams
     *
     * @var array
     */
    protected $servicesEnabled = array();

    /**
     * Active services objects array
     *
     * @var array
     */
    protected $activeServices = array();

    /**
     * contains default interface module URL
     */
    const URL_ME = '?module=senddog';

    /**
     * Path to running dog flag
     */
    const PID_PATH = 'exports/senddogrunning.pid';

    /**
     * Classic-SendDog SMS services libs path
     */
    const SERVICES_LIB_PATH = 'api/vendor/senddog_classic_services/';

    public function __construct() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;

        $this->loadAltCfg();
        $this->setOptions();
        $this->loadBaseConfig();
        $this->preloadSmsServicesLibs();
        $this->initSmsQueue();
        $this->initMessages();
        $this->loadServicesConfigs();
        $this->loadTelegramConfig();
    }

    /**
     * Loads system alter config into protected property for further usage
     * 
     * @return void
     */
    protected function loadAltCfg() {
        $this->altCfg = $this->ubConfig->getAlter();
    }

    /**
     * Loads required options and 
     * 
     * @return void
     */
    protected function setOptions() {
        $servicesConfigPath = CONFIG_PATH . 'senddog.d/';
        $allServicesConf = rcms_scandir($servicesConfigPath, '*.ini');
        if (!empty($allServicesConf)) {
            foreach ($allServicesConf as $io => $eachConfig) {
                $serviceConfig = rcms_parse_ini_file($servicesConfigPath . $eachConfig, true);
                if (!empty($serviceConfig)) {
                    $this->servicesEnabled += $serviceConfig;
                }
            }
        }
        if (isset($this->altCfg['SENDDOG_SMS_SERVICES_ENABLED'])) {
            if (!empty($this->altCfg['SENDDOG_SMS_SERVICES_ENABLED'])) {
                $servicesEnabledOnly = explode(',', $this->altCfg['SENDDOG_SMS_SERVICES_ENABLED']);
                if (!empty($servicesEnabledOnly)) {
                    $servicesEnabledOnly = array_flip($servicesEnabledOnly);
                    foreach ($this->servicesEnabled as $serviceId => $serviceParams) {
                        if (!isset($servicesEnabledOnly[$serviceId])) {
                            unset($this->servicesEnabled[$serviceId]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Preloads all enabled SMS services libs and creates separate instances of each
     * 
     * @return void
     */
    protected function preloadSmsServicesLibs() {
        if (!empty($this->servicesEnabled)) {
            foreach ($this->servicesEnabled as $serviceId => $serviceParams) {
                require_once (self::SERVICES_LIB_PATH . $serviceId . '.php');
                $serviceClassName = $serviceId;
                $this->activeServices[$serviceId] = new $serviceClassName($this->settings);
            }
        }
    }

    /**
     * Loads enabled SMS services data
     * 
     * @return void
     */
    protected function loadServicesConfigs() {
        if (!empty($this->servicesEnabled)) {
            foreach ($this->servicesEnabled as $serviceId => $serviceParams) {
                if (isset($serviceParams['CONFIG'])) {
                    if (!empty($serviceParams['CONFIG'])) {
                        $loadConfigMethodName = $serviceParams['CONFIG'];
                        $this->activeServices[$serviceId]->$loadConfigMethodName();
                    }
                }
            }
        }
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
     * Renders current telegram bot contacts
     * 
     * @return string
     */
    public function renderTelegramContacts() {
        $result = '';
        $allEmployeeChatIds = array();
        $telegram = new UbillingTelegram();
        $telegram->setToken($this->settings['TELEGRAM_BOTTOKEN']);
        $rawContacts = $telegram->getBotContacts();
        $allEmployeeData = ts_GetAllEmployeeData();

        if (!empty($allEmployeeData)) {
            foreach ($allEmployeeData as $io => $each) {
                if (!empty($each['telegram'])) {
                    if (!empty($each['admlogin'])) {
                        $empNameLabel = $each['name'] . ' (' . $each['admlogin'] . ')';
                    } else {
                        $empNameLabel = $each['name'];
                    }
                    $allEmployeeChatIds[$each['telegram']] = $empNameLabel;
                }
            }
        }

        $result .= wf_BackLink(self::URL_ME, '', true);

        if (!empty($rawContacts)) {
            $cells = wf_TableCell('');
            $cells .= wf_TableCell(__('Chat ID'));
            $cells .= wf_TableCell(__('Type'));
            $cells .= wf_TableCell(__('Worker'));
            $cells .= wf_TableCell(__('Username'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Message'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($rawContacts as $io => $each) {
                $cells = wf_TableCell($this->newContact($each['lastmessage']));
                $cells .= wf_TableCell($each['chatid']);
                $chatType=__($each['type']);
                $cells .= wf_TableCell($chatType);
                $employeeName = (isset($allEmployeeChatIds[$each['chatid']])) ? $allEmployeeChatIds[$each['chatid']] : '';
                $cells .= wf_TableCell($employeeName);
                $userNameLabel = (!empty($each['name'])) ? wf_Link('https://t.me/'.$each['name'], $each['name']) : '';
                $cells .= wf_TableCell($userNameLabel);
                $cells .= wf_TableCell($each['first_name'] . ' ' . $each['last_name']);
                $cells .= wf_TableCell($each['lastmessage']);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }
        return ($result);
    }

    /**
     * Returns new contact marker
     * 
     * @param string $message
     * 
     * @return string
     */
    protected function newContact($message) {
        $result = '';
        $markers = array('go', 'start', 'хуй', 'huy'); //default new contact markers array

        if (!empty($markers)) {
            foreach ($markers as $io => $eachMarker) {
                if (ispos($message, $eachMarker)) {
                    $result = wf_img_sized('skins/icon_telegram_small.png', '', '10');
                }
            }
        }
        return($result);
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
        $inputs = '';

        if (!empty($this->servicesEnabled)) {
            foreach ($this->servicesEnabled as $serviceId => $serviceParams) {
                if (isset($serviceParams['INTERFACE'])) {
                    if (!empty($serviceParams['INTERFACE'])) {
                        $configInterfaceMethodName = $serviceParams['INTERFACE'];
                        $inputs .= $this->activeServices[$serviceId]->$configInterfaceMethodName();
                    }
                }
            }
        }
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

        if (!empty($this->servicesEnabled)) {
            foreach ($this->servicesEnabled as $serviceId => $serviceParams) {
                if (isset($serviceParams['SAVE'])) {
                    if (!empty($serviceParams['SAVE'])) {
                        $saveSettingsMethodName = $serviceParams['SAVE'];
                        $this->activeServices[$serviceId]->$saveSettingsMethodName();
                    }
                }
            }
        }

//telegram bot token configuration
        if (ubRouting::post('edittelegrambottoken') != $this->settings['TELEGRAM_BOTTOKEN']) {
            zb_StorageSet('SENDDOG_TELEGRAM_BOTTOKEN', ubRouting::post('edittelegrambottoken'));
            log_register('SENDDOG CONFIG SET TELEGRAMBOTTOKEN');
        }


//default sms service
        if (ubRouting::post('defaultsmsservice') != $this->settings['SMS_SERVICE']) {
            zb_StorageSet('SENDDOG_SMS_SERVICE', ubRouting::post('defaultsmsservice'));
            log_register('SENDDOG CONFIG SET SMSSERVICE `' . ubRouting::post('defaultsmsservice') . '`');
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
            $smsServiceId = $this->settings['SMS_SERVICE'];
            if (isset($this->servicesEnabled[$smsServiceId])) {
                $messagePushMethodName = $this->servicesEnabled[$smsServiceId]['PUSH'];
                $this->activeServices[$smsServiceId]->$messagePushMethodName();
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
        $defaultServiceId = $this->settings['SMS_SERVICE'];
        if (isset($this->servicesEnabled[$defaultServiceId])) {
            if (isset($this->servicesEnabled[$defaultServiceId]['HISTORY'])) {
                if (!empty($this->servicesEnabled[$defaultServiceId]['HISTORY'])) {
                    $historyMethodName = $this->servicesEnabled[$defaultServiceId]['HISTORY'];
                    $this->activeServices[$defaultServiceId]->$historyMethodName();
                }
            }
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
    public static function cutInternationalsFromPhoneNum($PhoneNumber) {
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

    /**
     * Renders service balance or another misc info by its serviceId
     * 
     * @param  string $serviceId
     * 
     * @return void
     */
    public function renderBalanceInfo($serviceId) {
        if (isset($this->servicesEnabled[$serviceId])) {
            if (isset($this->servicesEnabled[$serviceId]['BALANCE'])) {
                if (!empty($this->servicesEnabled[$serviceId]['BALANCE'])) {
                    $balanceMethodName = $this->servicesEnabled[$serviceId]['BALANCE'];
                    $serviceName = (isset($this->servicesEnabled[$serviceId]['NAME'])) ? $this->servicesEnabled[$serviceId]['NAME'] : $serviceId;
                    show_window(__($serviceName) . ' ' . __('Balance'), $this->activeServices[$serviceId]->$balanceMethodName());
                }
            }
        }
    }

}
