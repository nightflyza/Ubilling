<?php

/**
 * SendDog classic SMS services prototype class
 */
class SendDogProto {

    /**
     * Contains SMS service settings
     *
     * @var array
     */
    protected $settings = array();

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
    protected $ubConfig = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    const URL_ME = '?module=senddog';

    public function __construct($settingsBase) {
        $this->initUbConfig();
        $this->initMessages();
        $this->initSmsQueue();
        $this->setBaseSettings($settingsBase);
    }

    /**
     * Clones ubillingConfig object for future usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function initUbConfig() {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
    }

    /**
     * Basic settings setter
     * 
     * @param array $settingsBase
     * 
     * @return void
     */
    protected function setBaseSettings($settingsBase) {
        $this->settings = $settingsBase;
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

}
