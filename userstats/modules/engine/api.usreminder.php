<?php

class USReminder {
    /**
     * Placeholder for Ubilling UserStats config instance
     *
     * @var null
     */
    protected $usConfig = null;

    /**
     * Contains currently logged-in user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * Placeholder for currency "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgCurrency = 'UAH';

    /**
     * Placeholder for REMINDER_ENABLED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderEnabled = 0;

    /**
     * Placeholder for REMINDER_PRICE "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderPrice = 0;

    /**
     * Placeholder for REMINDER_TAGID "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderTagID = 0;

    /**
     * Placeholder for REMINDER_NUMBER_LENGTH "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderNumberLen = 0;

    /**
     * Placeholder for REMINDER_DAYS_THRESHOLD "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderDaysTreshold = 0;

    /**
     * Placeholder for REMINDER_PREFIX "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderPrefix = '';

    /**
     * Placeholder for REMINDER_FEE "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderFee = 0;

    /**
     * Placeholder for REMINDER_CASHTYPEID "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderCashTypeID = 0;

    /**
     * Placeholder for REMINDER_TURNOFF "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderTurnOFFAble = 0;

    /**
     * Placeholder for REMINDER_CHANGE_NUMBER "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderNumberChangeAllowed = 0;

    /**
     * Placeholder for REMINDER_EMAIL_ENABLED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderEmailEnabled = 0;

    /**
     * Placeholder for REMINDER_EMAIL_TAGID "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderEmailTagID = 0;

    /**
     * Placeholder for REMINDER_EMAIL_CHANGE_ALLOWED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderEmailChangeAllowed = 0;

    /**
     * Placeholder for REMINDER_USE_EXTMOBILES "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderUseExtMobiles = 0;

    /**
     * Placeholder for REMINDER_CONSIDER_CREDIT "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderConsiderCredit = 0;

    /**
     * Placeholder for REMINDER_DAYS_THRESHOLD_CREDIT "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderDaysTresholdCredit = 0;

    /**
     * Placeholder for REMINDER_CONSIDER_CAP "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderConsiderCAP = 0;

    /**
     * Placeholder for REMINDER_DAYS_THRESHOLD_CAP "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderDaysTresholdCAP = 0;

    /**
     * Placeholder for REMINDER_CONSIDER_FROZEN "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderConsiderFrozen = 0;

    /**
     * Placeholder for REMINDER_DAYS_THRESHOLD_FROZEN "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderDaysTresholdFrozen = 0;

    /**
     * Placeholder for REMINDER_PRIVATBANK_INVOICE_PUSH "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderPBIEnabled = 0;

    /**
     * Placeholder for REMINDER_PBI_ONLY_TAG_ID "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderPBIOnlyTagID = 0;

    /**
     * Placeholder for REMINDER_PBI_AND_SMS_TAG_ID "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgReminderPBISMSTagID = 0;

    /**
     * Determines if the Reminder is enabled for current user
     *
     * @var bool
     */
    public $userReminderEnabled = false;

    /**
     * Determines if the PB invoices and SMS will be sent to the user
     *
     * @var bool
     */
    public $userPBISMSEnabled = false;

    /**
     * Determines if only the PB invoices will be sent to the user
     *
     * @var bool
     */
    public $userPBIOnlyEnabled = false;

    /**
     * Determines if fee charging is excluded for users with PBIOnly tag
     *
     * @var bool
     */
    public $pbionlyFeeExcluded = false;


    public function __construct($userLogin = '') {
        $this->loadConfig();
        $this->loadOptions();
    }


    /**
     * Get the UserStatsConfig instance
     *
     * @return void
     */
    protected function loadConfig() {
        $this->usConfig = new UserStatsConfig();
    }

