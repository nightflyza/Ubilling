<?php

/**
 * ProstoTV abstraction layer
 * 
 * https://docs.api.prosto.tv/
 */
class PTV {

    /**
     * Contains sytem alter.config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains login preloaded from config
     *
     * @var string
     */
    protected $login = '';

    /**
     * Contains password preloaded from config
     *
     * @var string
     */
    protected $password = '';

    /**
     * ProstoTV low-level API abstraction layer
     *
     * @var object
     */
    protected $api = '';

    /**
     * Contains all available system users data as login=>userdata
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains all subscribers data as login=>subscriberData
     *
     * @var array
     */
    protected $allSubscribers = array();

    /**
     * Subscribers database abstraction layer
     *
     * @var object
     */
    protected $subscribersDb = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Predefined routes, options etc.
     */
    const OPTION_LOGIN = 'PTV_LOGIN';
    const OPTION_PASSWORD = 'PTV_PASSWORD';
    const TABLE_SUBSCRIBERS = 'ptv_subscribers';
    const UNDEF = 'undefined_';
    const URL_ME = '?module=prostotv';
    const URL_USERPROFILE = '?module=userprofile&username=';
    const ROUTE_SUBLIST = 'subscribers';
    const ROUTE_SUBAJ = 'ajaxlist';
    const ROUTE_SUBVIEW = 'showsubscriber';

    /**
     * Creates new PTV instance
     */
    public function __construct() {
        $this->initMessages();
        $this->loadConfig();
        $this->setOptions();
        $this->initApi();
        $this->loadUserData();
        $this->initSubscribersDb();
        $this->loadSubscribers();
    }

    /**
     * Preloads required configs into protected props
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets required properties via config options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->login = $this->altCfg[self::OPTION_LOGIN];
        $this->password = $this->altCfg[self::OPTION_PASSWORD];
    }

    /**
     * Inits system message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits low-level API for further usage
     * 
     * @return void
     */
    protected function initApi() {
        require_once ('api/libs/api.prostotv.php');
        $this->api = new UTG\ProstoTV($this->login, $this->password);
    }

    /**
     * Inits subscribers database abstraction layer
     * 
     * @return void
     */
    protected function initSubscribersDb() {
        $this->subscribersDb = new NyanORM(self::TABLE_SUBSCRIBERS);
    }

    /**
     * Loads available subscribers from database
     * 
     * @return void
     */
    protected function loadSubscribers() {
        $this->allSubscribers = $this->subscribersDb->getAll('login');
    }

    /**
     * Loads available system users data
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllData();
    }

    /**
     * Registers a new user
     * 
     * @param string $userLogin
     * 
     * @return array/bool on error
     */
    public function userRegister($userLogin) {
        $result = false;
        $userLogin = ubRouting::filters($userLogin, 'mres');
        //user exists
        if (isset($this->allUserData[$userLogin])) {
            //not registered yet
            if (!isset($this->allSubscribers[$userLogin])) {
                $userData = $this->allUserData[$userLogin];
                $newPassword = $userData['Password'];
                $userRealName = $userData['realname'];
                $userRealNameParts = explode(' ', $userRealName);
                if (sizeof($userRealNameParts == 3)) {
                    $firstName = $userRealNameParts[1];
                    $middleName = $userRealNameParts[2];
                    $lastName = $userRealNameParts[0];
                } else {
                    $firstName = self::UNDEF . $userLogin;
                    $middleName = self::UNDEF . $userLogin;
                    $lastName = self::UNDEF . $userLogin;
                }

                $requestParams = array(
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'note' => $userLogin,
                    'password' => $newPassword
                );

                $result = $this->api->post('/objects', $requestParams);
                //log subscriber
                $newId = $result['id'];
                $this->subscribersDb->data('date', curdatetime());
                $this->subscribersDb->data('subscriberid', $newId);
                $this->subscribersDb->data('login', $userLogin);
                $this->subscribersDb->create();

                log_register('PTV SUB REGISTER (' . $userLogin . ') AS [' . $newId . ']');
            } else {
                log_register('PTV SUB REGISTER (' . $userLogin . ') DUPLICATE FAIL');
            }
        } else {
            log_register('PTV SUB REGISTER (' . $userLogin . ') NOTEXIST FAIL');
        }

        return($result);
    }

