<?php

class Reminder {

    protected $AllLogin = array();
    protected $AltCfg = array();
    protected $AllPhones = array();

    /*
     * it's a magic
     */

    public function __construct() {
        $this->loadAlter();
        $this->LoadRemindLogin();
        $this->LoadPhones();
        $this->sms = new UbillingSMS();
        $this->money = new FundsFlow();
    }

    /**
     * load all logins whith cash >=0 and with set tagid to $alllogin     
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

    /*
     * load all available phones + mobile
     */

    protected function LoadPhones() {
        $this->AllPhones = zb_UserGetAllPhoneData();
    }

    /*
     * load alter.ini config     
     */

    protected function loadAlter() {
        global $ubillingConfig;
        $this->AltCfg = $ubillingConfig->getAlter();
    }

    public function RemindUser() {
        $LiveDays = $this->AltCfg['REMINDER_DAYS_THRESHOLD'];
        $LiveTime = $LiveDays * 24 * 60 * 60;
        $CacheTime = time() - $LiveTime;

        foreach ($this->AllLogin as $EachLogin) {
            if ($this->money->getOnlineLeftCount($EachLogin, true) <= $LiveDays) {
                if (!file_exists('exports/REMINDER.' . $EachLogin)) {
                    $number = $this->AllPhones[$EachLogin]['mobile'];
                    $message = 'Shanovnij abonent, bud` laska popovnit` raxunok dlya podal`shogo vykorystannya poslugy internet';
                    $this->sms->sendSMS($number, $message, false);
                    file_put_contents('exports/REMINDER.' . $EachLogin, '');
                }
            } else {
                if (filemtime('exports/REMINDER.' . $EachLogin) > $CacheTime) {
                    unlink('exports/REMINDER.' . $EachLogin);
                }
            }
        }
    }

}
