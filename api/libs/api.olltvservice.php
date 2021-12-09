<?php

class OllTVService {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Olltv low-level API layer 
     *
     * @var object
     */
    protected $api = '';

    /**
     * OllTv subscribers database abstraction layer
     *
     * @var object
     */
    protected $subscribersDb = '';

    /**
     * Contains all available users data as login=>userData
     *
     * @var array
     */
    protected $allUsersData = array();

    /**
     * Contains pseudo-mail domain to generate subs emails
     *
     * @var string
     */
    protected $mailDomain = '';

    /**
     * Country code to skip from mobile numbers
     *
     * @var string
     */
    protected $countryCode = '+38';

    //some predefined routes, urls, paths etc
    const LOG_PATH = 'exports/olltv.log';
    const TABLE_SUBSCRIBERS = 'olltv_users';
    const TABLE_TARIFFS = 'olltv_tariffs';

    /**
     * Creates new OLLTV service instance
     * 
     * @return object
     */
    public function __construct() {
        $this->initMessages();
        $this->loadAlter();
        $this->setOptions();
        $this->initApi();
        $this->loadUserData();
        $this->initSubscribers();
    }

    /**
     * Loads some required config data
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     *  Sets some properties
     * 
     * @return void
     */
    protected function setOptions() {
        $this->mailDomain = $this->altCfg['OLLTV_DOMAIN'];
    }

    /**
     * Inits messages helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits Olltv low-level API layer
     * 
     * @return void
     */
    protected function initApi() {
        if (!empty($this->altCfg['OLLTV_LOGIN']) AND ! empty($this->altCfg['OLLTV_PASSWORD'])) {
            $this->api = new OllTv($this->altCfg['OLLTV_LOGIN'], $this->altCfg['OLLTV_PASSWORD'], false, self::LOG_PATH, $this->altCfg['OLLTV_DEBUG']);
        } else {
            throw new Exception('EX_EMPTY_OLLTVOPTIONS');
        }
    }

    /**
     * Inits subscribers database abstraction layer
     * 
     * @return void
     */
    protected function initSubscribers() {
        $this->subscribersDb = new NyanORM(self::TABLE_SUBSCRIBERS);
    }

    /**
     * Loads all available users data from database
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUsersData = zb_UserGetAllDataCache();
    }

    /**
     * Transforms stdObject into array
     * 
     * @param mixed $data
     * 
     * @return array
     */
    protected function makeArray($data) {
        $result = array();
        if (!empty($data)) {
            $result = json_decode(json_encode($data), true);
        }
        return($result);
    }

    /**
     * Returns existing users array
     * 
     * @return array
     */
    public function getUserList() {
        $result = $this->makeArray($this->api->getUserList());
        return($result);
    }

    /**
     * Generates user pseudo-mail or returns real mail if it exists in database
     * 
     * @param string $login
     * 
     * @return string
     */
    protected function generateMail($login) {
        if (!empty($this->mailDomain)) {
            $result = $login . '@' . $this->mailDomain;
        }

        if (isset($this->allUsersData[$login])) {
            if (!empty($this->allUsersData[$login]['email'])) {
                $result = $this->allUsersData[$login]['email'];
            }
        }
        return($result);
    }

    /**
     * Prepares mobile number for registration
     * 
     * @param string $mobile
     * 
     * @return string
     */
    protected function prepareMobile($mobile) {
        $result = '';
        if (!empty($mobile)) {
            $result = str_replace($this->countryCode, '', $mobile);
        }
        return($result);
    }

    /**
     * Creates new subscriber depends on system user data
     * 
     * @param string $login Existing user login
     * 
     * @return int/bool on error
     */
    public function createSubscriber($login) {
        $result = false;
        if (isset($this->allUsersData[$login])) {
            $userData = $this->allUsersData[$login];
            $mail = $this->generateMail($login);
            if (!empty($mail)) {
                $mobile = $this->prepareMobile($userData['mobile']);
                if (!empty($mobile)) {
                    $addParams = array('phone' => $mobile);
                    $creationResult = $this->api->addUser($mail, $login, $addParams);
                    if ($creationResult) {
                        $result = $creationResult;
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns existing olltv subscriber data
     * 
     * @param string $login
     * 
     * @return array
     */
    public function getSubscriberData($login) {
        $result = $this->makeArray($this->api->getUserInfo(array('account' => $login)));
        return($result);
    }

}