    /**
     * Returns subscriber remote data
     * 
     * @param string $userLogin
     * 
     * @return array/bool
     */
    public function getUserData($userLogin) {
        $result = false;
        if (isset($this->allSubscribers[$userLogin])) {
            $subscriberId = $this->allSubscribers[$userLogin]['subscriberid'];
            $reply = $this->api->get('/objects/' . $subscriberId);
            if ($reply) {
                $result = $reply;
            }
        }
        return($result);
    }

    /**
     * Checks is some subscriberId associated with registered user?
     * 
     * @param int $subscriberId
     * 
     * @return bool
     */
    public function isValidSubscriber($subscriberId) {
        $subscriberId = ubRouting::filters($subscriberId, 'int');
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if ($each['subscriberid'] == $subscriberId) {
                    $result = true;
                }
            }
        }
        return($result);
    }

    /**
     * Returns array of all existing user playlists
     * 
     * @param int $subscriberId
     * 
     * @return array/bool
     */
    public function getPlaylistsAll($subscriberId) {
        $result = false;
        if ($this->isValidSubscriber($subscriberId)) {
            $reply = $this->api->get('objects/' . $subscriberId . '/playlists');
            if (isset($reply['playlists'])) {
                $result = $reply['playlists'];
            }
        }
        return($result);
    }

    /**
     * Creates new playlist for some subscriber
     * 
     * @param int $subscriberId
     * 
     * @return array/bool
     */
    public function createPlayList($subscriberId) {
        $result = false;
        if ($this->isValidSubscriber($subscriberId)) {
            $result = $this->api->post('objects/' . $subscriberId . '/playlists');
        }
        return($result);
    }

    /**
     * Deletes some subscriber`s playlist
     * 
     * @param int $subscriberId
     * @param string $playListId
     * 
     * @return void
     */
    public function deletePlaylist($subscriberId, $playListId) {
        if ($this->isValidSubscriber($subscriberId)) {
            $this->api->delete('/objects/' . $subscriberId . '/playlists/' . $playListId);
        }
    }

    /**
     * Renders available subscribers JSON list
     * 
     * @return void
     */
    public function renderSubsribersAjReply() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $userLogin => $eachSub) {
                if (isset($this->allUserData[$userLogin])) {
                    $data[] = $eachSub['subscriberid'];
                    $data[] = $eachSub['date'];
                    $userAddress = @$this->allUserData[$userLogin]['fulladress'];
                    $userRealName = @$this->allUserData[$userLogin]['realname'];
                    $profileLink = wf_Link(self::URL_USERPROFILE . $userLogin, web_profile_icon());
                    $subViewUrl = self::URL_ME . '&' . self::ROUTE_SUBVIEW . '=' . $userLogin;
                    $actLinks = wf_Link($subViewUrl, web_edit_icon());
                    $data[] = $profileLink . ' ' . $userAddress;
                    $data[] = $userRealName;
                    $data[] = $actLinks;
                    $json->addRow($data);
                    unset($data);
                }
            }
        }
        $json->getJson();
    }

    /**
     * Renders existing subscribers list container
     * 
     * @return string
     */
    public function renderSubscribersList() {
        $result = '';
        $columns = array('ID', 'Date', 'Address', 'Real Name', 'Actions');
        $opts = '"order": [[ 1, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&' . self::ROUTE_SUBAJ . '=true', false, __('Subscriptions'), 50, $opts);
        return($result);
    }

    /**
     * Renders basic subscriber profile
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function renderSubscriber($userLogin) {
        $result = '';
        if (isset($this->allUserData[$userLogin])) {
            if (isset($this->allSubscribers[$userLogin])) {
                $subscriberId = $this->allSubscribers[$userLogin]['subscriberid'];
                $subData = $this->getUserData($userLogin);
                if ($subData != false) {
                    $result .= wf_tag('pre') . print_r($subData, true) . wf_tag('pre', true);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Empty reply received'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_SUBSCRIBER_NOT_EXISTS', 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('User not exists'), 'error');
        }
        return($result);
    }

}
