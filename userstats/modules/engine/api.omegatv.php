<?php

class OmegaTvFrontend {

    /**
     * Contains available omegatv service tariffs id=>tariffdata
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains all tariff names as tariffid=>name
     *
     * @var array
     */
    protected $tariffNames = array();

    /**
     * Contains all of internet users data as login=>data
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains available and active omegatv service subscriptions as customerid=>data
     *
     * @var array
     */
    protected $allSubscribers = array();

    /**
     * Contains system config as key=>value
     *
     * @var array
     */
    protected $usConfig = array();

    /**
     * Current instance user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * Contains Ubilling RemoteAPI URL
     *
     * @var string
     */
    protected $apiUrl = '';

    /**
     * Contains Ubilling RemoteAPI Key
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * Default maximum devices per user count
     */
    const MAX_DEVICES = 3;

    public function __construct() {
        $this->loadUsConfig();
        $this->setOptions();
        $this->loadTariffs();
        $this->loadUsers();
        $this->loadUserSubscriptions();
    }

    /**
     * Loads userstats config into protected usConfig variable
     * 
     * @return void
     */
    protected function loadUsConfig() {
        $this->usConfig = zbs_LoadConfig();
    }

    /**
     * Sets required object options
     * 
     * @return void
     */
    protected function setOptions() {
        $this->apiUrl = $this->usConfig['API_URL'];
        $this->apiKey = $this->usConfig['API_KEY'];
    }

