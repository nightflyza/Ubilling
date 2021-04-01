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
     * Contains all tariffs data as serviceid=>tariffData
     *
     * @var string
     */
    protected $allTariffs = array();

    /**
     * Subscribers database abstraction layer
     *
     * @var object
     */
    protected $subscribersDb = '';

    /**
     * Tariffs database abstraction layer
     *
     * @var object
     */
    protected $tariffsDb = '';

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
    const TABLE_TARIFFS = 'ptv_tariffs';
    const UNDEF = 'undefined_';
    const NEW_WINDOW = 'TARGET="_BLANK"';
    const URL_ME = '?module=prostotv';
    const URL_USERPROFILE = '?module=userprofile&username=';
    const ROUTE_SUBLIST = 'subscribers';
    const ROUTE_SUBAJ = 'ajaxlist';
    const ROUTE_SUBVIEW = 'showsubscriber';
    const ROUTE_TARIFFS = 'tariffs';
    const ROUTE_BUNDLES = 'bundles';
    const ROUTE_PLCREATE = 'createplaylist';
    const ROUTE_PLDEL = 'deleteplaylist';
    const ROUTE_SUBID = 'subscriberid';
    const ROUTE_DEVCREATE = 'createdevice';
    const ROUTE_DEVDEL = 'deletedevice';
    const ROUTE_SUBLOOKUP = 'username';
    const PROUTE_SUBREG = 'registersubscriber';
    const PROUTE_CREATETARIFFID = 'newtariffserviceid';
    const PROUTE_CREATETARIFFMAIN = 'newtariffmainflag';
    const PROUTE_CREATETARIFFNAME = 'newtariffname';
    const PROUTE_CREATETARIFFCHANS = 'newtariffchans';
    const PROUTE_CREATETARIFFFEE = 'newtarifffee';
    const PROUTE_TARIFFEDITSUBID = 'changetariffsubscriberid';
    const PROUTE_SETMAINTARIFFID = 'changemaintariffserviceid';
    const PROUTE_SETADDTARIFFID = 'changeaddionaltariffs';

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
        $this->initTariffsDb();
        $this->loadSubscribers();
        $this->loadTariffs();
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
     * Inits tariffs database abstraction layer
     * 
     * @return void
     */
    protected function initTariffsDb() {
        $this->tariffsDb = new NyanORM(self::TABLE_TARIFFS);
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
     * Loads available tariffs from database
     * 
     * @return void
     */
    protected function loadTariffs() {
        $this->allTariffs = $this->tariffsDb->getAll('serviceid');
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
                $this->subscribersDb->data('active', '1');
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
     * Returns subscripber ID by some of the users login
     * 
     * @param string $userLogin
     * 
     * @return int/bool
     */
    public function getSubscriberId($userLogin) {
        $result = false;
        if (isset($this->allSubscribers[$userLogin])) {
            $result = $this->allSubscribers[$userLogin]['subscriberid'];
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
     * Deletes some device for subscriber
     * 
     * @param int $subscriberId
     * 
     * @return array/bool
     */
    public function deleteDevice($subscriberId, $deviceId) {
        $result = false;
        if ($this->isValidSubscriber($subscriberId)) {
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $result = $this->api->delete('objects/' . $subscriberId . '/devices/' . $deviceId);
            log_register('PTV DEVICE DELETE SUB (' . $userLogin . ') AS [' . $subscriberId . ']');
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
                $tariffLabel = '';
                if (isset($this->allTariffs[$each['id']])) {
                    $tariffLabel = $this->allTariffs[$each['id']]['name'];
                } else {
                    $tariffLabel = $each['id'];
                }
                $result .= $tariffLabel . ' ';
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
                    $cells = wf_TableCell(__('IP'), '', 'row2');
                    $cells .= wf_TableCell($userData['ip']);
                    $rows .= wf_TableRow($cells, 'row3');
                    $cells = wf_TableCell(__('Status'), '', 'row2');
                    $actLed = ($this->allSubscribers[$userLogin]['active']) ? wf_img_sized('skins/icon_active.gif', '', 10) : wf_img_sized('skins/icon_inactive.gif', '', 10);
                    $cells .= wf_TableCell($actLed . ' ' . __($subData['status']));
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
                    $result .= $this->renderSubscriberControls($subscriberId, $subData);
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
                $devDelUrl = self::URL_ME . '&' . self::ROUTE_DEVDEL . '=' . $eachDevice['id'] . '&' . self::ROUTE_SUBID . '=' . $subscriberId;
                $devDelControls = wf_ConfirmDialog($devDelUrl, web_delete_icon() . ' ' . __('Delete'), $this->messages->getDeleteAlert(), '', $subProfileUrl);
                $cells .= wf_TableCell($devDelControls);
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
     * Renders users tariff change form
     * 
     * @param int $subscriberId
     * 
     * @return string
     */
    protected function renderTariffEditForm($subscriberId) {
        $result = '';
        if (!empty($this->allTariffs)) {
            $mainTariffsArr = array();
            $additionalTariffsArr = array();
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $currentMainTariff = $this->allSubscribers[$userLogin]['maintariff'];
            foreach ($this->allTariffs as $io => $each) {
                if ($each['main']) {
                    $mainTariffsArr[$each['serviceid']] = $each['name'];
                } else {
                    $additionalTariffsArr[$each['serviceid']] = $each['name'];
                }
            }

            if (!empty($mainTariffsArr)) {
                $inputs = wf_HiddenInput(self::PROUTE_TARIFFEDITSUBID, $subscriberId);
                $inputs .= wf_Selector(self::PROUTE_SETMAINTARIFFID, $mainTariffsArr, __('Primary') . ' ' . __('Tariff'), $currentMainTariff, true);


                $inputs .= wf_Submit(__('Save'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Tariffs') . ': ' . __('Not exists'), 'error');
        }
        return($result);
    }

    /**
     * Sets primary tariff for some subscriber
     * 
     * @param int $subscriberId
     * @param int $tariffId
     * 
     * @return void
     */
    public function setMainTariff($subscriberId, $tariffId) {
        $tariffId = ubRouting::filters($tariffId, 'int');
        $subscriberId = ubRouting::filters($subscriberId, 'int');
        if ($this->isValidSubscriber($subscriberId)) {
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $currentTariff = $this->allSubscribers[$userLogin]['maintariff'];
            //deleting old service if required
            if ($currentTariff) {
                if ($currentTariff != $tariffId) {
                    $this->api->delete('/objects/' . $subscriberId . '/services/' . $currentTariff);
                    log_register('PTV SUB (' . $userLogin . ') UNSET TARIFF [' . $tariffId . '] AS [' . $subscriberId . ']');
                }
            }

            if ($currentTariff != $tariffId) {
                //database update
                $this->subscribersDb->data('maintariff', $tariffId);
                $this->subscribersDb->where('subscriberid', '=', $subscriberId);
                $this->subscribersDb->save();

                //push to service API
                $this->api->post('/objects/' . $subscriberId . '/services', array('id' => $tariffId, 'auto_renewal' => 1));

                //put log record
                log_register('PTV SUB (' . $userLogin . ') SET TARIFF [' . $tariffId . '] AS [' . $subscriberId . ']');
            }
        }
    }

    /**
     * Returns some subscriber controls
     * 
     * @param int $subscriberId
     * @param array $subData
     * 
     * @return string
     */
    protected function renderSubscriberControls($subscriberId, $subData = array()) {
        $result = '';
        if ($this->isValidSubscriber($subscriberId)) {
            $userLogin = $this->getSubscriberLogin($subscriberId);
            $plCreateUrl = self::URL_ME . '&' . self::ROUTE_PLCREATE . '=' . $subscriberId;
            $subProfileUrl = self::URL_ME . '&' . self::ROUTE_SUBVIEW . '=' . $userLogin;
            $plCreateLabel = web_icon_create() . ' ' . __('Just create new playlist');
            $result .= wf_ConfirmDialog($plCreateUrl, $plCreateLabel, __('Just create new playlist') . '? ' . $this->messages->getEditAlert(), 'ubButton', $subProfileUrl) . ' ';

            $devCreateUrl = self::URL_ME . '&' . self::ROUTE_DEVCREATE . '=' . $subscriberId;
            $devCreateLabel = wf_img('skins/switch_models.png') . ' ' . __('Create new device');
            $result .= wf_ConfirmDialog($devCreateUrl, $devCreateLabel, __('Create new device') . '? ' . $this->messages->getEditAlert(), 'ubButton', $subProfileUrl) . ' ';

            $result .= wf_modalAuto(wf_img('skins/icon_tariff.gif') . ' ' . __('Edit tariff'), __('Tariff'), $this->renderTariffEditForm($subscriberId), 'ubButton');

            if (!empty($subData)) {
                $userScheme = wf_tag('pre') . print_r($subData, true) . wf_tag('pre', true);
                $result .= wf_modal(wf_img('skins/brain.png') . ' ' . __('User inside'), __('User inside'), $userScheme, 'ubButton', '800', '600');
            }
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

    /**
     * Renders bundles (server side tariffs) available at service
     * 
     * @return string
     */
    public function renderBundles() {
        $result = '';
        $raw = $this->api->get('/search/bundles');
        if ($raw) {
            if (isset($raw['bundles'])) {
                $cells = wf_TableCell(__('Service ID'));
                $cells .= wf_TableCell(__('Name') . ' ' . __('UA'));
                $cells .= wf_TableCell(__('Name') . ' ' . __('RU'));
                $cells .= wf_TableCell(__('Primary'));
                $cells .= wf_TableCell(__('Channels'));
                $cells .= wf_TableCell(__('Price'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($raw['bundles'] as $io => $each) {
                    $cells = wf_TableCell($each['service_id']);
                    $cells .= wf_TableCell($each['name_uk']);
                    $cells .= wf_TableCell($each['name_ru']);
                    $cells .= wf_TableCell(web_bool_led($each['main']));
                    $cells .= wf_TableCell($each['channels_count']);
                    $cells .= wf_TableCell($each['cost']);
                    $rows .= wf_TableRow($cells, 'row5');
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Tariffs offered') . ' ' . __('Not exists'), 'error');
        }
        return($result);
    }

    /**
     * Renders new tariff creation form
     * 
     * @return string
     */
    protected function renderTariffCreateForm() {
        $result = '';
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs = wf_TextInput(self::PROUTE_CREATETARIFFID, __('Service ID') . $sup, '', true, 5, 'digits');
        $inputs .= wf_TextInput(self::PROUTE_CREATETARIFFNAME, __('Tariff name') . $sup, '', true, 20);
        $inputs .= wf_CheckInput(self::PROUTE_CREATETARIFFMAIN, __('Primary'), true, true);
        $inputs .= wf_TextInput(self::PROUTE_CREATETARIFFCHANS, __('Description'), '', true, 20);
        $inputs .= wf_TextInput(self::PROUTE_CREATETARIFFFEE, __('Fee') . $sup, '', true, 4, 'finance');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Create'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Creates new tariff in database
     * 
     * @return void/string on error
     */
    public function createTariff() {
        $result = '';
        if (ubRouting::checkPost(array(self::PROUTE_CREATETARIFFNAME, self::PROUTE_CREATETARIFFID))) {
            $tariffId = ubRouting::post(self::PROUTE_CREATETARIFFID, 'int');
            $tariffName = ubRouting::post(self::PROUTE_CREATETARIFFNAME, 'mres');
            $tariffMain = (ubRouting::checkPost(self::PROUTE_CREATETARIFFMAIN)) ? 1 : 0;
            $tariffChans = ubRouting::post(self::PROUTE_CREATETARIFFCHANS, 'mres');
            $tariffFee = ubRouting::post(self::PROUTE_CREATETARIFFFEE);
            if ($tariffId) {
                if (!isset($this->allTariffs[$tariffId])) {
                    if ($tariffName) {
                        if (zb_checkMoney($tariffFee)) {
                            $this->tariffsDb->data('serviceid', $tariffId);
                            $this->tariffsDb->data('main', $tariffMain);
                            $this->tariffsDb->data('name', $tariffName);
                            $this->tariffsDb->data('chans', $tariffChans);
                            $this->tariffsDb->data('fee', $tariffFee);
                            $this->tariffsDb->create();
                            log_register('PTV TARIFF CREATE `' . $tariffName . '` AS [' . $tariffId . '] FEE `' . $tariffFee . '`');
                        } else {
                            $result .= __('Wrong format of money sum');
                        }
                    } else {
                        $result .= __('Wrong tariff name');
                    }
                } else {
                    $result .= __('Duplicate element ID');
                    log_register('PTV TARIFF CREATE `' . $tariffName . '` AS [' . $tariffId . '] DUPLICATE FAIL');
                }
            } else {
                $result .= __('Wrong tariff id');
            }
        }
        return($result);
    }

    /**
     * Renders list of tariffs available for users
     * 
     * @return string
     */
    public function renderTariffs() {
        $result = '';
        $result .= wf_modalAuto(web_add_icon() . ' ' . __('Create new tariff'), __('Create new tariff'), $this->renderTariffCreateForm(), 'ubButton');
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_BUNDLES . '=true', wf_img('skins/tariffinfo.gif') . ' ' . __('Available tariffs'), false, 'ubButton');
        $result .= wf_delimiter();
        if (!empty($this->allTariffs)) {
            $cells = wf_TableCell(__('Service ID'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Primary'));
            $cells .= wf_TableCell(__('Description'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['serviceid']);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell(web_bool_led($each['main']));
                $cells .= wf_TableCell($each['chans']);
                $cells .= wf_TableCell($each['fee']);
                $cells .= wf_TableCell('TODO');
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return($result);
    }

    /**
     * Performs fee processing of all registered subscribers
     * 
     * @return void
     */
    public function feeProcessing() {
        if (!empty($this->allSubscribers)) {
            //TODO
        }
    }

}
