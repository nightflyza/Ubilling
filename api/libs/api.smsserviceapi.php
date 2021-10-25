<?php

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

    /**
     * Placeholder for UbillingConfig object
     *
     * @var null
     */
    protected $ubConfig = null;

    public function __construct($smsServiceId, $smsPack = array()) {
        global $ubillingConfig;
        $this->ubConfig = $ubillingConfig;
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
        $errormes = $this->instanceSendDog->getUBMsgHelperInstance()->getStyledMessage(__('This SMS service does not support this function'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
        die(wf_modalAutoForm(__('Error'), $errormes, $_POST['modalWindowId'], '', true));
    }

    public abstract function getBalance();

    public abstract function getSMSQueue();

    public abstract function pushMessages();

    public abstract function checkMessagesStatuses();
}
