<?php

/**
 * Switches auth data abstraction
 */
class SwitchAuth {

    /**
     * Current instance switch ID
     *
     * @var int
     */
    protected $switchId = 0;

    /**
     * Auth data database abstraction layer
     *
     * @var object
     */
    protected $authDb = '';

    /**
     * Contains all devices auth data as swId=>authData
     *
     * @var array
     */
    protected $allAuthData = array();

    /**
     * System messages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some other predefinded stuff
     */
    const TABLE_AUTH = 'switchauth';
    const URL_ME = '?module=switchauth';
    const URL_SWPROFILE = '?module=switches&edit=';
    const ROUTE_DEVID = 'switchid';
    const PROUTE_DEVID = 'swithcauthdeviceid';
    const PROUTE_LOGIN = 'switchauthlogin';
    const PROUTE_PASSWORD = 'switchauthpassword';
    const PROUTE_ENABLE = 'switchauthenablepass';

    public function __construct($switchId = 0) {
        $this->initMessages();
        $this->initDb();
        if (!empty($switchId)) {
            $this->setSwitchId($switchId);
        }
        $this->loadAuthData();
    }

    /**
     * Current instance switchId setter
     * 
     * @param int/void $switchId
     * 
     * @return void
     */
    protected function setSwitchId($switchId = '') {
        $switchId = ubRouting::filters($switchId, 'int');
        if (!empty($switchId)) {
            $this->switchId = $switchId;
        }
    }


    /**
     * Initializes the messages property with an instance of UbillingMessageHelper.
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }


    /**
     * Initializes the database abstraction layer
     *
     * @return void
     */
    protected function initDb() {
        $this->authDb = new NyanORM(self::TABLE_AUTH);
    }

    /**
     * Loads available auth data into allAuthData property
     *
     * @return void
     */
    protected function loadAuthData() {
        if (!empty($this->switchId)) {
            $this->authDb->where('swid', '=', $this->switchId);
        }
        $this->allAuthData = $this->authDb->getAll('swid');
    }

    /**
     * Returns auth data for some specified device
     *
     * @param int $switchId
     * 
     * @return array|void
     */
    public function getAuthData($switchId) {
        $result = array();
        if (isset($this->allAuthData[$switchId])) {
            $result = $this->allAuthData[$switchId];
        }
        return ($result);
    }

    /**
     * Returns current device auth edit form
     * 
     * @return string
     */
    public function renderEditForm() {
        $result = '';
        if (!empty($this->switchId)) {
            $curAuthData = $this->getAuthData($this->switchId);
            $inputs = wf_HiddenInput(self::PROUTE_DEVID, $this->switchId);
            $inputs .= wf_TextInput(self::PROUTE_LOGIN, __('Login'), @$curAuthData['login'], true, 20, 'login');
            $inputs .= wf_PasswordInput(self::PROUTE_PASSWORD, __('Password'), @$curAuthData['password'], true, 20);
            $inputs .= wf_PasswordInput(self::PROUTE_ENABLE, __('Enable password'), @$curAuthData['enable'], true, 20);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Sets the authentication data for a device.
     *
     * @param int $switchId The ID of the switch.
     * @param string $login The login username.
     * @param string $password The login password.
     * @param string $enable The enable status.
     *
     * @return void
     */
    public function setAuthData($switchId, $login, $password, $enable) {
        $switchId = ubRouting::filters($switchId, 'int');
        $login = ubRouting::filters($login, 'mres');
        $password = ubRouting::filters($password, 'mres');
        $enable = ubRouting::filters($enable, 'mres');

        if ($switchId) {
            $curAuthData = $this->getAuthData($switchId);

            $this->authDb->data('swid', $switchId);
            $this->authDb->data('login', $login);
            $this->authDb->data('password', $password);
            $this->authDb->data('enable', $enable);
            //new record?
            if (empty($curAuthData)) {
                $this->authDb->create();
                log_register('SWITCHAUTH CREATED [' . $switchId . ']');
            } else {
                //updating existing record
                $recordId = $curAuthData['id'];
                $this->authDb->where('id', '=', $recordId);
                $this->authDb->save();
                log_register('SWITCHAUTH CHANGED [' . $switchId . ']');
            }
        }
    }

    /**
     * Flushes some device auth data record from database
     * 
     * @return void
     */
    public function flushAuthData($switchId) {
        $switchId = ubRouting::filters($switchId, 'int');
        if ($switchId) {
            $curAuthData = $this->getAuthData($switchId);
            if (!empty($curAuthData)) {
                $recordId = $curAuthData['id'];
                $this->authDb->where('id', '=', $recordId);
                $this->authDb->delete();
                log_register('SWITCHAUTH FLUSH [' . $switchId . ']');
            }
        }
    }
}