    /**
     * Essential options loader
     *
     * @return void
     */
    protected function loadOptions() {
        $this->uscfgCurrency                    = $this->usConfig->getUstasParam('currency', 'UAH');
        $this->uscfgReminderEnabled             = $this->usConfig->getUstasParam('REMINDER_ENABLED', 0);
        $this->uscfgReminderPrice               = $this->usConfig->getUstasParam('REMINDER_PRICE', 0);
        $this->uscfgReminderTagID               = $this->usConfig->getUstasParam('REMINDER_TAGID', 0);
        $this->uscfgReminderNumberLen           = $this->usConfig->getUstasParam('REMINDER_NUMBER_LENGTH', 0);
        $this->uscfgReminderDaysTreshold        = $this->usConfig->getUstasParam('REMINDER_DAYS_THRESHOLD', 0);
        $this->uscfgReminderPrefix              = $this->usConfig->getUstasParam('REMINDER_PREFIX', '');
        $this->uscfgReminderFee                 = $this->usConfig->getUstasParam('REMINDER_FEE', 0);
        $this->uscfgReminderCashTypeID          = $this->usConfig->getUstasParam('REMINDER_CASHTYPEID', 0);
        $this->uscfgReminderTurnOFFAble         = $this->usConfig->getUstasParam('REMINDER_TURNOFF', 0);
        $this->uscfgReminderNumberChangeAllowed = $this->usConfig->getUstasParam('REMINDER_CHANGE_NUMBER', 0);
        $this->uscfgReminderEmailEnabled        = $this->usConfig->getUstasParam('REMINDER_EMAIL_ENABLED', 0);
        $this->uscfgReminderEmailTagID          = $this->usConfig->getUstasParam('REMINDER_EMAIL_TAGID', 0);
        $this->uscfgReminderEmailChangeAllowed  = $this->usConfig->getUstasParam('REMINDER_EMAIL_CHANGE_ALLOWED', 0);
        $this->uscfgReminderUseExtMobiles       = $this->usConfig->getUstasParam('REMINDER_USE_EXTMOBILES', 0);
        $this->uscfgReminderConsiderCredit      = $this->usConfig->getUstasParam('REMINDER_CONSIDER_CREDIT', 0);
        $this->uscfgReminderDaysTresholdCredit  = $this->usConfig->getUstasParam('REMINDER_DAYS_THRESHOLD_CREDIT', 0);
        $this->uscfgReminderConsiderCAP         = $this->usConfig->getUstasParam('REMINDER_CONSIDER_CAP', 0);
        $this->uscfgReminderDaysTresholdCAP     = $this->usConfig->getUstasParam('REMINDER_DAYS_THRESHOLD_CAP', 0);
        $this->uscfgReminderConsiderFrozen      = $this->usConfig->getUstasParam('REMINDER_CONSIDER_FROZEN', 0);
        $this->uscfgReminderDaysTresholdFrozen  = $this->usConfig->getUstasParam('REMINDER_DAYS_THRESHOLD_FROZEN', 0);
        $this->uscfgReminderPBIEnabled          = $this->usConfig->getUstasParam('REMINDER_PRIVATBANK_INVOICE_PUSH', 0);
        $this->uscfgReminderPBIOnlyTagID        = $this->usConfig->getUstasParam('REMINDER_PBI_ONLY_TAG_ID', 0);
        $this->uscfgReminderPBISMSTagID         = $this->usConfig->getUstasParam('REMINDER_PBI_AND_SMS_TAG_ID', 0);
    }

    /**
     * Checks if Reminder is enabled for a certain user and returns the result
     *
     * @param $userLogin
     * @param $reminderTagID
     *
     * @return bool|type
     */
    public static function checkReminderEnabled($userLogin, $reminderTagID) {
        $result = stg_check_user_tag($userLogin, $reminderTagID);
        return ($result);
    }

    /**
     * Checks if PBI only sending is enabled for a certain user and returns the result
     *
     * @param $userLogin
     * @param $pbionlyTagID
     *
     * @return bool|type
     */
    public static function checkPBIOnlyEnabled($userLogin, $pbionlyTagID) {
        $result = stg_check_user_tag($userLogin, $pbionlyTagID);
        return ($result);
    }

    /**
     * Checks if PBI and SMS sending is enabled for a certain user and returns the result
     *
     * @param $userLogin
     * @param $pbionlyTagID
     *
     * @return bool|type
     */
    public static function checkPBISMSEnabled($userLogin, $pbionlyTagID) {
        $result = stg_check_user_tag($userLogin, $pbionlyTagID);
        return ($result);
    }

    /**
     * Checks if PBI only sending is free of charge and returns the result
     *
     * @param $pbionlyTagID
     *
     * @return bool|type
     */
    public static function getPBIOnlyExcludedStatus($reminderTagID, $pbionlyTagID) {
        $result = false;
        $vservicesDB = new NyanORM('vservices');
        $vservicesDB->where('tagid', '=', $reminderTagID);
        $vservicesDB->selectable('exclude_tags');
        $excludedTags = $vservicesDB->getAll();
        $excludedTags = empty($excludedTags[0]['exclude_tags']) ? array() : explode(',', $excludedTags[0]['exclude_tags']);
        $result = in_array($pbionlyTagID, $excludedTags);
        return ($result);
    }

