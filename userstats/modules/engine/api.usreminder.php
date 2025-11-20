<?php

class USReminder {
    /**
     * Placeholder for Ubilling UserStats config instance
     *
     * @var null
     */
    protected $usConfig = null;

    /**
     * Placeholder for currency "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgCurrency = 'UAH';

    /**
     * Placeholder for TICKETING_ENABLED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgTicketingEnabled = 0;

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
    protected $uscfgReminderInstantFeeON = 0;

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
    protected $uscfgReminderTurnONOFFAble = 0;

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
     * Contains currently logged-in user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * Contains currently logged-in user mobile
     *
     * @var string
     */
    protected $userMobile = '';

    /**
     * Contains currently logged-in user ext mobile
     *
     * @var array
     */
    protected $userMobileExt = array();

    /**
     * Contains currently logged-in user E-mail
     *
     * @var string
     */
    protected $userEmail = '';

    /**
     * Determines if the Reminder is enabled for current user
     *
     * @var bool
     */
    protected $userReminderON = false;

    /**
     * Determines if the PB invoices and SMS will be sent to the user
     *
     * @var bool
     */
    protected $userPBISMSON = false;

    /**
     * Determines if only the PB invoices will be sent to the user
     *
     * @var bool
     */
    protected $userPBIOnlyON = false;

    /**
     * Determines if E-mail notifications will be sent to the user
     *
     * @var bool
     */
    protected $userEmailReminderON = false;

    /**
     * Determines if fee charging is excluded for users with PBIOnly tag
     *
     * @var bool
     */
    protected $pbionlyFeeExcluded = false;


    const URL_TICKETING = '?module=ticketing';