    /**
     * Loads available users from database
     * 
     * @return void
     */
    protected function loadUsers() {
        $query = "SELECT * from `users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['login']] = $each;
            }
        }
    }

    /**
     * Loads existing users profiles/subscriptions
     * 
     * @return void
     */
    protected function loadUserSubscriptions() {
        $query = "SELECT * from `om_users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allSubscribers[$each['customerid']] = $each;
            }
        }
    }

    /**
     * Sets current user login
     * 
     * @return void
     */
    public function setLogin($login) {
        $this->userLogin = $login;
    }

    /**
     * Loads existing tariffs from database
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `om_tariffs` WHERE `type`='base' OR `type`='bundle' ORDER BY `type` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['id']] = $each;
                $this->tariffNames[$each['tariffid']] = $each['tariffname'];
            }
        }
    }

    /**
     * Checks is user subscribed for some tariff or not?
     * 
     * @param string $login
     * @param int $tariffid
     * 
     * @return bool
     */
    protected function isUserSubscribed($login, $tariffid) {
        $result = false;
        if (!empty($this->allSubscribers)) {
            $tariffExternalId = $this->allTariffs[$tariffid]['tariffid'];
            foreach ($this->allSubscribers as $io => $each) {
                if (($each['login'] == $login)) {
                    if ($each['basetariffid'] == $tariffExternalId) {
                        $result = true;
                        break;
                    }

                    if (!empty($each['bundletariffs'])) {
                        $bundleTariffs = unserialize($each['bundletariffs']);
                        if (isset($bundleTariffs[$tariffExternalId])) {
                            $result = true;
                            break;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Returns user login transformed to some numeric hash
     * 
     * @param string $login
     * 
     * @return int
     */
    public function generateCustormerId($login) {
        $result = '';
        if (!empty($login)) {
            $result = crc32($login);
        }
        return($result);
    }

    /**
     * Renders available user devices list and some controls
     * 
     * @return string
     */
    public function renderUserDevicesForm() {
        $result = '';
        if (!empty($this->userLogin)) {
            //available devices
            $currentDevices = $this->getDevicesData();
            $currentPlaylists = $this->getPlaylistsData();

            $devCount = 0;
            $rows = '';

            if (!empty($currentDevices) OR ( !empty($currentPlaylists))) {
                $currentDevices = json_decode($currentDevices, true);
                $currentPlaylists = json_decode($currentPlaylists, true);

                if (!empty($currentDevices)) {

                    foreach ($currentDevices as $io => $each) {
                        $cells = la_TableCell($each['uniq']);
                        $cells .= la_TableCell(date("Y-m-d H:i:s", $each['activation_data']));
                        $cells .= la_TableCell($each['model']);
                        $deviceControls = la_JSAlert('?module=omegatv&deletedevice=' . $each['uniq'], __('Delete'), __('Are you sure') . '?');
                        $cells .= la_TableCell($deviceControls);
                        $rows .= la_TableRow($cells, 'row3');
                        $devCount++;
                    }
                }

                if (!empty($currentPlaylists)) {

                    foreach ($currentPlaylists as $io => $each) {
                        $cells = la_TableCell($each['uniq']);
                        $actDate = ($each['activation_data']) ? date("Y-m-d H:i:s", $each['activation_data']) : '-';
                        $cells .= la_TableCell($actDate);
                        $playlistControls = la_Link($each['url'], __('Playlist'));
                        $cells .= la_TableCell($playlistControls);
                        $deviceControls = la_JSAlert('?module=omegatv&deleteplaylist=' . $each['uniq'], __('Delete'), __('Are you sure') . '?');
                        $cells .= la_TableCell($deviceControls);
                        $rows .= la_TableRow($cells, 'row3');
                        $devCount++;
                    }
                }

                $result .= la_TableBody($rows, '100%', 0, 'sortable');
            }

            //maximum devices limit
            if ($devCount < self::MAX_DEVICES) {
                //new device activation
                if (la_CheckGet(array('getcode'))) {
                    $actCode = $this->getDeviceActivationCode();
                    $result .= la_tag('br');
                    $result .= la_tag('h3', false) . __('Activation code') . ': ' . $actCode . la_tag('h3', true);
                } else {
                    $result .= la_tag('br');
                    $actCodeControl = la_Link('?module=omegatv&getcode=true', __('Get device activation code'));
                    $newPlControl = la_Link('?module=omegatv&newplaylist=true', __('Add playlist'));
                    $result .= $actCodeControl . ' / ' . $newPlControl;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is user protected from his own stupidity?
     * 
     * @param int $tariffId
     * @return bool
     */
    protected function checkUserProtection($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = true;
        if (isset($this->usConfig['OM_PROTECTION'])) {
            if ($this->usConfig['OM_PROTECTION']) {
                if (isset($this->allTariffs[$tariffId])) {
                    $tariffFee = $this->allTariffs[$tariffId]['fee'];
                    $tariffData = $this->allTariffs[$tariffId];
                    $userData = $this->allUsers[$this->userLogin];
                    $userBalance = $userData['Cash'];
                    if ($userBalance < $tariffFee) {
                        $result = false;
                    }
                } else {
                    $result = false;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders tariffs list with subscribtion form
     * 
     * @return string
     */
    public function renderSubscribeForm() {
        $result = '';
        $iconsPath = zbs_GetCurrentSkinPath($this->usConfig) . 'iconz/';

        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $headerType = ($each['type'] == 'base') ? 'mgheaderprimary' : 'mgheader';
                $freeAppend = la_delimiter();
                $tariffFee = $each['fee'];
                $primaryLabel = ($each['type'] == 'base') ? la_img($iconsPath . 'ok_small.png') : la_img($iconsPath . 'unavail_small.png');
                $subscribedLabel = ($this->isUserSubscribed($this->userLogin, $each['id'])) ? la_img($iconsPath . 'ok_small.png') : la_img($iconsPath . 'unavail_small.png');

                $tariffInfo = la_tag('div', false, $headerType) . $each['tariffname'] . la_tag('div', true);
                $cells = la_TableCell(la_tag('b') . __('Fee') . la_tag('b', true));
                $cells .= la_TableCell($tariffFee . ' ' . $this->usConfig['currency']);
                $rows = la_TableRow($cells);
                $cells = la_TableCell(la_tag('b') . __('Base') . la_tag('b', true));
                $cells .= la_TableCell($primaryLabel);
                $rows .= la_TableRow($cells);
                $cells = la_TableCell(la_tag('b') . __('You subscribed') . la_tag('b', true));
                $cells .= la_TableCell($subscribedLabel);
                $rows .= la_TableRow($cells);
                $tariffInfo .= la_TableBody($rows, '100%', 0);
                $tariffInfo .= $freeAppend;


                if ($this->checkBalance()) {
                    if ($this->isUserSubscribed($this->userLogin, $each['id'])) {
                        $subscribeControl = la_Link('?module=omegatv&unsubscribe=' . $each['tariffid'], __('Unsubscribe'), false, 'mgunsubcontrol');
                    } else {
                        if ($this->checkUserProtection($each['id'])) {
                            $alertText = __('I have thought well and understand that I activate this service for myself not by chance and completely meaningfully and I am aware of all the consequences.');
                            $subscribeControl = la_ConfirmDialog('?module=omegatv&subscribe=' . $each['tariffid'], __('Subscribe'), $alertText, 'mgsubcontrol','?module=omegatv');
                        } else {
                            $subscribeControl = __('The amount of money in your account is not sufficient to process subscription');
                        }
                    }


                    $tariffInfo .= $subscribeControl;
                } else {
                    $tariffInfo .= __('The amount of money in your account is not sufficient to process subscription');
                }

                $result .= la_tag('div', false, 'mgcontainer') . $tariffInfo . la_tag('div', true);
            }
        }
        return ($result);
    }

    /**
     * Check user balance for subscribtion availability
     * 
     * @return bool
     */
    protected function checkBalance() {
        $result = false;
        if (!empty($this->userLogin)) {
            if (isset($this->allUsers[$this->userLogin])) {
                $userData = $this->allUsers[$this->userLogin];
                $userBalance = $this->allUsers[$this->userLogin]['Cash'];
                if ($userBalance >= 0) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns local customer ID from database
     * 
     * @param string $userLogin
     * 
     * @return int
     */
    public function getLocalCustomerId() {
        $result = '';
        if (!empty($this->allSubscribers)) {
            foreach ($this->allSubscribers as $io => $each) {
                if ($each['login'] == $this->userLogin) {
                    $result = $each['customerid'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns array of subscribed tariff Ids=>type
     * 
     * @return array
     */
    public function getSubscribedTariffs() {
        $result = array();
        $customerId = $this->getLocalCustomerId();
        if (!empty($customerId)) {
            if (isset($this->allSubscribers[$customerId])) {
                $localCustomerData = $this->allSubscribers[$customerId];
                if (!empty($localCustomerData['basetariffid'])) {
                    $result[$localCustomerData['basetariffid']] = 'base';
                }

                if (!empty($localCustomerData['bundletariffs'])) {
                    $bundleTariffs = unserialize($localCustomerData['bundletariffs']);
                    if (!empty($bundleTariffs)) {
                        foreach ($bundleTariffs as $io => $each) {
                            $result[$io] = 'bundle';
                        }
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Gets view URL via remote API
     * 
     * @return string
     */
    public function getViewButtonURL() {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=viewurl&userlogin=' . $this->userLogin;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Gets device activation code via remote API
     * 
     * @return string
     */
    public function getDeviceActivationCode() {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=getcode&userlogin=' . $this->userLogin;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Gets devices data via remote API
     * 
     * @return string
     */
    public function getDevicesData() {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=getdevices&userlogin=' . $this->userLogin;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Gets playlists data via remote API
     * 
     * @return string
     */
    public function getPlaylistsData() {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=getplaylists&userlogin=' . $this->userLogin;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Pushes device deletion request via remote API
     * 
     * @param string $uniq
     * 
     * @return string
     */
    public function pushDeviceDelete($uniq) {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=deletedev&userlogin=' . $this->userLogin . '&uniq=' . $uniq;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Pushes playlist deletion request via remote API
     * 
     * @param string $uniq
     * 
     * @return string
     */
    public function pushPlaylistDelete($uniq) {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=deletepl&userlogin=' . $this->userLogin . '&uniq=' . $uniq;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Pushes playlist assign request via remote API
     * 
     * @param string $uniq
     * 
     * @return string
     */
    public function pushPlaylistAssign() {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=assignpl&userlogin=' . $this->userLogin;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Pushes tariff subscription request via remote API
     * 
     * @param  int $tariffId
     * 
     * @return string
     */
    public function pushSubscribeRequest($tariffId) {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=subscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffId;
        @$result = file_get_contents($action);
        return ($result);
    }

    /**
     * Pushes tariff unsubscription request via remote API
     * 
     * @param  int $tariffId
     * 
     * @return string
     */
    public function pushUnsubscribeRequest($tariffId) {
        $result = '';
        $action = $this->apiUrl . '?module=remoteapi&key=' . $this->apiKey . '&action=omcontrol&param=unsubscribe&userlogin=' . $this->userLogin . '&tariffid=' . $tariffId;
        @$result = file_get_contents($action);
        return ($result);
    }

}

?>