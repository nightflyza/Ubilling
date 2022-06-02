<?php

/**
 * OmegaTV OTT service implementation
 */
class OmegaTV {

    /**
     * HlsTV object placeholder for further usage 
     *
     * @var object
     */
    protected $hls = '';

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all of available omega tariffs as id=>data
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
     * Contains available user profiles as customerid=>data
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains available users data as login=>data
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains local queue for deffered actions
     *
     * @var array
     */
    protected $queue = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains default channel icon size
     *
     * @var int
     */
    protected $chanIconSize = 32;

    /**
     * Is tariffs unsub delayed in queue or not flag
     *
     * @var bool
     */
    protected $unsubDelay = false;

    /**
     * Contains array of currently suspended users without base tariff
     *
     * @var array
     */
    protected $suspended = array();

    /**
     * Contains bundled internet tariffs names as name=>someshit. No fee charging for them. Lol.
     *
     * @var array
     */
    protected $bundledTariffs = array();

    /**
     * Basic module path
     */
    const URL_ME = '?module=omegatv';

    /**
     * Default user profile viewing URL
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Default subscriber profile viewing URL
     */
    const URL_SUBSCRIBER = '?module=omegatv&customerprofile=';

    /**
     * Creates new OmegaTV instance
     */
    public function __construct() {
        $this->initHls();
        $this->initMessages();
        $this->loadAlter();
        $this->loadTariffs();
        $this->loadUserData();
        $this->loadUserProfiles();
        $this->loadQueue();
        $this->loadSuspended();
        $this->loadBundleTariffs();
    }

    /**
     * Loads system alter config into protected property.
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads bundle tariffs list from config option into protected prop.
     * 
     * @return void
     */
    protected function loadBundleTariffs() {
        //A-A-A-A-A-A-AA!!!!!111 Skybetik  eto pizdets!
        if (isset($this->altCfg['OMEGATV_TARIFFSBUNDLE'])) {
            $bundleTariffsList = array();
            $bundleTariffsTmp = explode(',', $this->altCfg['OMEGATV_TARIFFSBUNDLE']);

            if (!empty($bundleTariffsTmp)) {
                foreach ($bundleTariffsTmp as $optionIndex => $eachBundleTariffName) {
                    $cleanTariffName = trim($eachBundleTariffName);
                    $this->bundledTariffs[$cleanTariffName] = $eachBundleTariffName;
                }
            }
        }
    }

