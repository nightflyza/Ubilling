<?php

class Reminder {

    /**
     * Contains all of available user logins with reminder tag
     *
     * @var array
     */
    protected $AllLogin = array();

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $AltCfg = array();

    /**
     * Contains all of available user phones data
     *
     * @var array
     */
    protected $AllPhones = array();

    /**
     * Contains all frozen users
     * 
     * @var array
     */
    protected $AllPassive = array();

    /**
     * Placeholder for UbillingSMS object
     *
     * @var object
     */
    protected $sms = '';

    /**
     * Placeholder for FundsFlow object
     *
     * @var object
     */
    protected $money = '';

    /**
     * Contains data for native templating messages
     *
     * @var array
     */
    protected $AllTemplates = array();

    const FLAGPREFIX = 'exports/REMINDER.';

    /**
     * it's a magic
     */
    public function __construct() {
        $this->loadAlter();
        $this->LoadAllTemplates();
        $this->LoadRemindLogin();
        $this->LoadPhones();
        $this->LoadPassive();
        $this->sms = new UbillingSMS();
        $this->money = new FundsFlow();
        $this->money->runDataLoders();
    }

    /**
     * load all logins whith cash >=0 and with set tagid to $alllogin
     * 
     * @return void
     */
    protected function LoadRemindLogin() {
        if (isset($this->AltCfg['REMINDER_TAGID'])) {
            $tagid = vf($this->AltCfg['REMINDER_TAGID'], 3);
            $query = "SELECT `login` FROM `tags` WHERE `tagid`='" . $tagid . "'";
            $tmp = simple_queryall($query);
            if (!empty($tmp)) {
                $this->AllLogin = $tmp;
            }
        }
    }

    /**
     * load all available phones + mobile
     * 
     * @return void
     */
    protected function LoadPhones() {
        $this->AllPhones = zb_UserGetAllPhoneData();
    }

    /**
     * load alter.ini config     
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->AltCfg = $ubillingConfig->getAlter();
    }

    /**
     * Load all users templates
     * 
     * @return void
     */
    protected function LoadAllTemplates() {
        $this->AllTemplates = zb_TemplateGetAllUserData();
    }

    /**
     * Loads all of passive aka frozen users from database
     *
     * @return void
     */
    protected function LoadPassive() {
        $query = "SELECT `login` FROM `users` WHERE `Passive`=1";
        $data = simple_queryall($query);
        if (!empty($data)) {
            foreach ($data as $each) {
                $this->AllPassive[] = $each['login'];
            }
        }
    }

    /**
     * Check is user frozen or not?
     * 
     * @param string $login
     * 
     * @return bool
     */
    protected function FilterPassive($login) {
        if (!empty($this->AllPassive)) {
            foreach ($this->AllPassive as $each) {
                if ($each == $login) {
                    return(true);
                } else {
                    return(false);
                }
            }
        }
    }

    /**
     * Make queue for sms send
     * 
     * @return void
     */
    public function RemindUser() {
        $LiveDays = $this->AltCfg['REMINDER_DAYS_THRESHOLD'];
        $LiveTime = $LiveDays * 24 * 60 * 60;
        $CacheTime = time() - $LiveTime;

        foreach ($this->AllLogin as $userLoginData) {
            $eachLogin = $userLoginData['login'];
            if (!$this->FilterPassive($eachLogin)) {
                if ($this->money->getOnlineLeftCountFast($eachLogin) <= $LiveDays AND $this->money->getOnlineLeftCountFast($eachLogin) >= 0) {
                    if (!file_exists(self::FLAGPREFIX . $eachLogin)) {
                        $number = $this->AllPhones[$eachLogin]['mobile'];
                        if (!empty($number)) {
                            $number = trim($number);
                            $number = str_replace($this->AltCfg['REMINDER_PREFIX'], '', $number);
                            $number = vf($number, 3);
                            $number = $this->AltCfg['REMINDER_PREFIX'] . $number;
                            $template = $this->AltCfg['REMINDER_TEMPLATE'];
                            if (!empty($template)) {
                                $message = zb_TemplateReplace($eachLogin, $template, $this->AllTemplates);
                                if (!empty($message)) {
                                    $this->sms->sendSMS($number, $message, false);
                                    file_put_contents(self::FLAGPREFIX . $eachLogin, '');
                                }
                            }
                        }
                    }
                } elseif ($this->money->getOnlineLeftCountFast($eachLogin) == -2) {
                    log_register(__('SMS will not sent. Tariff is free.' . ' ' . 'Login' . ': ' . $eachLogin));
                } else {
                    if (file_exists(self::FLAGPREFIX . $eachLogin)) {
                        if (filemtime(self::FLAGPREFIX . $eachLogin) > $CacheTime) {
                            unlink(self::FLAGPREFIX . $eachLogin);
                        }
                    }
                }
            }
        }
    }

}
