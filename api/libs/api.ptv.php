<?php

/**
 * ProstoTV Ubilling abstraction layer
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
    const NEW_WINDOW = 'TARGET="_BLANK"';
    const URL_ME = '?module=prostotv';
    const URL_USERPROFILE = '?module=userprofile&username=';
    const ROUTE_SUBLIST = 'subscribers';
    const ROUTE_SUBAJ = 'ajaxlist';
    const ROUTE_SUBVIEW = 'showsubscriber';
    const ROUTE_TARIFFS = 'tariffs';
    const ROUTE_PLCREATE = 'createplaylist';
    const ROUTE_PLDEL = 'deleteplaylist';
    const ROUTE_SUBID = 'subscriberid';
    const ROUTE_DEVCREATE = 'createdevice';
    const PROUTE_SUBREG = 'registersubscriber';

    /**
     * Through the darkness of future past
     * The magician longs to see.
     * One chanse out between two worlds
     * Fire walk with me
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
     * Returns existing subscriber user login by its ID
     * 
     * @param int $subscriberId
     * 
     * @return string/bool
     */
    public function getSubscriberLogin($subscriberId) {
        $result = false;
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if ($each['subscriberid'] == $subscriberId) {
                    $result = $each['login'];
                }
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
            $userLogin = $this->getSubscriberLogin($subscriberId);

            $result = $this->api->post('objects/' . $subscriberId . '/playlists');
            log_register('PTV PLAYLIST CREATE SUB (' . $userLogin . ') AS [' . $subscriberId . ']');
        }
        return($result);
    }

    /**
     * Creates some device for subscriber
     * 
     * @param int $subscriberId
     * 
     * @return array/bool
     */
    public function createDevice($subscriberId) {
        $result = false;
        if ($this->isValidSubscriber($subscriberId)) {
            $userLogin = $this->getSubscriberLogin($subscriberId);

            $result = $this->api->post('objects/' . $subscriberId . '/devices');
            log_register('PTV DEVICE CREATE SUB (' . $userLogin . ') AS [' . $subscriberId . ']');
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
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $this->api->delete('/objects/' . $subscriberId . '/playlists/' . $playListId);
            log_register('PTV PLAYLIST DELETE SUB (' . $userLogin . ') AS [' . $subscriberId . ']');
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
     * Renders subscriber services
     * 
     * @param array $subData
     * 
     * @return string
     */
    protected function renderServices($subData) {
        $result = '';
        if (!empty($subData['services'])) {
            foreach ($subData['services'] as $io => $each) {
                $result .= $each;
            }
        } else {
            $result .= __('No tariff');
        }
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
                $userData = $this->allUserData[$userLogin];
                $subProfileUrl = self::URL_ME . '&' . self::ROUTE_SUBVIEW . '=' . $userLogin;
                if ($subData != false) {
                    $cells = wf_TableCell(__('Address'), '', 'row2');
                    $cells .= wf_TableCell(wf_Link(self::URL_USERPROFILE . $userLogin, web_profile_icon() . ' ' . $userData['fulladress']));
                    $rows = wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('ID'), '30%', 'row2');
                    $cells .= wf_TableCell($subData['id']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Cash'), '', 'row2');
                    $cells .= wf_TableCell($userData['Cash']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Credit'), '', 'row2');
                    $cells .= wf_TableCell($userData['Credit']);
                    $rows .= wf_TableRow($cells, 'row3');
//                    $cells = wf_TableCell(__('Password'), '', 'row2');
//                    $cells .= wf_TableCell($userData['Password']);
//                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('IP'), '', 'row2');
                    $cells .= wf_TableCell($userData['ip']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Status'), '', 'row2');
                    $cells .= wf_TableCell(__($subData['status']));
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Profile') . ' ' . __('EBS'), '', 'row2');
                    $cells .= wf_TableCell(wf_Link($subData['ebs_url'], wf_img('skins/arrow_right_green.png') . ' ' . __('Show'), false, '', self::NEW_WINDOW));
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Date'), '', 'row2');
                    $cells .= wf_TableCell($subData['date_create']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Tariffs'), '', 'row2');
                    $cells .= wf_TableCell($this->renderServices($subData));
                    $rows .= wf_TableRow($cells, 'row3');
                    $result .= wf_TableBody($rows, '100%', 0, '');

                    //append playlists
                    $result .= $this->renderPlaylists($subData);
                    //append devices
                    $result .= $this->renderDevices($subData);

                    //some user controls here
                    $result .= wf_delimiter(0);
                    $result .= $this->renderSubscriberControls($subscriberId);

                    //debug info TODO: remove it
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

    /**
     *  Renders devices of some subscriber
     * 
     * @param array $subData
     * 
     * @return string
     */
    protected function renderDevices($subData) {
        $result = '';
        if (!empty($subData['devices'])) {
            $subscriberId = $subData['id'];
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $subProfileUrl = self::URL_ME . '&' . self::ROUTE_SUBVIEW . '=' . $userLogin;
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Created'));
            $cells .= wf_TableCell(__('Updated'));
            $cells .= wf_TableCell(__('Login'));
            $cells .= wf_TableCell(__('Password'));
            $cells .= wf_TableCell(__('Device'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($subData['devices'] as $io => $eachDevice) {
                $cells = wf_TableCell($eachDevice['id']);
                $cells .= wf_TableCell($eachDevice['created']);
                $cells .= wf_TableCell($eachDevice['updated']);
                $cells .= wf_TableCell($eachDevice['login']);
                $cells .= wf_TableCell($eachDevice['password']);
                $cells .= wf_TableCell($eachDevice['device']);
                $cells .= wf_TableCell($eachDevice['ip']);
                $cells .= wf_TableCell('TODO');
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_tag('b') . __('Devices') . wf_tag('b', true) . wf_delimiter(0);
            $result .= wf_TableBody($rows, '100%', 0, '');
        } else {
            $result .= $this->messages->getStyledMessage(__('This user have no any devices'), 'warning');
        }
        return($result);
    }

    /**
     * Renders playlists of some subscriber
     * 
     * @param array $subData
     * 
     * @return string
     */
    protected function renderPlaylists($subData) {
        $result = '';
        if (!empty($subData['playlists'])) {
            $subscriberId = $subData['id'];
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $subProfileUrl = self::URL_ME . '&' . self::ROUTE_SUBVIEW . '=' . $userLogin;

            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Created'));
            $cells .= wf_TableCell(__('Updated'));
            $cells .= wf_TableCell(__('Genres'));
            $cells .= wf_TableCell(__('TV guide'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('URL'));
            $cells .= wf_TableCell(__('Device'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($subData['playlists'] as $io => $eachPlaylist) {
                $cells = wf_TableCell($eachPlaylist['id']);
                $cells .= wf_TableCell($eachPlaylist['created']);
                $cells .= wf_TableCell($eachPlaylist['updated']);
                $cells .= wf_TableCell(web_bool_led($eachPlaylist['genres']));
                $cells .= wf_TableCell(web_bool_led($eachPlaylist['tv_guide']));
                $cells .= wf_TableCell($eachPlaylist['ip']);
                $urlControls = wf_Link($eachPlaylist['url'], $eachPlaylist['url'], false, '', self::NEW_WINDOW);
                $cells .= wf_TableCell($urlControls);
                $cells .= wf_TableCell($eachPlaylist['device_id']);
                $plDeleteUrl = self::URL_ME . '&' . self::ROUTE_PLDEL . '=' . $eachPlaylist['id'] . '&' . self::ROUTE_SUBID . '=' . $subscriberId;
                $plDelControls = wf_ConfirmDialog($plDeleteUrl, web_delete_icon() . ' ' . __('Delete'), $this->messages->getDeleteAlert(), '', $subProfileUrl);
                $cells .= wf_TableCell($plDelControls);

                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_tag('b') . __('Playlists') . wf_tag('b', true) . wf_delimiter(0);
            $result .= wf_TableBody($rows, '100%', 0, '');
        } else {
            $result .= $this->messages->getStyledMessage(__('This user have no any playlists'), 'warning');
        }
        return($result);
    }

    /**
     * Returns some subscriber controls
     * 
     * @param int $subscriberId
     * 
     * @return string
     */
    protected function renderSubscriberControls($subscriberId) {
        $result = '';
        if ($this->isValidSubscriber($subscriberId)) {
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $plCreateUrl = self::URL_ME . '&' . self::ROUTE_PLCREATE . '=' . $subscriberId;
            $subProfileUrl = self::URL_ME . '&' . self::ROUTE_SUBVIEW . '=' . $userLogin;
            $plCreateLabel = web_icon_create() . ' ' . __('Just create new playlist');
            $result .= wf_ConfirmDialog($plCreateUrl, $plCreateLabel, __('Just create new playlist') . '? ' . $this->messages->getEditAlert(), 'ubButton', $subProfileUrl);

            $devCreateUrl = self::URL_ME . '&' . self::ROUTE_DEVCREATE . '=' . $subscriberId;
            $devCreateLabel = wf_img('skins/switch_models.png') . ' ' . __('Create new device');
            $result .= wf_ConfirmDialog($devCreateUrl, $devCreateLabel, __('Create new device') . '? ' . $this->messages->getEditAlert(), 'ubButton', $subProfileUrl);
        }
        return($result);
    }

    /**
     * Renders basic user registration form
     * 
     * @return string
     */
    protected function renderUserRegisterForm() {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_SUBREG, __('Login'), '', false, 20);
        $inputs .= wf_Submit(__('Register'));
        $result .= wf_Form("", 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders primary module controls
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_modalAuto(web_icon_create() . ' ' . __('Users registration'), __('Users registration'), $this->renderUserRegisterForm(), 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_SUBLIST . '=true', wf_img('skins/ukv/users.png') . ' ' . __('Subscriptions'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_TARIFFS . '=true', wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton') . ' ';
        return($result);
    }

    public function renderBundles() {
        $result = '';
        //TODO:
        $result = $this->api->get('/search/bundles');
        return($result);
    }

}