    /**
     * Inits HLS object for further usage
     * 
     * @return void
     */
    protected function initHls() {
        $this->hls = new HlsTV();
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
     * Loads existing tariffs from database
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `om_tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['id']] = $each;
                $this->tariffNames[$each['tariffid']] = $each['tariffname'];
            }
        }
    }

    /**
     * Loads existing queue records for some actions
     * 
     * @return void
     */
    protected function loadQueue() {
        $query = "SELECT * from `om_queue`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->queue[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing suspended users
     * 
     * @return void
     */
    protected function loadSuspended() {
        $query = "SELECT * from `om_suspend`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->suspended[$each['login']] = $each['id'];
            }
        }
    }

    /**
     * Loads existing users profiles
     * 
     * @return void
     */
    protected function loadUserProfiles() {
        $query = "SELECT * from `om_users`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['customerid']] = $each;
            }
        }
    }

    /**
     * Loads internet users data into protected property for further usage
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllData();
    }

    /**
     * Renders available tariffs list
     * 
     * @param string $list - tariff list to render base/bundle/promo
     * @param bool $withIds - render tariff IDs or not?
     * @param bool $withChannels - render channels preview or not?
     * 
     * @return string
     */
    public function renderTariffsRemote($list, $withIds = true, $withChannels = true) {
        $result = '';

        switch ($list) {
            case 'base':
                $allTariffs = $this->hls->getTariffsBase();
                break;
            case 'bundle':
                $allTariffs = $this->hls->getTariffsBundle();
                break;
            case 'promo':
                $allTariffs = $this->hls->getTariffsPromo();
                break;
        }
        if (!empty($allTariffs)) {
            if (isset($allTariffs['result'])) {
                if ($list != 'promo') {
                    $allTariffs = $allTariffs['result'];
                } else {
                    $allTariffs = $allTariffs['result']['promo_limited'];
                }
                if (!empty($allTariffs)) {
                    foreach ($allTariffs as $io => $each) {
                        $tariffTitle = ($withIds) ? $each['tariff_id'] . ': ' . $each['tariff_name'] : $each['tariff_name'];
                        $result .= wf_tag('h3') . $tariffTitle . wf_tag('h3', true);
                        if ($withChannels) {
                            if (!empty($each['hls_channels'])) {
                                $cells = wf_TableCell('');
                                $cells .= wf_TableCell(__('Channels'));
                                $cells .= wf_TableCell(__('Category'));

                                $rows = wf_TableRow($cells, 'row1');
                                foreach ($each['hls_channels'] as $chanId => $eachChannel) {
                                    $cells = wf_TableCell(wf_img_sized($eachChannel['logo'], $eachChannel['name'], $this->chanIconSize), $this->chanIconSize + 10);
                                    $cells .= wf_TableCell($eachChannel['name']);
                                    $cells .= wf_TableCell($eachChannel['ganre']);

                                    $rows .= wf_TableRow($cells, 'row3');
                                }
                                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                            }
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Trys to render human-readable tariff name
     * 
     * @param int $tariffId
     * 
     * @return string
     */
    protected function getTariffName($tariffId) {
        $result = '';
        if (isset($this->tariffNames[$tariffId])) {
            $result .= $this->tariffNames[$tariffId];
        } else {
            $result .= $tariffId;
        }
        return ($result);
    }

    /**
     * Returns current user devices info as JSON
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function getUserDevicesData($userLogin) {
        $result = '';
        $customerId = $this->getLocalCustomerId($userLogin);
        if (!empty($customerId)) {
            $userInfo = $this->hls->getUserInfo($customerId);
            if (isset($userInfo['result'])) {
                $userInfo = $userInfo['result'];
                if (isset($userInfo['devices'])) {
                    $result .= json_encode($userInfo['devices']);
                }
            }
        }
        return ($result);
    }

    /**
     * Returns current user playlists info as JSON
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function getUserPlaylistsData($userLogin) {
        $result = '';
        $customerId = $this->getLocalCustomerId($userLogin);
        if (!empty($customerId)) {
            $userInfo = $this->hls->getUserInfo($customerId);
            if (isset($userInfo['result'])) {
                $userInfo = $userInfo['result'];
                if (isset($userInfo['playlists'])) {
                    $result .= json_encode($userInfo['playlists']);
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes some device from user
     * 
     * @param string $userLogin
     * @param string $uniq
     * 
     * @return void
     */
    public function deleteUserDevice($userLogin, $uniq) {
        $customerId = $this->getLocalCustomerId($userLogin);
        $uniq = trim($uniq);
        if (!empty($customerId)) {
            $userInfo = $this->hls->getUserInfo($customerId);
            if (isset($userInfo['result'])) {
                //checking for user device ownership
                $userInfo = $userInfo['result'];
                if (isset($userInfo['devices'])) {
                    if (!empty($userInfo['devices'])) {
                        foreach ($userInfo['devices'] as $io => $each) {
                            if ($each['uniq'] == $uniq) {
                                $this->deleteDevice($customerId, $uniq);
                                log_register('OMEGATV DEVICE DELETE `' . $uniq . '` FOR (' . $userLogin . ') AS [' . $customerId . ']');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Deletes some playlists assigned from user
     * 
     * @param string $userLogin
     * @param string $uniq
     * 
     * @return void
     */
    public function deleteUserPlaylist($userLogin, $uniq) {
        $customerId = $this->getLocalCustomerId($userLogin);
        $uniq = trim($uniq);
        if (!empty($customerId)) {
            $userInfo = $this->hls->getUserInfo($customerId);
            if (isset($userInfo['result'])) {
                //checking for user playlist ownership
                $userInfo = $userInfo['result'];
                if (isset($userInfo['playlists'])) {
                    if (!empty($userInfo['playlists'])) {
                        foreach ($userInfo['playlists'] as $io => $each) {
                            if ($each['uniq'] == $uniq) {
                                $this->deletePlaylist($customerId, $uniq);
                                log_register('OMEGATV PLAYLIST DELETE `' . $uniq . '` FOR (' . $userLogin . ') AS [' . $customerId . ']');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Creates new playlist and assigns it to existing user
     * 
     * @param string $userLogin
     * 
     * @return void/string on error
     */
    public function assignUserPlaylist($userLogin) {
        $result = '';
        $customerId = $this->getLocalCustomerId($userLogin);
        if (!empty($customerId)) {
            if (isset($this->allUsers[$customerId])) {
                $assignResult = $this->hls->addPlayList($customerId);
                if (isset($assignResult['error'])) {
                    $result .= __('Strange exeption') . ': ' . $assignResult['error']['code'] . ' - ' . $assignResult['error']['msg'];
                } else {
                    $uniq = $assignResult['result']['uniq'];
                    $userLogin = $this->getLocalCustomerLogin($customerId);
                    log_register('OMEGATV PLAYLIST ASSIGN `' . $uniq . '` FOR (' . $userLogin . ') AS [' . $customerId . ']');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('User not exists');
            }
        }
        return ($result);
    }

    /**
     * Renders form to manual tariff changing
     * 
     * @param int $customerId
     * 
     * @return string
     */
    protected function renderManualTariffForm($customerId) {
        $customerId = vf($customerId, 3);
        $result = '';
        $userData = @$this->allUsers[$customerId];
        $currentBundleTariffs = $this->extractBundle($customerId);
        if (!empty($this->allTariffs)) {
            $inputs = '';
            $baseTariffs = array();
            $bundleTariffs = array('' => '-');
            foreach ($this->allTariffs as $io => $each) {
                if ($each['type'] == 'base') {
                    $baseTariffs[$each['tariffid']] = $each['tariffname'] . ' (' . __($each['type']) . ')';
                }

                if ($each['type'] == 'bundle') {
                    if (!isset($currentBundleTariffs[$each['tariffid']])) {
                        $bundleTariffs[$each['tariffid']] = $each['tariffname'] . ' (' . __($each['type']) . ')';
                    }
                }
            }

            $inputs .= wf_Selector('changebasetariff', $baseTariffs, __('Base tariff'), $userData['basetariffid'], true);
            $inputs .= wf_Selector('addbundletariff', $bundleTariffs, __('Add bundle tariff'), '', true);
            $inputs .= wf_CheckInput('deleteallbundle', __('Delete all bundle tariffs'), true, false);
            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form(self::URL_SUBSCRIBER . $customerId, 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Performs editing of user tariffs
     * 
     * @param int $customerId
     * 
     * @return void
     */
    public function changeUserTariffs($customerId) {
        $customerId = vf($customerId, 3);
        if (wf_CheckPost(array('changebasetariff'))) {
            $newBase = vf($_POST['changebasetariff'], 3);
            if (isset($this->allUsers[$customerId])) {
                $userLogin = $this->allUsers[$customerId]['login'];
                $userCurrentBase = $this->allUsers[$customerId]['basetariffid'];
                $userCurrentBundle = $this->extractBundle($customerId);
                if ($userCurrentBase != $newBase) {
                    $newTariffs = array('base' => $newBase, 'bundle' => $userCurrentBundle);
                    $this->hls->setUserTariff($customerId, $newTariffs);
                    simple_update_field('om_users', 'basetariffid', $newBase, "WHERE `customerid`='" . $customerId . "'");
                    log_register('OMEGATV SET TARIFF [' . $newBase . '] BASE FOR (' . $userLogin . ') AS [' . $customerId . ']');
                }

                if (wf_CheckPost(array('addbundletariff'))) {
                    $newBundle = vf($_POST['addbundletariff'], 3);
                    if (!isset($userCurrentBundle[$newBundle])) {
                        $userCurrentBundle[$newBundle] = $newBundle;
                        $newTariffs = array('base' => $newBase, 'bundle' => $userCurrentBundle);
                        $this->hls->setUserTariff($customerId, $newTariffs);
                        $saveBundle = trim(serialize($userCurrentBundle));
                        simple_update_field('om_users', 'bundletariffs', $saveBundle, "WHERE `customerid`='" . $customerId . "'");
                        log_register('OMEGATV SET TARIFF [' . $newBundle . '] BUNDLE FOR (' . $userLogin . ') AS [' . $customerId . ']');
                    }
                }

                if (wf_CheckPost(array('deleteallbundle'))) {
                    $userCurrentBundle = array();
                    $newTariffs = array('base' => $newBase, 'bundle' => $userCurrentBundle);
                    $this->hls->setUserTariff($customerId, $newTariffs);
                    $saveBundle = trim(serialize($userCurrentBundle));
                    simple_update_field('om_users', 'bundletariffs', $saveBundle, "WHERE `customerid`='" . $customerId . "'");
                    log_register('OMEGATV FLUSH BUNDLE FOR (' . $userLogin . ') AS [' . $customerId . ']');
                }
            }
        }
    }

    /**
     * Renders manual device assign form
     * 
     * @return string
     */
    protected function renderDeviceAddForm($customerId) {
        $result = '';
        $inputs = wf_HiddenInput('manualassigndevice', 'true');
        $inputs .= wf_HiddenInput('manualassigndevicecustomerid', $customerId);
        $inputs .= wf_TextInput('manualassigndeviceuniq', __('Uniq'), '', true, 20, 'alphanumeric');
        $inputs .= wf_CheckInput('manualassignnewplaylist', __('Just create new playlist'), true, false);
        $inputs .= wf_Submit(__('Assign'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Assigns some device uniq to some customer
     * 
     * @return void/string on error
     */
    public function assignDeviceManual() {
        $result = '';
        if (wf_CheckPost(array('manualassigndevice', 'manualassigndevicecustomerid', 'manualassigndeviceuniq'))) {
            $customerId = vf($_POST['manualassigndevicecustomerid'], 3); //int
            $uniq = vf($_POST['manualassigndeviceuniq']); //alphanumeric
            if (isset($this->allUsers[$customerId])) {
                $assignResult = $this->hls->addDevice($customerId, $uniq);
                if (isset($assignResult['error'])) {
                    $result .= __('Strange exeption') . ': ' . $assignResult['error']['code'] . ' - ' . $assignResult['error']['msg'];
                } else {
                    $userLogin = $this->getLocalCustomerLogin($customerId);
                    log_register('OMEGATV DEVICE ASSIGN `' . $uniq . '` FOR (' . $userLogin . ') AS [' . $customerId . ']');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('User not exists');
            }
        }
        return ($result);
    }

    /**
     * Assigns new playlist for some existing user
     * 
     * @return void/string on error
     */
    public function assignPlaylistManual() {
        $result = '';
        if (wf_CheckPost(array('manualassigndevicecustomerid', 'manualassigndevice', 'manualassignnewplaylist'))) {
            $customerId = vf($_POST['manualassigndevicecustomerid'], 3); //int
            if (isset($this->allUsers[$customerId])) {
                $assignResult = $this->hls->addPlayList($customerId);
                if (isset($assignResult['error'])) {
                    $result .= __('Strange exeption') . ': ' . $assignResult['error']['code'] . ' - ' . $assignResult['error']['msg'];
                } else {
                    $uniq = $assignResult['result']['uniq'];
                    $userLogin = $this->getLocalCustomerLogin($customerId);
                    log_register('OMEGATV PLAYLIST ASSIGN `' . $uniq . '` FOR (' . $userLogin . ') AS [' . $customerId . ']');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('User not exists');
            }
        }
        return ($result);
    }

    /**
     * Renders profile controls
     * 
     * @return string
     */
    protected function renderProfileControls($customerId) {
        $customerId = vf($customerId, 3);
        $result = wf_tag('br');
        $result .= wf_Link(self::URL_ME . '&customerprofile=' . $customerId . '&blockuser=true', web_bool_led(0) . ' ' . __('Block user'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&customerprofile=' . $customerId . '&unblockuser=true', web_bool_led(1) . ' ' . __('Unblock user'), false, 'ubButton');
        $result .= wf_modalAuto(web_edit_icon() . ' ' . __('Edit tariff'), __('Edit tariff'), $this->renderManualTariffForm($customerId), 'ubButton');
        $result .= wf_modalAuto(wf_img('skins/switch_models.png') . ' ' . __('Assign device'), __('Assign device'), $this->renderDeviceAddForm($customerId), 'ubButton');
        return ($result);
    }

    /**
     * Sets user local and remote profile as active or not
     * 
     * @param int $customerId
     * @param bool $state
     * 
     * @return void
     */
    public function setCustomerActive($customerId, $state) {
        $customerId = vf($customerId, 3);
        if (isset($this->allUsers[$customerId])) {
            $userLogin = $this->allUsers[$customerId]['login'];
            $where = "WHERE `customerid`='" . $customerId . "'";
            if ($state) {
                $this->hls->setUserActivate($customerId);
                simple_update_field('om_users', 'active', '1', $where);
                log_register('OMEGATV UNBLOCK USER (' . $userLogin . ') AS [' . $customerId . ']');
                $this->suspendUser($userLogin, false);
            } else {
                $this->hls->setUserBlock($customerId);
                simple_update_field('om_users', 'active', '0', $where);
                log_register('OMEGATV BLOCK USER (' . $userLogin . ') AS [' . $customerId . ']');
                $this->suspendUser($userLogin, true);
            }
        }
    }

    /**
     * Renders some user profile info
     * 
     * @param int $customerId
     * 
     * @return string
     */
    public function renderUserInfo($customerId) {
        $customerId = vf($customerId, 3);
        $result = '';
        $result .= wf_AjaxLoader();

        $userInfo = $this->hls->getUserInfo($customerId);

        $localUserInfo = @$this->allUsers[$customerId];

        if (isset($userInfo['result'])) {
            $userInfo = $userInfo['result'];
            $cells = wf_TableCell(__('Full address'), '', 'row2');
            $userAddress = @$this->allUserData[$localUserInfo['login']]['fulladress'];
            $userLink = wf_Link(self::URL_PROFILE . $localUserInfo['login'], web_profile_icon() . ' ' . $userAddress);
            $cells .= wf_TableCell($userLink);
            $rows = wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('ID'), '', 'row2');
            $cells .= wf_TableCell($userInfo['id']);
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Date'), '', 'row2');
            $cells .= wf_TableCell($localUserInfo['actdate']);
            $rows .= wf_TableRow($cells, 'row3');

            if (!empty($userInfo['tariff'])) {
                foreach ($userInfo['tariff'] as $io => $each) {
                    $cells = wf_TableCell(__('Tariffs') . ' ' . __($io), '', 'row2');
                    $tariffsList = '';
                    if (!empty($each)) {
                        foreach ($each as $ia => $tariffId) {
                            $tariffsList .= $this->getTariffName($tariffId) . ' ';
                        }
                    }
                    $cells .= wf_TableCell($tariffsList);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $cells = wf_TableCell(__('Status'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($userInfo['status']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Preview'), '', 'row2');
            $cells .= wf_TableCell(wf_Link($userInfo['web_url'], __('View online'), false, '', 'TARGET="_BLANK"'));
            $rows .= wf_TableRow($cells, 'row3');

            if (!empty($userInfo['devices'])) {
                foreach ($userInfo['devices'] as $io => $each) {
                    $cells = wf_TableCell(__('Device') . ' ' . $io, '', 'row2');
                    $deviceLabel = __('Uniq') . ': ' . $each['uniq'] . ' ' . __('Date') . ': ' . date("Y-m-d H:i:s", $each['activation_data']) . ' ' . __('Model') . ': ' . $each['model'];
                    $deviceControls = wf_JSAlert(self::URL_ME . '&subscriptions=true&customerid=' . $customerId . '&deletedevice=' . $each['uniq'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells .= wf_TableCell($deviceControls . ' ' . $deviceLabel);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            if (!empty($userInfo['playlists'])) {
                foreach ($userInfo['playlists'] as $io => $each) {
                    $cells = wf_TableCell(__('Playlist') . ' ' . $io, '', 'row2');
                    $actDate = ($each['activation_data']) ? date("Y-m-d H:i:s", $each['activation_data']) : __('Inactive');
                    $playlistLabel = __('Uniq') . ': ' . $each['uniq'] . ' ' . __('Date') . ': ' . $actDate . ' ' . wf_Link($each['url'], __('Download'));
                    $playlistControls = wf_JSAlert(self::URL_ME . '&subscriptions=true&customerid=' . $customerId . '&deleteplaylist=' . $each['uniq'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells .= wf_TableCell($playlistControls . ' ' . $playlistLabel);
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }

            $cells = wf_TableCell(__('Device activation'), '', 'row2');
            $getCodeLink = $this->ajDevCodeLink($customerId, __('Get code'));
            $cells .= wf_TableCell(wf_AjaxContainer('deviceactivationcodecontainer', '', $getCodeLink));
            $rows .= wf_TableRow($cells, 'row3');

            $result .= wf_TableBody($rows, '100%', 0);
        }

        if (!empty($localUserInfo)) {
            $result .= wf_tag('b') . __('Local profile') . wf_tag('b', true) . wf_tag('br');
            $rows = '';

            $cells = wf_TableCell(__('Tariff') . ' ' . __('base'), '', 'row2');
            $cells .= wf_TableCell($this->getTariffName($localUserInfo['basetariffid']));
            $rows .= wf_TableRow($cells, 'row3');

            $bundleTariffs = $this->extractBundle($customerId);
            $bundleTariffsList = '';
            if (!empty($bundleTariffs)) {
                foreach ($bundleTariffs as $io => $each) {
                    $bundleTariffsList .= $this->getTariffName($io) . ' ';
                }
            }

            $cells = wf_TableCell(__('Tariffs') . ' ' . __('bundle'), '', 'row2');
            $cells .= wf_TableCell($bundleTariffsList);
            $rows .= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Status'), '', 'row2');
            $cells .= wf_TableCell(web_bool_led($localUserInfo['active']));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Suspended'), '', 'row2');
            $suspFlag = (isset($this->suspended[$localUserInfo['login']])) ? true : false;
            $cells .= wf_TableCell(web_bool_led($suspFlag));
            $rows .= wf_TableRow($cells, 'row3');

            $result .= wf_TableBody($rows, '100%', 0);
        }

        $result .= $this->renderProfileControls($customerId);

        return($result);
    }

    /**
     * Renders list of all devices with some controls
     * 
     * @return string
     */
    public function renderDevicesList() {
        $result = '';
        $allDevices = $this->hls->getDeviceList();
        if (isset($allDevices['result'])) {
            if (!empty($allDevices['result'])) {
                $allDevices = $allDevices['result'];
                $cells = wf_TableCell(__('Uniq'));
                $cells .= wf_TableCell(__('Model'));
                $cells .= wf_TableCell(__('Registration'));
                $cells .= wf_TableCell(__('Activation'));
                $cells .= wf_TableCell(__('User'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($allDevices as $io => $each) {
                    $cells = wf_TableCell($each['uniq']);
                    $cells .= wf_TableCell($each['model']);
                    $cells .= wf_TableCell(date("Y-m-d H:i:s", $each['registration_date']));
                    $cells .= wf_TableCell(date("Y-m-d H:i:s", $each['activation_date']));
                    $userLogin = $this->getLocalCustomerLogin($each['customer_id']);
                    $userAddress = @$this->allUserData[$userLogin]['fulladress'];
                    $userLink = wf_Link(self::URL_SUBSCRIBER . $each['customer_id'], web_profile_icon() . ' ' . $userAddress);
                    $cells .= wf_TableCell($userLink);
                    $actLinks = wf_JSAlert(self::URL_ME . '&devices=true&customerid=' . $each['customer_id'] . '&deletedevice=' . $each['uniq'], web_delete_icon(), $this->messages->getDeleteAlert());
                    $cells .= wf_TableCell($actLinks);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_NOREPLY', 'warning');
        }
        return ($result);
    }

    /**
     * Extract existing customer available tariffs
     * 
     * @param int $customerId
     * 
     * @return array
     */
    protected function extractBundle($customerId) {
        $result = array();
        if (isset($this->allUsers[$customerId])) {
            $localUserInfo = $this->allUsers[$customerId];
            $bundleTariffs = $localUserInfo['bundletariffs'];
            if (!empty($bundleTariffs)) {
                $result = unserialize($bundleTariffs);
            }
        }
        return ($result);
    }

    /**
     * Returns device activation code ajax link
     * 
     * @param int $customerId
     * @param string $label
     * 
     * @return string
     */
    protected function ajDevCodeLink($customerId, $label) {
        $result = wf_AjaxLink(self::URL_ME . '&subscriptions=true&getdevicecode=' . $customerId, $label, 'deviceactivationcodecontainer');
        return ($result);
    }

    /**
     * Renders default module controls
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&subscriptions=true', wf_img('skins/ukv/users.png') . ' ' . __('Subscriptions'), false, 'ubButton') . ' ';
        $result .= wf_modalAuto(wf_img('skins/ukv/add.png') . ' ' . __('Users registration'), __('Registration'), $this->renderUserRegisterForm(), 'ubButton');
        $result .= wf_Link(self::URL_ME . '&tariffs=true', wf_img('skins/ukv/dollar.png') . ' ' . __('Tariffs'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&devices=true', wf_img('skins/switch_models.png') . ' ' . __('Devices'), false, 'ubButton') . ' ';
        // $result .= wf_Link(self::URL_ME . '&reports=true', wf_img('skins/ukv/report.png') . ' ' . __('Reports'), false, 'ubButton') . ' ';
        return($result);
    }

    /**
     * Renders new customer registration form
     * 
     * @return string
     */
    protected function renderUserRegisterForm() {
        $result = '';
        $loginPreset = (wf_CheckGet(array('username'))) ? $_GET['username'] : '';
        $inputs = wf_HiddenInput('manualregister', 'true');
        $inputs .= wf_TextInput('manualregisterlogin', __('Login'), $loginPreset, true, '15');
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new user profile for existing internet user
     * 
     * @param string $userLogin
     * 
     * @return void/strin on error
     */
    public function registerUserManual($userLogin) {
        $result = '';
        if (!empty($userLogin)) {
            if (isset($this->allUserData[$userLogin])) {
                $userLocalProfile = $this->getLocalCustomerId($userLogin);
                if (empty($userLocalProfile)) {
                    $this->createUserProfile($userLogin);
                } else {
                    $result .= __('Something went wrong') . ': ' . __('Duplicate login') . ' - ' . $userLogin;
                    log_register('OMEGATV FAIL CUSTOMER REGISTER (' . $userLogin . ') DUPLICATE');
                }
            } else {
                $result .= __('Something went wrong') . ': ' . __('User not exist') . ' - ' . $userLogin;
                log_register('OMEGATV FAIL CUSTOMER REGISTER (' . $userLogin . ') NOLOGIN');
            }
        }
        return ($result);
    }

    /**
     * Renders channels preview controls panel
     * 
     * @return string
     */
    public function renderChanControls() {
        $result = wf_Link(self::URL_ME . '&tariffs=true&chanlist=base', web_icon_search() . ' ' . __('Base'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&tariffs=true&chanlist=bundle', web_icon_search() . ' ' . __('Bundle'), false, 'ubButton');
        $result .= wf_Link(self::URL_ME . '&tariffs=true&chanlist=promo', web_icon_search() . ' ' . __('Promo'), false, 'ubButton');
        return ($result);
    }

    /**
     * Returns array of available remote tariffs as tariffid=>name
     * 
     * @return array
     */
    protected function getTariffsRemote() {
        $result = array();
        $baseTariffs = $this->hls->getTariffsBase();
        $bundleTariffs = $this->hls->getTariffsBundle();
        $promoTariffs = $this->hls->getTariffsPromo();

        if (isset($baseTariffs['result'])) {
            foreach ($baseTariffs['result'] as $io => $each) {
                $result[$each['tariff_id']] = $each['tariff_name'] . ' (' . __('base') . ')';
            }
        }

        if (isset($bundleTariffs['result'])) {
            foreach ($bundleTariffs['result'] as $io => $each) {
                $result[$each['tariff_id']] = $each['tariff_name'] . ' (' . __('bundle') . ')';
            }
        }

        if (isset($promoTariffs['result'])) {
            if (isset($promoTariffs['result']['promo_limited'])) {
                foreach ($promoTariffs['result']['promo_limited'] as $io => $each) {
                    $result[$each['tariff_id']] = $each['tariff_name'] . ' (' . __('promo limited') . ')';
                }
            }

            if (isset($promoTariffs['result']['promo'])) {
                foreach ($promoTariffs['result']['promo'] as $io => $each) {
                    $result[$each['tariff_id']] = $each['tariff_name'] . ' (' . __('promo') . ')';
                }
            }
        }

        return($result);
    }

    /**
     * Renders tariff creation form
     * 
     * @return string
     */
    public function renderTariffCreateForm() {
        $result = '';
        $remoteTariffs = $this->getTariffsRemote();
        $tmpArr = array();
        if (!empty($remoteTariffs)) {
            foreach ($remoteTariffs as $io => $each) {
                //excluding already registered tariffs. Commented due skybetik request.
                // if (!isset($this->tariffNames[$io])) {
                $tmpArr[$io] = $io . ' - ' . $each;
                //}
            }
        }


        if (!empty($tmpArr)) {
            $tariffsTypes = array(
                'base' => __('Base'),
                'bundle' => __('Bundle'),
                'promo' => __('Promo')
            );

            $inputs = wf_Selector('newtariffid', $tmpArr, __('ID'), '', true);
            $inputs .= wf_TextInput('newtariffname', __('Tariff name'), '', true, 25);
            $inputs .= wf_Selector('newtarifftype', $tariffsTypes, __('Type'), '', true);
            $inputs .= wf_TextInput('newtarifffee', __('Fee'), '0', true, 3, 'finance');
            $inputs .= wf_Submit(__('Create'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

    /**
     * Renders tariff editing form
     * 
     * @param int $tariffId
     * 
     * @return string
     */
    protected function renderTariffEditForm($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = '';
        if (isset($this->allTariffs[$tariffId])) {
            $tariffData = $this->allTariffs[$tariffId];
            if (!empty($tariffData)) {
                $tariffsTypes = array(
                    'base' => __('Base'),
                    'bundle' => __('Bundle'),
                    'promo' => __('Promo')
                );

                $inputs = wf_HiddenInput('edittariffid', $tariffId);
                $inputs .= wf_TextInput('edittariffname', __('Tariff name'), $tariffData['tariffname'], true, 25);
                $inputs .= wf_Selector('edittarifftype', $tariffsTypes, __('Type'), $tariffData['type'], true);
                $inputs .= wf_TextInput('edittarifffee', __('Fee'), $tariffData['fee'], true, 3, 'finance');
                $inputs .= wf_Submit(__('Save'));

                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            }
        }
        return($result);
    }

    /**
     * Catches tariff editing request and edits it in database if something changed
     * 
     * @return void
     */
    public function catchTariffSave() {
        if (wf_CheckPost(array('edittariffid', 'edittariffname', 'edittarifftype'))) {
            $tariffId = vf($_POST['edittariffid'], 3);
            $where = "WHERE `id`='" . $tariffId . "';";
            if (isset($this->allTariffs[$tariffId])) {
                $tariffCurrentData = $this->allTariffs[$tariffId];
                if ($_POST['edittariffname'] != $tariffCurrentData['tariffname']) {
                    simple_update_field('om_tariffs', 'tariffname', $_POST['edittariffname'], $where);
                    log_register('OMEGATV TARIFF EDIT [' . $tariffCurrentData['tariffid'] . '] AS [' . $tariffId . '] NAME `' . $_POST['edittariffname'] . '`');
                }

                if ($_POST['edittarifftype'] != $tariffCurrentData['type']) {
                    simple_update_field('om_tariffs', 'type', $_POST['edittarifftype'], $where);
                    log_register('OMEGATV TARIFF EDIT [' . $tariffCurrentData['tariffid'] . '] AS [' . $tariffId . '] TYPE `' . $_POST['edittarifftype'] . '`');
                }

                if ($_POST['edittarifffee'] != $tariffCurrentData['fee']) {
                    simple_update_field('om_tariffs', 'fee', $_POST['edittarifffee'], $where);
                    log_register('OMEGATV TARIFF EDIT [' . $tariffCurrentData['tariffid'] . '] AS [' . $tariffId . '] FEE `' . $_POST['edittarifffee'] . '`');
                }
            }
        }
    }

    /**
     * Creates new tariff in database
     * 
     * @return void
     */
    public function createTariff() {
        if (wf_CheckPost(array('newtariffid', 'newtariffname', 'newtarifftype'))) {
            $tariffid_f = vf($_POST['newtariffid'], 3);
            $name_f = mysql_real_escape_string($_POST['newtariffname']);
            $type_f = vf($_POST['newtarifftype']);
            $fee = $_POST['newtarifffee'];
            $fee_f = mysql_real_escape_string($fee);
            $query = "INSERT INTO `om_tariffs` (`id`,`tariffid`,`tariffname`,`type`,`fee`) VALUES ";
            $query .= "(NULL,'" . $tariffid_f . "','" . $name_f . "','" . $type_f . "','" . $fee_f . "');";
            nr_query($query);
            $newId = simple_get_lastid('om_tariffs');
            log_register('OMEGATV TARIFF CREATE [' . $tariffid_f . '] AS [' . $newId . '] TYPE `' . $type_f . '` FEE `' . $fee . '`');
        }
    }

    /**
     * Renders list of available tariffs
     * 
     * @return string
     */
    public function renderTariffsList() {
        $result = '';
        if (!empty($this->allTariffs)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Tariff') . ' ' . __('Code'));
            $cells .= wf_TableCell(__('Tariff name'));
            $cells .= wf_TableCell(__('Type'));
            $cells .= wf_TableCell(__('Fee'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allTariffs as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['tariffid']);
                $cells .= wf_TableCell($each['tariffname']);
                $cells .= wf_TableCell(__($each['type']));
                $cells .= wf_TableCell($each['fee']);
                $actLinks = wf_JSAlert(self::URL_ME . '&tariffs=true&deleteid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->renderTariffEditForm($each['id'])) . ' ';
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return($result);
    }

    /**
     * Checks is tariff used by some customers
     * 
     * @param int $id
     * 
     * @return bool
     */
    public function isTariffProtected($id) {
        $id = vf($id, 3);
        $result = false;
        if (isset($this->allTariffs[$id])) {
            $tariffCode = $this->allTariffs[$id]['tariffid'];
            if (!empty($this->allUsers)) {
                foreach ($this->allUsers as $io => $each) {
                    if ($each['basetariffid'] == $tariffCode) {
                        $result = true;
                    }
                    $userBundleTariffs = $this->extractBundle($io);
                    if (isset($userBundleTariffs[$tariffCode])) {
                        $result = true;
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes some tariff from database
     * 
     * @param int $id
     * 
     * @return void
     */
    public function deleteTariff($id) {
        $id = vf($id, 3);
        if (isset($this->allTariffs[$id])) {
            $query = "DELETE from `om_tariffs` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register('OMEGATV TARIFF DELETE [' . $id . ']');
        }
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
     * Returns web-player URL
     * 
     * @param int $customerId
     * 
     * @return string
     */
    public function generateWebURL($customerId) {
        $result = '';
        $userInfo = $this->hls->getUserInfo($customerId);

        if (isset($userInfo['result'])) {
            $userInfo = $userInfo['result'];
            if (!empty($userInfo)) {
                $result .= $userInfo['web_url'];
            }
        }
        return ($result);
    }

    /**
     * Returns web URL by some user login
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function getWebUrlByLogin($userLogin) {
        $result = '';
        $customerId = $this->getLocalCustomerId($userLogin);
        if (!empty($customerId)) {
            //already existing user
            $result .= $this->generateWebURL($customerId);
        } else {
            //first usage
            $this->createUserProfile($userLogin);
            $customerId = $this->getLocalCustomerId($userLogin);
            $result .= $this->generateWebURL($customerId);
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
    public function getLocalCustomerId($userLogin) {
        $result = '';
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if ($each['login'] == $userLogin) {
                    $result = $each['customerid'];
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns local customer login by ID from database
     * 
     * @param int $customerId
     * 
     * @return string
     */
    public function getLocalCustomerLogin($customerId) {
        $result = '';
        if (!empty($this->allUsers)) {
            if (isset($this->allUsers[$customerId])) {
                $result = $this->allUsers[$customerId]['login'];
            }
        }
        return ($result);
    }

    /**
     * Returns new device activation code
     * 
     * @param int $customerId
     * 
     * @return string
     */
    public function generateDeviceCode($customerId) {
        $result = '';
        $codeData = $this->hls->getDeviceCode($customerId);
        if (isset($codeData['result'])) {
            if (isset($codeData['result']['code'])) {
                $result .= $codeData['result']['code'] . ' ' . $this->ajDevCodeLink($customerId, __('Renew'));
            }
        }
        return ($result);
    }

    /**
     * Returns new device activation code by user login
     * 
     * @param string $userLogin
     * 
     * @return string
     */
    public function getDeviceCodeByLogin($userLogin) {
        $result = '';
        $customerId = $this->getLocalCustomerId($userLogin);
        if (!empty($customerId)) {
            $codeData = $this->hls->getDeviceCode($customerId);
            if (isset($codeData['result'])) {
                if (isset($codeData['result']['code'])) {
                    $result .= $codeData['result']['code'];
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes device assigned to some customerid
     * 
     * @param int $customerId
     * @param string $deviceId
     * 
     * @return void
     */
    public function deleteDevice($customerId, $deviceId) {
        $this->hls->deleteDevice($customerId, $deviceId);
    }

    /**
     * Deletes playlist assigned to some customerid
     * 
     * @param int $customerId
     * @param string $playlistId
     * 
     * @return void
     */
    public function deletePlaylist($customerId, $playlistId) {
        $this->hls->deletePlayList($customerId, $playlistId);
    }

    /**
     * Creates new user profile
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    protected function createUserProfile($userLogin) {
        $customerId = $this->generateCustormerId($userLogin);
        $login_f = mysql_real_escape_string($userLogin);
        $curdate = curdatetime();
        $query = "INSERT INTO `om_users` (`id`,`login`,`customerid`,`basetariffid`,`bundletariffs`,`active`,`actdate`) VALUES ";
        $query .= "(NULL,'" . $login_f . "','" . $customerId . "',NULL,NULL,'0','" . $curdate . "');";
        nr_query($query);
        log_register('OMEGATV CUSTOMER REGISTER (' . $userLogin . ') AS [' . $customerId . ']');
        $this->loadUserProfiles();
    }

    /**
     * Returns tariff local data 
     * 
     * @param int $tariffId
     * 
     * @return array
     */
    protected function getTariffData($tariffId) {
        $result = array();
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                if ($each['tariffid'] == $tariffId) {
                    $result = $each;
                }
            }
        }
        return ($result);
    }

    /**
     * Creates some subscription if it possible
     * 
     * @param string $userLogin
     * @param int $tariffId
     * 
     * @return void/string on error
     */
    public function createSubscription($userLogin, $tariffId) {
        $result = '';
        $tariffId = vf($tariffId, 3);
        if (isset($this->tariffNames[$tariffId])) {
            if (isset($this->allUserData[$userLogin])) {
                $customerId = $this->getLocalCustomerId($userLogin);
                if (!empty($customerId)) {
                    $subscriberData = $this->allUsers[$customerId];
                    $tariffData = $this->getTariffData($tariffId);
                    if (!empty($tariffData)) {
                        //base tariff subscription
                        if ($tariffData['type'] == 'base') {
                            if (empty($subscriberData['basetariffid'])) {
                                $setTariffList = array('base' => $tariffId);
                                $this->hls->setUserTariff($customerId, $setTariffList);
                                simple_update_field('om_users', 'basetariffid', $tariffId, "WHERE `customerid`='" . $customerId . "'");
                                $this->hls->setUserActivate($customerId);
                                simple_update_field('om_users', 'active', '1', "WHERE `customerid`='" . $customerId . "'");
                                log_register('OMEGATV SUBSCRIBE TARIFF [' . $tariffId . '] BASE FOR (' . $userLogin . ') AS [' . $customerId . ']');
                                //delete user from suspend queue
                                $this->suspendUser($userLogin, false);
                            } else {
                                $result .= 'Only one base tariff allowed';
                            }
                        }
                        //bundle tariffs subscription
                        if ($tariffData['type'] == 'bundle') {
                            if (!empty($subscriberData['basetariffid'])) {
                                $bundleTariffsCurrent = $this->extractBundle($customerId);
                                if (!isset($bundleTariffsCurrent[$tariffId])) {
                                    $bundleTariffsCurrent[$tariffId] = $tariffId;
                                    $setTariffList = array('base' => $subscriberData['basetariffid'], 'bundle' => $bundleTariffsCurrent);
                                    $this->hls->setUserTariff($customerId, $setTariffList);
                                    $storeBundleTariffs = serialize($bundleTariffsCurrent);
                                    simple_update_field('om_users', 'bundletariffs', $storeBundleTariffs, "WHERE `customerid`='" . $customerId . "'");
                                    log_register('OMEGATV SUBSCRIBE TARIFF [' . $tariffId . '] BUNDLE FOR (' . $userLogin . ') AS [' . $customerId . ']');
                                } else {
                                    $result .= 'Tariff already subscribed';
                                }
                            } else {
                                $result .= 'Available only in addition to base tariff';
                            }
                        }
                    } else {
                        $result .= 'Local tariff not exists';
                    }
                } else {
                    $result .= 'Subscriber profile not found';
                }
            } else {
                $result .= 'User login not found';
            }
        } else {
            $result .= 'Wrong tariff';
        }
        return ($result);
    }

    /**
     * Charges fee for some tariff
     * 
     * @param string $userLogin
     * @param int $tariffId
     * 
     * @return void
     */
    protected function chargeFee($userLogin, $tariffId) {
        $tariffData = $this->getTariffData($tariffId);
        $customerId = $this->getLocalCustomerId($userLogin);
        $tariffFee = $tariffData['fee'];
        zb_CashAdd($userLogin, '-' . $tariffFee, 'add', 1, 'OMEGATV:' . $tariffId);
        log_register('OMEGATV CHARGE TARIFF [' . $tariffId . '] FEE `' . $tariffFee . '` FOR (' . $userLogin . ') AS [' . $customerId . ']');
    }

    /**
     * Sets user as suspended or not to preventing his automatic ressurection
     * 
     * @param string $userLogin
     * @param bool $state
     * 
     * @return void
     */
    protected function suspendUser($userLogin, $state) {
        $login_f = mysql_real_escape_string($userLogin);
        $customerId = $this->getLocalCustomerId($userLogin);
        if ($state) {
            $query = "INSERT INTO `om_suspend` (`id`,`login`) VALUES (NULL,'" . $login_f . "');";
            nr_query($query);
            log_register('OMEGATV SUSPEND USER (' . $userLogin . ') AS [' . $customerId . ']');
        } else {
            $query = "DELETE FROM `om_suspend` WHERE `login`='" . $login_f . "'";
            nr_query($query);
            log_register('OMEGATV UNSUSPEND USER (' . $userLogin . ') AS [' . $customerId . ']');
        }
    }

    /**
     * Deletes or pushes queue for some subscription if it possible
     * 
     * @param string $userLogin
     * @param int $tariffId
     * 
     * @return void/string on error
     */
    public function deleteSubscription($userLogin, $tariffId) {
        $result = '';
        $tariffId = vf($tariffId, 3);
        if (isset($this->tariffNames[$tariffId])) {
            if (isset($this->allUserData[$userLogin])) {
                $customerId = $this->getLocalCustomerId($userLogin);
                if (!empty($customerId)) {
                    $subscriberData = $this->allUsers[$customerId];
                    $tariffData = $this->getTariffData($tariffId);
                    if (!empty($tariffData)) {
                        //base tariff unsubscription
                        if ($tariffData['type'] == 'base') {
                            if (!empty($subscriberData['basetariffid'])) {
                                if ($subscriberData['basetariffid'] == $tariffId) {
                                    //unsubscription right now. Base tariff kills additional tariffs too.
                                    if (!$this->unsubDelay) {
                                        //charging fee for all tariffs
                                        $baseTariffFee = $tariffData['fee'];
                                        $this->chargeFee($userLogin, $tariffId);

                                        $bundleTariffs = $this->extractBundle($customerId);
                                        if (!empty($bundleTariffs)) {
                                            foreach ($bundleTariffs as $io => $each) {
                                                $this->chargeFee($userLogin, $io);
                                            }
                                        }

                                        //setting user down
                                        $this->hls->setUserBlock($customerId);
                                        simple_update_field('om_users', 'active', '0', "WHERE `customerid`='" . $customerId . "'");
                                        //dropping local tariffs
                                        simple_update_field('om_users', 'basetariffid', '', "WHERE `customerid`='" . $customerId . "'");
                                        simple_update_field('om_users', 'bundletariffs', '', "WHERE `customerid`='" . $customerId . "'");
                                        log_register('OMEGATV UNSUBSCRIBE TARIFF [' . $tariffId . '] BASE FOR (' . $userLogin . ') AS [' . $customerId . ']');
                                        //suspending user to prevent his automatic ressurection
                                        $this->suspendUser($userLogin, true);
                                    } else {
                                        //TODO: push unsub to queue
                                    }
                                } else {
                                    $result .= 'This tariff is not assigned for you';
                                }
                            } else {
                                $result .= 'You have not assigned base tariff';
                            }
                        }
                        //bundle tariffs unsubscription
                        if ($tariffData['type'] == 'bundle') {
                            $bundleTariffsCurrent = $this->extractBundle($customerId);
                            //unsubscription right now.
                            if (!$this->unsubDelay) {
                                if (isset($bundleTariffsCurrent[$tariffId])) {
                                    unset($bundleTariffsCurrent[$tariffId]);
                                    $setTariffList = array('base' => $subscriberData['basetariffid'], 'bundle' => $bundleTariffsCurrent);
                                    //charging fee for this bundle tariff
                                    $this->chargeFee($userLogin, $tariffId);
                                    $this->hls->setUserTariff($customerId, $setTariffList);
                                    $storeBundleTariffs = serialize($bundleTariffsCurrent);
                                    simple_update_field('om_users', 'bundletariffs', $storeBundleTariffs, "WHERE `customerid`='" . $customerId . "'");
                                    log_register('OMEGATV UNSUBSCRIBE TARIFF [' . $tariffId . '] BUNDLE FOR (' . $userLogin . ') AS [' . $customerId . ']');
                                } else {
                                    $result .= 'This tariff is not assigned for you';
                                }
                            } else {
                                //TODO: push bundle  unsub to queue
                            }
                        }
                    } else {
                        $result .= 'Local tariff not exists';
                    }
                } else {
                    $result .= 'Subscriber profile not found';
                }
            } else {
                $result .= 'User login not found';
            }
        } else {
            $result .= 'Wrong tariff';
        }
        return ($result);
    }

    /**
     * Renders available subscriptions container list with some controls
     * 
     * @return string
     */
    public function renderUserListContainer() {
        $result = '';
        $columns = array('ID', 'Full address', 'Real Name', 'Cash', 'Base tariff', 'Bundle tariffs', 'Date', 'Active', 'Customer ID', 'Actions');
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&subscriptions=true&ajuserlist=true', false, __('Users'));
        return ($result);
    }

    /**
     * Renders JSON data for ajax user list container content
     * 
     * @return void
     */
    public function ajUserList() {
        $result = '';
        $json = new wf_JqDtHelper();
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                $userAddress = @$this->allUserData[$each['login']]['fulladress'];
                $userLink = wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . $userAddress);
                $data[] = $each['id'];
                $data[] = $userLink;
                $data[] = @$this->allUserData[$each['login']]['realname'];
                $data[] = @$this->allUserData[$each['login']]['Cash'];
                $data[] = $this->getTariffName($each['basetariffid']);
                $bundleList = '';
                if (!empty($each['bundletariffs'])) {
                    $allBundle = unserialize($each['bundletariffs']);
                    if (!empty($allBundle)) {
                        foreach ($allBundle as $bundleTariffId => $eachbundleData) {
                            $bundleList .= $this->getTariffName($bundleTariffId) . ' ';
                        }
                    }
                }
                $data[] = $bundleList;
                $data[] = $each['actdate'];
                $data[] = web_bool_led($each['active'], true);
                $data[] = $each['customerid'];
                $actLinks = wf_Link(self::URL_ME . '&customerprofile=' . $each['customerid'], web_edit_icon());
                $actLinks .= wf_Link('https://admin.hls.tv/customers/operator#page=1&search=' . $each['customerid'], web_icon_search());
                $data[] = $actLinks;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Charges all users tariffs fee, disables it when users go down
     * 
     * @return void
     */
    public function chargeAllUsersFee() {
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if ($each['active']) {
                    if (isset($this->allUserData[$each['login']])) {
                        if (!$this->allUserData[$each['login']]['Passive']) {
                            $userInternetTariff = $this->allUserData[$each['login']]['Tariff'];
                            //user is not on internet bundled tariff
                            if (!isset($this->bundledTariffs[$userInternetTariff])) {
                                if (!empty($each['basetariffid'])) {
                                    $this->chargeFee($each['login'], $each['basetariffid']);
                                    $userBundleTariffs = $this->extractBundle($each['customerid']);
                                    if (!empty($userBundleTariffs)) {
                                        foreach ($userBundleTariffs as $eachBundleId => $eachBundleTariff) {
                                            $this->chargeFee($each['login'], $eachBundleId);
                                        }
                                    }
                                }
                            } else {
                                log_register('OMEGATV CHARGE SKIP INETBUNDLE FOR (' . $each['login'] . ') AS [' . $each['id'] . ']');
                            }
                            //end of internet bundle  check
                        }
                    }
                }
            }

            //checking for debtors/freezed users and disabling it
            $this->loadUserData();
            foreach ($this->allUsers as $io => $each) {
                if ($each['active']) {
                    if (isset($this->allUserData[$each['login']])) {
                        $userData = $this->allUserData[$each['login']];
                        if ($userData['Passive']) {
                            //user is frozen by some reason - need to disable him
                            $this->hls->setUserBlock($each['customerid']);
                            simple_update_field('om_users', 'active', '0', "WHERE `customerid`='" . $each['customerid'] . "'");
                            log_register('OMEGATV BLOCK FROZEN USER (' . $each['login'] . ') AS [' . $each['customerid'] . ']');
                        }

                        //if user have debt after charging fee - we need to block him too
                        if ($userData['Cash'] < '-' . $userData['Credit']) {
                            $this->hls->setUserBlock($each['customerid']);
                            simple_update_field('om_users', 'active', '0', "WHERE `customerid`='" . $each['customerid'] . "'");
                            log_register('OMEGATV BLOCK DEBTOR USER (' . $each['login'] . ') AS [' . $each['customerid'] . ']');
                        }
                    }
                }
            }
        }
    }

    /**
     * Resurrects some users if their was disabled by inactivity
     * 
     * @return void
     */
    public function resurrectAllUsers() {
        if (!empty($this->allUsers)) {
            foreach ($this->allUsers as $io => $each) {
                if (!$each['active']) {
                    if (isset($this->allUserData[$each['login']])) {
                        $userData = $this->allUserData[$each['login']];
                        if (($userData['Passive'] == 0) AND ( $userData['Cash'] >= '-' . $userData['Credit'])) {
                            if (!empty($each['basetariffid'])) {
                                //check is user resurrection suspended?
                                if (!isset($this->suspended[$each['login']])) {
                                    //unblock this user
                                    $this->hls->setUserActivate($each['customerid']);
                                    simple_update_field('om_users', 'active', '1', "WHERE `customerid`='" . $each['customerid'] . "'");
                                    log_register('OMEGATV RESURRECT USER (' . $each['login'] . ') AS [' . $each['customerid'] . ']');
                                }
                            }
                        }
                    }
                }
            }
        }
    }

}

?>