    /**
     * Change user mobile
     * @param type $login string
     * @param type $mobile int
     */
    protected function changeUserMobile($userLogin, $mobile) {
        $login    = ubRouting::filters($userLogin, 'vf');
        $phonesDB = new NyanORM('phones');
        $phonesDB->data('mobile', $mobile);
        $phonesDB->where('login', '=', $login);
        $phonesDB->save();
        log_register('CHANGE UserMobile (' . $login . ') `' . $mobile . '`');
    }

    /**
     * Change user email
     * @param type $login string
     * @param type $mobile int
     */
    protected function changeUserEmail($userLogin, $email) {
        $login    = ubRouting::filters($userLogin, 'vf');
        $phonesDB = new NyanORM('emails');
        $phonesDB->data('email', $email);
        $phonesDB->where('login', '=', $login);
        $phonesDB->save();
        log_register('CHANGE UserEmail (' . $login . ') `' . $email . '`');
    }

    /**
     * Adding user tag form
     * @return main form for tag add
     */
    protected function zbs_ShowEnableReminderForm() {
        $inputs = la_tag('center');
        $inputs.= la_HiddenInput('setremind', 'true');
        $inputs.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
        $inputs.= la_delimiter();
        $inputs.= la_Submit(__('Remind me please'));
        $inputs.= la_tag('center', true);
        $form = la_Form("", 'POST', $inputs, '');

        return($form);
    }

    /**
     * Deleting user tag form
     * @return type for for tag delete
     */
    protected function zbs_ShowDisableReminderForm() {
        $inputs = la_tag('center');
        $inputs.= la_HiddenInput('deleteremind', 'true');
        $inputs.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
        $inputs.= la_delimiter();
        $inputs.= la_Submit(__('Don\'t remind me'));
        $inputs.= la_tag('center', true);
        $form = la_Form("", 'POST', $inputs, '');

        return($form);
    }

    /**
     *
     * @return type form for changin mobile
     */
    protected function zbs_ShowChangeMobileForm() {
        global $us_config;
        $inputs = la_tag('center');
        $inputs.= la_HiddenInput('changemobile', 'true');
        $inputs.= $this->uscfgReminderPrefix . ' ';
        $inputs.= la_TextInput('mobile');
        $inputs.= la_delimiter();
        $inputs.= la_Submit(__('Change mobile'));
        $inputs.= la_tag('center', true);
        $form = la_Form("", 'POST', $inputs, '');

        return($form);
    }

    /**
     *
     * @return type form for changin mobile
     */
    protected function zbs_ShowChangeEmailForm($user_login) {
        $email  = zbs_UserGetEmail($user_login);
        $email  = empty($email) ? '' : $email;

        $inputs = la_tag('center');
        $inputs.= la_HiddenInput('changemail', 'true');
        $inputs.= la_TextInput('email', '', $email);
        $inputs.= la_delimiter();
        $inputs.= la_Submit(__('Change E-mail'));
        $inputs.= la_tag('center', true);
        $form = la_Form("", 'POST', $inputs, '');

        return($form);
    }

    /**
     * Checks the essential Reminder options set and returns error message or empty string if all is ok
     *
     * @return string
     */
    public function checkEssentialOptions() {
        $result = '';
        $uscfg = $this->usConfig->getUstas();

        if (empty($this->uscfgReminderEnabled)) {
            $result = 'REMINDER_DISABLED';
        } elseif (!isset($uscfg['REMINDER_PRICE'])) {
            $result = 'REMINDER: PRICE not set';
        } elseif (!isset($uscfg['REMINDER_TAGID'])) {
            $result = 'REMINDER: TAGID not set';
        } elseif (!isset($uscfg['REMINDER_NUMBER_LENGTH'])) {
            $result = 'REMINDER: NUMBER_LENGTH not set';
        } elseif (!isset($uscfg['REMINDER_DAYS_THRESHOLD'])) {
            $result = 'REMINDER: DAYS_TRESHOLD not set';
        } elseif (!isset($uscfg['REMINDER_PREFIX'])) {
            $result = 'REMINDER: PREFIX not set';
        } elseif (!isset($uscfg['REMINDER_FEE'])) {
            $result = 'REMINDER: FEE not set';
        } elseif (!isset($uscfg['REMINDER_CASHTYPEID'])) {
            $result = 'REMINDER: CASHTYPEID not set';
        } elseif (!isset($uscfg['REMINDER_TURNOFF'])) {
            $result = 'REMINDER: TURNOFF not set';
        }

        return ($result);
    }

    public function showMainWindow() {

    }
}