    public function __construct($userLogin = '') {
        if (empty($userLogin)) {
            $userIP          = zbs_UserDetectIp('debug');
            $this->userLogin = zbs_UserGetLoginByIp($userIP);
        } else {
            $this->userLogin = $userLogin;
        }

        $this->loadConfig();
        $this->loadOptions();
        $this->showMainWindow();
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
        $this->uscfgTicketingEnabled            = $this->usConfig->getUstasParam('TICKETING_ENABLED', 0);
        $this->uscfgReminderEnabled             = $this->usConfig->getUstasParam('REMINDER_ENABLED', 0);
        $this->uscfgReminderPrice               = $this->usConfig->getUstasParam('REMINDER_PRICE', 0);
        $this->uscfgReminderTagID               = $this->usConfig->getUstasParam('REMINDER_TAGID', 0);
        $this->uscfgReminderNumberLen           = $this->usConfig->getUstasParam('REMINDER_NUMBER_LENGTH', 0);
        $this->uscfgReminderDaysTreshold        = $this->usConfig->getUstasParam('REMINDER_DAYS_THRESHOLD', 0);
        $this->uscfgReminderPrefix              = $this->usConfig->getUstasParam('REMINDER_PREFIX', '');
        $this->uscfgReminderInstantFeeON                 = $this->usConfig->getUstasParam('REMINDER_FEE', 0);
        $this->uscfgReminderCashTypeID          = $this->usConfig->getUstasParam('REMINDER_CASHTYPEID', 0);
        $this->uscfgReminderTurnONOFFAble         = $this->usConfig->getUstasParam('REMINDER_TURNOFF', 0);
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

        if ($this->uscfgReminderPBIEnabled and !empty($this->uscfgReminderTagID) and !empty($this->uscfgReminderPBIOnlyTagID)) {
            $this->pbionlyFeeExcluded = self::getPBIOnlyExcludedStatus($this->uscfgReminderTagID, $this->uscfgReminderPBIOnlyTagID);
        }
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

    /**
     * Checks user's mobile number correctness
     *
     * @param $mobile
     *
     * @return bool
     */
    protected function checkUserMobileIsCorrect($mobile) {
        $checkNumber = trim($mobile);
        $checkNumber = str_replace($this->uscfgReminderPrefix, '', $checkNumber);
        $checkNumber = vf($checkNumber, 3);
        $checkNumber = $this->uscfgReminderPrefix . $checkNumber;
        $prefixSize = strlen($this->uscfgReminderPrefix);
        $checkSize = $prefixSize + $this->uscfgReminderNumberLen;

        return (strlen($checkNumber) == $checkSize);
    }

    /**
     * Checks if Reminder is enabled for a certain user and returns the result
     *
     * @param $userLogin
     * @param $reminderTagID
     *
     * @return bool|type
     */
    public static function checkUserReminderEnabled($userLogin, $reminderTagID) {
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
    public static function checkUserPBIOnlyEnabled($userLogin, $pbionlyTagID) {
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
    public static function checkUserPBISMSEnabled($userLogin, $pbionlyTagID) {
        $result = stg_check_user_tag($userLogin, $pbionlyTagID);
        return ($result);
    }

    /**
     * Checks if E-mail sending is enabled for a certain user and returns the result
     *
     * @param $userLogin
     * @param $emailTagID
     *
     * @return mixed
     */
    public static function checkUserEmailEnabled($userLogin, $emailTagID) {
        $result = stg_check_user_tag($userLogin, $emailTagID);
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
     * Retrieves essential user data and tags assignment
     *
     * @param $userLogin
     *
     * @return void
     */
    protected function getUserReminderConfig($userLogin = '') {
        $userLogin                 = (empty($userLogin)) ? $this->userLogin : $userLogin;
        $this->userReminderON      = self::checkUserReminderEnabled($userLogin, $this->uscfgReminderTagID);
        $this->userPBIOnlyON       = self::checkUserPBIOnlyEnabled($userLogin, $this->uscfgReminderPBIOnlyTagID);
        $this->userPBISMSON        = self::checkUserPBISMSEnabled($userLogin, $this->uscfgReminderPBISMSTagID);
        $this->userEmailReminderON = self::checkUserEmailEnabled($userLogin, $this->uscfgReminderEmailTagID);
        $this->userMobile          = zbs_UserGetMobile($userLogin);
        $this->userMobileExt       = zbs_UserGetMobileExt($userLogin);
        $this->userEmail           = zbs_UserGetEmail($userLogin);
    }

    /**
     * Change user mobile
     *
     * @param type $userLogin string
     * @param type $mobile int
     *
     * @return void
     */
    protected function changeUserMobile($userLogin, $mobile) {
        $login    = ubRouting::filters($userLogin, 'login');
        $phonesDB = new NyanORM('phones');
        $phonesDB->data('mobile', $mobile);
        $phonesDB->where('login', '=', $login);
        $phonesDB->save();
    }

    /**
     * Change user email
     *
     * @param type $userLogin string
     * @param type $email int
     *
     * @return void
     */
    protected function changeUserEmail($userLogin, $email) {
        $login    = ubRouting::filters($userLogin, 'login');
        $phonesDB = new NyanORM('emails');
        $phonesDB->data('email', $email);
        $phonesDB->where('login', '=', $login);
        $phonesDB->save();
    }

    /**
     * Returns mobiles displaying and editing form
     *
     * @param $mobile
     * @param $mobileExt
     *
     * @return string
     */
    protected function renderMobilesForm($mobile = '', $mobileExt = array()) {
        if (!empty($mobile)) {
            $mobileText = __("Your current main cell phone number is") . ": " . la_nbsp(4) . la_tag('b') . $mobile . la_tag('b', true);
        } else {
            $mobileText = __("Your have empty main cell phone number") . "." . " ";
            $mobileText.= ($this->uscfgReminderNumberChangeAllowed) ? __("Please enter and save it using the form below") . "." : '';
        }

        if ($this->uscfgReminderUseExtMobiles and !empty($mobileExt)) {
            $mobileText.= la_delimiter();
            $mobileText.= __('You provider has also enabled notifications to additional cell phone numbers specified in your profile. You may find those below (however - you can\'t modify them directly from here, only via request to your provider support)') . ':';
            $mobileText.= la_delimiter(0);
            $extMobiles = '';

            foreach ($mobileExt as $extMob) {
                $extMobiles.= la_nbsp(4) . $extMob . la_delimiter(0);
            }

            $mobileText.= $extMobiles;
        }

        $mobileText.= ($this->uscfgReminderNumberChangeAllowed) ? $this->renderChangeMobileForm() : '';

        return($mobileText);
    }

    /**
     * Returns E-mail displaying and editing form
     *
     * @param $mobile
     * @param $mobileExt
     *
     * @return string
     */
    protected function renderEmailForm($email = '') {
        $emailText = (empty($email))
                      ? __("You E-mail is empty") . "." . " "
                      : __("Your current E-mail is") . ": " . la_nbsp(4) . la_tag('b') . $email . la_tag('b', true);

        if ($this->uscfgReminderEmailChangeAllowed) {
            $emailText.= (empty($email)) ? __("You may add it using the form below") . "." : '';
            $emailText.= $this->renderChangeEmailForm();
        }

        return($emailText);
    }

    /**
     * Returns main mobile editing from
     *
     * @return string
     */
    protected function renderChangeMobileForm() {
        $inputs = la_HiddenInput('changemobile', 'true');
        $inputs.= la_tag('span', false, '', 'style="text-align: right;"');
        $inputs.= $this->uscfgReminderPrefix . ' ';
        $inputs.= la_tag('span', true);
        $inputs.= la_TextInput('mobile', '', '', '', '', 'mobile');
        $inputs.= la_Submit(__('Change main cell phone number'), 'full-width-occupy');
        $form   = la_Form("", 'POST', $inputs, 'form-grid-2cols', '', '', 'style="justify-content: center;"');

        return ($form);
    }

    /**
     * Returns E-mail editing from
     *
     * @return string
     */
    protected function renderChangeEmailForm() {
        $inputs = la_HiddenInput('changemail', 'true');
        $inputs.= la_TextInput('email', '', '', '', '', 'email', 'full-width-occupy');
        $inputs.= la_Submit(__('Change E-mail'), 'full-width-occupy');
        $form = la_Form("", 'POST', $inputs, 'form-grid-2cols', '', '', 'style="justify-content: center;"');

        return ($form);
    }

    /**
     * Returns Reminder type editing form (used only if PrivatBank invoices are ON)
     *
     * @return string
     */
    protected function renderChangeReminderTypeForm() {
        $pbiOptions = la_RadioInput('pbiopts', 'SMS only', 'pbismsonly', false, (!$this->userPBISMSON and !$this->userPBIOnlyON));
        $pbiOptions.= la_RadioInput('pbiopts', 'PrivatBank invoices only', 'pbiinvonly', false, $this->userPBIOnlyON);
        $pbiOptions.= la_RadioInput('pbiopts', 'PrivatBank invoices and SMS', 'pbiinvsms', false, $this->userPBISMSON);
        $pbiOptions.= la_Submit(__('Change reminder type'), 'full-width-occupy');
        $pbiForm    = la_Form("", 'POST', $pbiOptions, 'form-grid-2cols', '', '', 'style="justify-content: center;"');

        return ($pbiForm);
    }

    /**
     * Returns Reminder state editing form
     * @return string
     */
    protected function renderONOFFReminderForm($emailReminder = false) {
        $inputs     = '';

        if ($emailReminder) {
            if ($this->userEmailReminderON) {
                $toggleSwitch = 'deleteremindemail';
                $toggleText   = __('Don\'t remind me on E-mail');
            } else {
                $toggleSwitch = 'setremindemail';
                $toggleText   = __('Remind me on E-mail please');
            }
        } else {
            if ($this->userReminderON) {
                $toggleSwitch = 'deleteremind';
                $toggleText   = __('Don\'t remind me');
            } else {
                $toggleSwitch = 'setremind';
                $toggleText   = __('Remind me please');
            }
        }

        $inputs.= la_HiddenInput($toggleSwitch, 'true');
        $inputs.= la_CheckInput('agree', __('I am sure that I am an adult and have read everything that is written above'), false, false);
        $inputs.= la_Submit($toggleText, 'full-width-occupy');
        $form   = la_Form("", 'POST', $inputs, 'form-grid-2cols', '', '', 'style="justify-content: center; grid-template-columns: auto;"');

        return ($form);
    }

    /**
     * Returns Reminder configuration editing from
     *
     * @return string
     */
    protected function renderReminderConfig() {
        $formContents = '';

        if ($this->uscfgReminderPBIEnabled) {
            if ($this->userPBISMSON) {
                $reminderType = 'PrivatBank invoices and SMS';
            } elseif ($this->userPBIOnlyON) {
                $reminderType = 'PrivatBank invoices only';
            } else {
                $reminderType = 'SMS only';
            }
        } else {
            $reminderType = 'SMS';
        }

        if ($this->userReminderON) {
            $formContents.= __("Your payments reminder is currently enabled via") . ' ' . la_tag('b') . __($reminderType) . la_tag('b', true) . ".";

            if ($this->uscfgReminderPBIEnabled) {
                $formContents.= la_delimiter(0) . la_tag('sup') . la_tag('b') . '*' . la_tag('b', true) . la_tag('sup', true);
                $formContents.= __('Pay attention that PrivatBank invoices are designed to remind about Internet service balance only') . '. ';
            }

            if ($this->uscfgReminderEnabled != 2) {
                $formContents.= la_delimiter() . __('You will be reminded within') . ' ' . $this->uscfgReminderDaysTreshold . ' ' .
                                __('days') . ' ' . __('until the expiration of the service') . '. ';
            }

            if ($this->uscfgReminderConsiderCredit) {
                $daysCredit = (empty($this->uscfgReminderDaysTresholdCredit)) ? $this->uscfgReminderDaysTreshold : $this->uscfgReminderDaysTresholdCredit;
                $formContents.= la_delimiter(0) . __('You will be reminded within') . ' ' . $daysCredit . ' ' . __('days') . ' ' . __('before the credit expire date') . '. ';
            }

            if ($this->uscfgReminderConsiderCAP) {
                $daysCAP = (empty($this->uscfgReminderDaysTresholdCAP)) ? $this->uscfgReminderDaysTreshold : $this->uscfgReminderDaysTresholdCAP;
                $formContents.= la_delimiter(0) . __('You will be reminded within') . ' ' . $daysCAP . ' ' . __('days') . ' ' . __('before inactiveness penalty will be applied') . '. ';
            }

            if ($this->uscfgReminderConsiderFrozen) {
                $daysFrozen = (empty($this->uscfgReminderDaysTresholdFrozen)) ? $this->uscfgReminderDaysTreshold : $this->uscfgReminderDaysTresholdFrozen;
                $formContents.= la_delimiter(0) . __('You will be reminded within') . ' ' . $daysFrozen . ' ' . __('days') . ' ' . __('before available freeze days run out') . '. ';
            }

            if ($this->uscfgReminderTurnONOFFAble) {
                $formContents.= la_tag('hr');
                $formContents.= __("Disable payments reminder to your cell phones") . '?';
                $formContents.= $this->renderONOFFReminderForm();

                if ($this->uscfgReminderPBIEnabled) {
                    $formContents.= la_tag('hr');
                    $formContents.= __('You may change your cell phones reminder type using the form below. But keep in mind this action will not affect the reminder ON/OFF state itself.');
                    $formContents.= $this->renderChangeReminderTypeForm();
                }
            }
        } else {
            $formContents.= __('Reminder service to your cell phone numbers is disabled for you') . '.';

            // check user's cell phone availability and show appropriate form/message
            if (empty($this->userMobile)) {
                $formContents.= la_delimiter(0);
                $formContents.= __("You can't enable payments cell phone numbers reminder - your main cell phone number is empty") . ".";
            } elseif ($this->uscfgReminderTurnONOFFAble) {
                if ($this->checkUserMobileIsCorrect($this->userMobile)) {
                    $formContents.= la_delimiter(0);
                    $formContents.= __("You can enable payments reminder") . '. ';
                    $formContents.= __("It costs") . " " . $this->uscfgReminderPrice . ' ' . $this->uscfgCurrency . " " .
                                     __("per month") . "." . la_delimiter(0);

                    if ($this->uscfgReminderPBIEnabled and $this->pbionlyFeeExcluded) {
                        $formContents.= la_tag('sup') . la_tag('b') . '*' . la_tag('b', true) . la_tag('sup', true);
                        $formContents.= __('But if you will enable PrivatBank invoices only - reminder service will be free of charge, although activation cost will still be charged') . '.';
                    }

                    if ($this->uscfgReminderInstantFeeON) {
                        $formContents.= la_delimiter();
                        $formContents.= la_tag('b') . __("Attention") . la_tag('b', true) . "," . " " . __("activation cost is") .
                                         " " . $this->uscfgReminderPrice . " " . $this->uscfgCurrency . " " .
                                         __("and will be charged at once") . ".";
                    }

                    $formContents.= $this->renderONOFFReminderForm();

                    /*if ($this->uscfgReminderPBIEnabled) {
                        $formContents.= la_tag('hr');
                        $formContents.= __('You may change your cell phone numbers reminder type using the form below.
                                            But keep in mind this action will not affect the reminder ON/OFF state itself.');
                        $formContents .= $this->renderChangeReminderTypeForm();
                    }*/
                } else {
                    $formContents.= la_delimiter(0);
                    $formContents.= la_tag('b') . __('Wrong mobile format') . la_tag('b', true);
                }
            }
        }

        // check E-mail reminder status and user's e-mail availability and show appropriate form/message
        if ($this->uscfgReminderEmailEnabled) {
            if ($this->userEmailReminderON) {
                $formContents.= la_tag('hr');
                $formContents.= __("Payments reminder is currently enabled for your E-mail") . '.';

                if ($this->uscfgReminderTurnONOFFAble) {
                    $formContents.= la_delimiter();
                    $formContents.= __("Disable payments reminder to your E-mail") . '?';
                    $formContents.= $this->renderONOFFReminderForm(true);
                }
            } else {
                $formContents.= la_tag('hr');
                $formContents.= __('Reminder service to your E-mail is disabled for you') . '.';

                if (empty($this->userEmail)) {
                    $formContents.= la_delimiter(0);
                    $formContents.= __("You can't enable payments E-mail reminder - your E-mail is empty") . '.';
                } elseif ($this->uscfgReminderTurnONOFFAble) {
                    $formContents.= la_tag('hr');
                    $formContents.= __("Enable payments reminder to my E-mail") . '.';
                    $formContents.= $this->renderONOFFReminderForm(true);
                }
            }
        }

        if (!$this->uscfgReminderTurnONOFFAble) {
            $ticketingStr = ($this->uscfgTicketingEnabled
                            ? '( ' . la_Link(self::URL_TICKETING, __('for example - using "Ticketing service"')) . ')'
                            : '');
            $formContents.= la_tag('hr');
            $formContents.= __('You\'re not allowed to change the state of the reminder service by yourself') . '.';
            $formContents.= la_delimiter(0);
            $formContents.= __('If you want to enable/disable reminder service - please contact provider support') . '. ' . $ticketingStr;
        }

        return ($formContents);
    }

    /**
     * Router method to process the changes
     *
     * @return void
     * @throws Exception
     */
    public function router() {
        $userLogin          = $this->userLogin;
        $policyNotAccepted  = false;
        $logMessage         = '';

        // user cell phone number change
        if (ubRouting::checkPost(array('changemobile', 'mobile'))) {
            $mobile = ubRouting::filters(ubRouting::post('mobile'), 'int');
            $mobile = preg_replace('/^(\+38|38)/Ui', '', $mobile);

            if (!empty($mobile) and (strlen($mobile) == 10 or strlen($mobile) == 12)) {
                $this->changeUserMobile($userLogin, $this->uscfgReminderPrefix . $mobile);
                $logMessage = 'US_REMINDER: user (' . $userLogin . ') changed his cell phone number to: ' . $this->uscfgReminderPrefix . $mobile;
            } else {
                $logMessage = 'US_REMINDER: user (' . $userLogin . ') provided invalid or empty cell phone number: ' . strip_tags(ubRouting::filters(ubRouting::post('mobile'), 'vf'));
            }
        }

        // user email change
        if (ubRouting::checkPost(array('changemail', 'email'))) {
            $email = ubRouting::filters(ubRouting::post('email'), 'fi', FILTER_VALIDATE_EMAIL);
            
            if (!empty($email)) {
                $this->changeUserEmail($userLogin, $email);
                $logMessage = 'US_REMINDER: user (' . $userLogin . ') changed his E-mail to: ' . $email;
            } else {
                $logMessage = 'US_REMINDER: user (' . $userLogin . ') provided invalid or empty E-mail: ' . strip_tags(ubRouting::filters(ubRouting::post('email'), 'vf'));
            }
        }

        // user change service state
        if (ubRouting::checkPost(array('setremind',
                                       'deleteremind',
                                       'setremindemail',
                                       'deleteremindemail'
                                 ), true, true)) {

            if (ubRouting::checkPost('agree')) {
                if ($this->uscfgReminderTurnONOFFAble) {
                    if (ubRouting::checkPost('setremind')) {
                        stg_add_user_tag($userLogin, $this->uscfgReminderTagID);

                        if ($this->uscfgReminderInstantFeeON) {
                            billing_addcash($userLogin, '-' . $this->uscfgReminderPrice);
                            zbs_PaymentLog($userLogin, '-' . $this->uscfgReminderPrice, $this->uscfgReminderCashTypeID, "REMINDER");
                        }

                        $logMessage = 'US_REMINDER: user (' . $userLogin . ') has enabled Reminder service';
                    } elseif (ubRouting::checkPost('deleteremind')) {
                        stg_del_user_tagid($userLogin, $this->uscfgReminderTagID);
                        $logMessage = 'US_REMINDER: user (' . $userLogin . ') has disabled Reminder service';
                    } elseif (ubRouting::checkPost('setremindemail')) {
                        stg_add_user_tag($userLogin, $this->uscfgReminderEmailTagID);
                        $logMessage = 'US_REMINDER: user (' . $userLogin . ') has enabled E-mail Reminder service';
                    } elseif (ubRouting::checkPost('deleteremindemail')) {
                        stg_del_user_tagid($userLogin, $this->uscfgReminderEmailTagID);
                        $logMessage = 'US_REMINDER: user (' . $userLogin . ') has disabled E-mail Reminder service';
                    }
                }
            } else {
                $policyNotAccepted = true;
            }
        }

        // user change service type
        if (ubRouting::checkPost('pbiopts')) {
            $pbiopts = ubRouting::post('pbiopts');

            if ($this->uscfgReminderTurnONOFFAble) {
                if ($pbiopts == 'pbismsonly') {
                    stg_del_user_tagid($userLogin, $this->uscfgReminderPBIOnlyTagID);
                    stg_del_user_tagid($userLogin, $this->uscfgReminderPBISMSTagID);
                    $logMessage = 'US_REMINDER: user (' . $userLogin . ') has enabled "PBI SMS only" Reminder service type';
                } elseif ($pbiopts == 'pbiinvonly') {
                    stg_add_user_tag($userLogin, $this->uscfgReminderPBIOnlyTagID);
                    stg_del_user_tagid($userLogin, $this->uscfgReminderPBISMSTagID);
                    $logMessage = 'US_REMINDER: user (' . $userLogin . ') has enabled "PBI only" Reminder service type';
                } elseif ($pbiopts == 'pbiinvsms') {
                    stg_del_user_tagid($userLogin, $this->uscfgReminderPBIOnlyTagID);
                    stg_add_user_tag($userLogin, $this->uscfgReminderPBISMSTagID);
                    $logMessage = 'US_REMINDER: user (' . $userLogin . ') has enabled "PBI and SMS" Reminder service type';
                }
            }
        }

        if (!empty($logMessage)) { log_register($logMessage); }

        if ($policyNotAccepted) {
            show_window(__('Sorry'), la_tag('span', false, '', 'style="color: orangered; font-weight: 600;"') . __('You must accept our policy') . la_tag('span', true));
        } elseif (!$this->uscfgReminderTurnONOFFAble) {
            show_window(__('Sorry'), __('You\'re not allowed to change reminder service state'));
        } else {
            rcms_redirect("?module=reminder");
        }
    }

    /**
     * Renders main module frontend
     *
     * @return void
     */
    public function showMainWindow() {
        $checkResult = $this->checkEssentialOptions();

        if (empty($checkResult)) {
            $this->getUserReminderConfig();

            show_window(__('Your cell phones'), $this->renderMobilesForm($this->userMobile, $this->userMobileExt));

            if ($this->uscfgReminderEmailEnabled) {
                show_window(__('Your E-mail'), $this->renderEmailForm($this->userEmail));
            }

            show_window(__("Reminder config"), $this->renderReminderConfig());
        } elseif ($checkResult == 'REMINDER_DISABLED') {
            show_window(__('Sorry'), __('This module is disabled'));
        } else {
            die($checkResult);
        }
    }
}